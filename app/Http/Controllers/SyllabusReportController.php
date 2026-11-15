<?php

namespace App\Http\Controllers;

use App\Models\SyllabusMapping;
use App\Services\SyllabusCoverage;
use App\Support\SyllabusActivityLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SyllabusReportController extends Controller
{
    public function __construct(
        private readonly SyllabusCoverage $coverage,
    ) {}

    public function mappingPdf(Request $request, SyllabusMapping $mapping): Response
    {
        $user = Auth::user();
        abort_unless($user?->can('syllabus.reports.view') && $this->canAccessMapping($user, $mapping), 403);

        $teacherId = $this->resolveTeacherId($request, $mapping);

        $lessons = $this->coverage->lessons($mapping, $teacherId);
        $groups = $this->coverage->groups($mapping, $teacherId);
        $stats = $this->coverage->stats($mapping, $teacherId);
        $assessmentDates = $this->coverage->assessmentDates($lessons);
        $coordinatorComments = $this->coverage->coordinatorComments($mapping);

        $progress = [];

        foreach ($lessons as $lesson) {
            $p = $lesson->progress->first();
            $progress[$lesson->id] = [
                'completion' => $p?->completion_date?->toDateString(),
                'comment' => $p?->teacher_comment,
            ];
        }

        $teacher = $teacherId ? \App\Models\User::find($teacherId) : null;

        SyllabusActivityLogger::record('syllabus_mapping', $mapping->id, 'report_viewed', null, ['format' => 'pdf', 'teacher_id' => $teacherId]);

        return Pdf::loadView('pdf.syllabus-mapping', compact('mapping', 'groups', 'stats', 'progress', 'assessmentDates', 'coordinatorComments', 'teacher'))
            ->setPaper('a4', 'landscape')
            ->stream('syllabus-mapping.pdf');
    }

    public function mappingExcel(Request $request, SyllabusMapping $mapping): StreamedResponse
    {
        $user = Auth::user();
        abort_unless($user?->can('syllabus.reports.view') && $this->canAccessMapping($user, $mapping), 403);

        $teacherId = $this->resolveTeacherId($request, $mapping);

        $lessons = $this->coverage->lessons($mapping, $teacherId);
        $groups = $this->coverage->groups($mapping, $teacherId);
        $stats = $this->coverage->stats($mapping, $teacherId);
        $assessmentDates = $this->coverage->assessmentDates($lessons);
        $coordinatorComments = $this->coverage->coordinatorComments($mapping);

        $progress = [];

        foreach ($lessons as $lesson) {
            $p = $lesson->progress->first();
            $progress[$lesson->id] = [
                'completion' => $p?->completion_date?->toDateString(),
                'comment' => $p?->teacher_comment,
            ];
        }

        $teacherName = $teacherId ? \App\Models\User::whereKey($teacherId)->value('name') : '—';
        $filename = sprintf('syllabus-mapping-%s-%s.xlsx', $mapping->subject->code ?: $mapping->subject->name, str_replace(' ', '-', $mapping->grade_stream));

        SyllabusActivityLogger::record('syllabus_mapping', $mapping->id, 'report_viewed', null, ['format' => 'excel', 'teacher_id' => $teacherId]);

        return response()->streamDownload(function () use ($mapping, $groups, $stats, $progress, $assessmentDates, $coordinatorComments, $teacherName, $filename): void {
            $writer = new XlsxWriter;
            $writer->openToBrowser($filename);

            $titleStyle = (new Style)->setFontBold()->setFontSize(16)->setFontColor(Color::toARGB(Color::DARK_BLUE));
            $metaStyle = (new Style)->setFontSize(9)->setFontColor(Color::toARGB('4B5563'));
            $headerStyle = (new Style)->setFontBold()->setFontSize(9)->setFontColor(Color::toARGB(Color::WHITE))->setBackgroundColor(Color::toARGB('4B5563'))->setCellAlignment(CellAlignment::CENTER);
            $strandStyle = (new Style)->setFontBold()->setFontSize(9)->setFontColor(Color::toARGB(Color::WHITE))->setBackgroundColor(Color::toARGB(Color::DARK_BLUE));
            $substrandStyle = (new Style)->setFontBold()->setFontSize(8)->setBackgroundColor(Color::toARGB('E5E7EB'));
            $dataStyle = (new Style)->setFontSize(9)->setBorder(new Border(new BorderPart(Border::BOTTOM, Color::toARGB('D1D5DB'), Border::WIDTH_THIN, Border::STYLE_SOLID)));
            $completedStyle = (new Style)->setFontSize(9)->setBackgroundColor(Color::toARGB('F0FDF4'))->setBorder(new Border(new BorderPart(Border::BOTTOM, Color::toARGB('D1D5DB'), Border::WIDTH_THIN, Border::STYLE_SOLID)));

            $writer->addRow(Row::fromValues(['Syllabus Mapping Outline — '.$mapping->subject->name], $titleStyle));
            $writer->addRow(Row::fromValues([sprintf('%s · %s · %s · Campus: %s · Teacher: %s', $mapping->grade_stream, $mapping->term->name, $mapping->yearSession->name, $mapping->branch ?? 'School-wide', $teacherName)], $metaStyle));
            $writer->addRow(Row::fromValues([sprintf('Total: %d · Completed: %d · Pending: %d · Overdue: %d · Late: %d · Coverage: %.2f%%', $stats['total_lessons'], $stats['completed_lessons'], $stats['pending_lessons'], $stats['overdue_lessons'], $stats['late_lessons'], $stats['coverage_percent'])], $metaStyle));
            $writer->addRow(Row::fromValues([]));

            $writer->addRow(Row::fromValues(['Lesson No.', 'Out Of', 'Theme / Strand', 'Substrand', 'Lesson Topic', 'Date of Completion', 'Late by', '% Coverage', "Teachers' Comments", 'Date of Assessment', "Coordinators' Comments"], $headerStyle));

            foreach ($groups as $strandGroup) {
                $strandExpected = $strandGroup['strand']->expected_completion_date;
                $strandLabel = $strandGroup['strand']->name.($strandExpected ? ' — Expected: '.$strandExpected->format('d/m/Y') : ' — No expected date');
                $writer->addRow(Row::fromValues([$strandLabel], $strandStyle));

                foreach ($strandGroup['substrands'] as $substrandGroup) {
                    $writer->addRow(Row::fromValues(['', '', '', $substrandGroup['substrand']->name], $substrandStyle));

                    foreach ($substrandGroup['lessons'] as $lesson) {
                        $p = $progress[$lesson->id] ?? ['completion' => null, 'comment' => null];
                        $completed = ! empty($p['completion']);
                        $strandExpected = $strandGroup['strand']->expected_completion_date;
                        $lateDays = $strandExpected && $completed
                            ? (int) $strandExpected->copy()->startOfDay()->diffInDays(\Carbon\Carbon::parse($p['completion'])->startOfDay(), false)
                            : null;
                        $lateLabel = $lateDays === null ? '' : ($lateDays > 0 ? $lateDays.' day(s) late' : 'On time');
                        $coverage = $mapping->total_lessons > 0 && $completed ? round(($lesson->lesson_number / $mapping->total_lessons) * 100, 2) : 0.0;
                        $style = $completed ? $completedStyle : $dataStyle;

                        $writer->addRow(Row::fromValues([
                            $lesson->lesson_number,
                            $mapping->total_lessons,
                            $strandGroup['strand']->name,
                            $substrandGroup['substrand']->name,
                            $lesson->topic,
                            $p['completion'] ? \Carbon\Carbon::parse($p['completion'])->format('d/m/Y') : '',
                            $lateLabel,
                            number_format($coverage, 2).'%',
                            $p['comment'] ?? '',
                            $assessmentDates[$lesson->id] ?? '',
                            $coordinatorComments[$strandGroup['strand']->id] ?? '',
                        ], $style));
                    }
                }
            }

            $writer->close();
        }, 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function coveragePdf(Request $request): Response
    {
        $user = Auth::user();
        abort_unless($this->canViewSchoolWideReports($user), 403);

        [$rows, $filters] = $this->coverageRows($request);

        SyllabusActivityLogger::record('syllabus_report', null, 'coverage_report_viewed', null, ['format' => 'pdf', 'filters' => $filters]);

        return Pdf::loadView('pdf.syllabus-coverage', compact('rows', 'filters'))
            ->setPaper('a4', 'landscape')
            ->stream('syllabus-coverage.pdf');
    }

    public function coverageExcel(Request $request): StreamedResponse
    {
        $user = Auth::user();
        abort_unless($this->canViewSchoolWideReports($user), 403);

        [$rows, $filters] = $this->coverageRows($request);

        SyllabusActivityLogger::record('syllabus_report', null, 'coverage_report_viewed', null, ['format' => 'excel', 'filters' => $filters]);

        return response()->streamDownload(function () use ($rows, $filters): void {
            $writer = new XlsxWriter;
            $writer->openToBrowser('syllabus-coverage.xlsx');

            $titleStyle = (new Style)->setFontBold()->setFontSize(16)->setFontColor(Color::toARGB(Color::DARK_BLUE));
            $metaStyle = (new Style)->setFontSize(9)->setFontColor(Color::toARGB('4B5563'));
            $headerStyle = (new Style)->setFontBold()->setFontSize(9)->setFontColor(Color::toARGB(Color::WHITE))->setBackgroundColor(Color::toARGB('4B5563'));
            $dataStyle = (new Style)->setFontSize(9)->setBorder(new Border(new BorderPart(Border::BOTTOM, Color::toARGB('D1D5DB'), Border::WIDTH_THIN, Border::STYLE_SOLID)));

            $writer->addRow(Row::fromValues(['Syllabus Coverage Report'], $titleStyle));
            $writer->addRow(Row::fromValues([implode(' · ', array_filter($filters)) ?: 'All mappings'], $metaStyle));
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues(['Grade', 'Subject', 'Campus', 'Teacher', 'Total', 'Completed', 'Pending', 'Overdue', 'Late', 'Coverage %', 'Status'], $headerStyle));

            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues([
                    $row['grade'],
                    $row['subject'],
                    $row['branch'] ?? 'School-wide',
                    $row['teacher'],
                    $row['total_lessons'],
                    $row['completed_lessons'],
                    $row['pending_lessons'],
                    $row['overdue_lessons'],
                    $row['late_lessons'],
                    round($row['coverage_percent'], 2),
                    $row['status'],
                ], $dataStyle));
            }

            $writer->close();
        }, 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<string, string>}
     */
    private function coverageRows(Request $request): array
    {
        $filters = array_filter([
            'year' => \App\Models\YearSession::find($request->integer('year_session_id'))?->name,
            'term' => \App\Models\Term::find($request->integer('term_id'))?->name,
            'branch' => $request->string('branch')->toString() ?: null,
            'class' => \App\Models\Classes::find($request->integer('class_id'))?->name,
            'subject' => \App\Models\Subject::find($request->integer('subject_id'))?->name,
            'teacher' => \App\Models\User::find($request->integer('teacher_id'))?->name,
        ]);

        $user = Auth::user();

        $query = SyllabusMapping::query()
            ->with(['class.branch', 'subject', 'term', 'yearSession', 'stream'])
            ->when(! $user?->hasSyllabusViewAllAccess(), fn ($q) => $q->accessibleTo($user))
            ->when($request->integer('year_session_id'), fn ($q, $v) => $q->where('year_session_id', $v))
            ->when($request->integer('term_id'), fn ($q, $v) => $q->where('term_id', $v))
            ->when($request->string('branch')->toString(), function ($q, $v): void {
                $q->whereHas('class.branch', fn ($branchQuery) => $branchQuery->where('name', $v));
            })
            ->when($request->integer('class_id'), fn ($q, $v) => $q->where('class_id', $v))
            ->when($request->integer('subject_id'), fn ($q, $v) => $q->where('subject_id', $v))
            ->when($request->integer('teacher_id'), function ($q, $v): void {
                $assignments = \App\Models\SubjectAssignment::query()
                    ->where('user_id', $v)
                    ->where('assignment_role', \App\Models\SubjectAssignment::ROLE_TEACHER)
                    ->get(['subject_id', 'class_id', 'branch']);

                if ($assignments->isEmpty()) {
                    $q->whereKey(0);

                    return;
                }

                $q->where(function ($inner) use ($assignments): void {
                    foreach ($assignments as $assignment) {
                        $inner->orWhere(function ($qq) use ($assignment): void {
                            $qq->where('subject_id', $assignment->subject_id)
                                ->where('class_id', $assignment->class_id);

                            if ($assignment->branch) {
                                $qq->whereHas('class.branch', fn ($branchQuery) => $branchQuery->where('name', $assignment->branch));
                            }
                        });
                    }
                });
            });

        $rows = [];

        foreach ($query->get() as $mapping) {
            $stats = $this->coverage->stats($mapping);
            $teacher = $mapping->assignedTeacher();

            $rows[] = [
                'grade' => $mapping->grade_stream,
                'subject' => $mapping->subject->name,
                'branch' => $mapping->branch,
                'teacher' => $teacher?->name ?? '—',
                'total_lessons' => $stats['total_lessons'],
                'completed_lessons' => $stats['completed_lessons'],
                'pending_lessons' => $stats['pending_lessons'],
                'overdue_lessons' => $stats['overdue_lessons'],
                'late_lessons' => $stats['late_lessons'],
                'coverage_percent' => $stats['coverage_percent'],
                'status' => $mapping->status->label(),
            ];
        }

        usort($rows, fn ($a, $b) => $a['grade'] <=> $b['grade'] ?: $a['subject'] <=> $b['subject']);

        return [$rows, $filters];
    }

    private function canAccessMapping(?\App\Models\User $user, SyllabusMapping $mapping): bool
    {
        if (! $user) {
            return false;
        }

        return $user->isAssignedToMapping($mapping);
    }

    private function canViewSchoolWideReports(?\App\Models\User $user): bool
    {
        return $user?->hasSyllabusViewAllAccess() ?? false;
    }

    private function resolveTeacherId(Request $request, SyllabusMapping $mapping): ?int
    {
        $teacherId = $request->integer('teacher_id') ?: null;

        if ($teacherId && $mapping->assignedTeachers()->pluck('id')->contains($teacherId)) {
            return $teacherId;
        }

        $user = Auth::user();

        if ($user && $user->hasRole('Teacher') && ! $user->hasUnrestrictedAccess() && $mapping->assignedTeachers()->pluck('id')->contains($user->id)) {
            return $user->id;
        }

        return $mapping->assignedTeacher()?->id;
    }
}
