<?php

namespace App\Services;

use App\Models\SyllabusMapping;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Automatic report section resolvers. Every resolver is null-safe and returns
 * explicit "No data available" notices rather than fabricated figures.
 *
 * @return array{content: string, payload: array<string, mixed>}
 */
class ReportDataSources
{
    public const LABELS = [
        'enrollment.branch_learners' => 'Learner numbers (this branch)',
        'enrollment.org_learners' => 'Learner population (all branches)',
        'enrollment.org_breakdown' => 'School population by branch',
        'syllabus.coverage' => 'Syllabus mapping coverage',
        'assessment.formative' => 'Formative assessments',
        'assessments.summary' => 'Formative assessments',
        'attendance.branch_summary' => 'Attendance summary (this scope)',
        'attendance.org_summary' => 'Attendance summary (all branches)',
        'attendance.teacher_summary' => 'Attendance summary (own classes)',
        'discipline.branch_summary' => 'Discipline summary (this scope)',
        'discipline.org_summary' => 'Discipline summary (all branches)',
        'discipline.teacher_summary' => 'Discipline summary (own classes)',
        'lesson_attendance.note' => 'Lesson attendance',
        'documents.status' => 'Professional documents',
    ];

    public function resolve(string $source, User $actor): array
    {
        $scope = ReportScope::for($actor);

        return match ($source) {
            'enrollment.branch_learners' => $this->enrollmentLearners($scope->branchIds()),
            'enrollment.org_learners', 'enrollment.org_breakdown' => $this->enrollmentBreakdown(),
            'syllabus.coverage' => $this->syllabusCoverage($scope),
            'attendance.branch_summary' => $this->attendanceSummary($scope, false),
            'attendance.org_summary' => $this->attendanceSummary($scope, true),
            'attendance.teacher_summary' => $this->attendanceSummary($scope, false),
            'discipline.branch_summary' => $this->disciplineSummary($scope, false),
            'discipline.org_summary' => $this->disciplineSummary($scope, true),
            'discipline.teacher_summary' => $this->disciplineSummary($scope, false),
            'assessments.summary', 'assessment.formative' => $this->assessmentsSummary($scope),
            'lesson_attendance.note' => $this->note('Lesson attendance is not yet captured in the system. Add a narrative observation for this section.', ['available' => false]),
            'documents.status' => $this->note('Professional documents are not yet tracked in the system. Add a narrative observation for this section.', ['available' => false]),
            default => $this->note('No automatic data source is configured for this section. Add a narrative observation.', ['available' => false, 'source' => $source]),
        };
    }

    /** Render a plain-text/HTML string safe for both Blade and PDF output. */
    public function resolveContent(string $source, User $actor): string
    {
        return $this->resolve($source, $actor)['content'];
    }

    // ------------------------------------------------------------------
    // Enrollment
    // ------------------------------------------------------------------

    private function enrollmentLearners(?array $branchIds): array
    {
        $branchNames = $branchIds === null ? null : \App\Models\Branch::whereIn('id', $branchIds)->pluck('name')->all();

        $sessionId = \App\Models\YearSession::query()->orderByDesc('id')->value('id');
        $termId = $sessionId
            ? \App\Models\Term::query()->where('year_session_id', $sessionId)->orderByDesc('id')->value('id')
            : null;

        $rows = \App\Models\Enrollment::query()
            ->when($branchNames !== null, fn ($q) => $q->whereIn('branch', $branchNames === [] ? ['— none —'] : $branchNames))
            ->when($sessionId, fn ($q) => $q->where('year_session_id', $sessionId))
            ->when($termId, fn ($q) => $q->where('term_id', $termId))
            ->get();

        $total = (int) $rows->sum('total_learners');

        if ($rows->isEmpty()) {
            return $this->note('No enrollment records have been captured yet for this scope. Enter learner information manually or record it in the Enrollment module first.', [
                'available' => false,
                'total' => 0,
            ]);
        }

        $table = '<table cellpadding="4" cellspacing="0" border="0" style="border-collapse:collapse">'
            . '<thead><tr><th align="left">Branch</th><th align="right">Learners</th></tr></thead><tbody>';
        foreach ($rows->groupBy('branch') as $branch => $branchRows) {
            $table .= '<tr><td>' . e($branch ?: 'Unspecified') . '</td><td align="right">' . (int) $branchRows->sum('total_learners') . '</td></tr>';
        }
        $table .= '<tr><td><strong>Total</strong></td><td align="right"><strong>' . $total . '</strong></td></tr>';
        $table .= '</tbody></table>';

        return [
            'content' => $table . "\n\nTotal learners in scope: <strong>{$total}</strong>.",
            'payload' => ['available' => true, 'total' => $total, 'rows' => $rows->map(fn ($r) => ['branch' => $r->branch, 'learners' => (int) $r->total_learners])->all()],
        ];
    }

    private function enrollmentBreakdown(): array
    {
        return $this->enrollmentLearners(null);
    }

    // ------------------------------------------------------------------
    // Syllabus coverage
    // ------------------------------------------------------------------

    private function syllabusCoverage(ReportScope $scope): array
    {
        $coverage = app(\App\Services\SyllabusCoverage::class);

        $mappings = $scope->mappingsQuery(SyllabusMapping::query()->with(['class.branch', 'subject', 'term', 'yearSession', 'stream']))->get();

        if ($mappings->isEmpty()) {
            return $this->note('No syllabus mappings exist for this scope yet.', ['available' => false, 'mappings' => 0]);
        }

        $rows = [];
        $totalLessons = 0;
        $completedLessons = 0;
        $overdueLessons = 0;
        $coverageSum = 0.0;

        foreach ($mappings as $mapping) {
            $stats = $coverage->stats($mapping);
            $totalLessons += $stats['total_lessons'];
            $completedLessons += $stats['completed_lessons'];
            $overdueLessons += $stats['overdue_lessons'];
            $coverageSum += $stats['coverage_percent'];

            $rows[] = [
                'grade_stream' => $mapping->grade_stream,
                'subject' => $mapping->subject?->name,
                'coverage' => $stats['coverage_percent'],
                'completed' => $stats['completed_lessons'],
                'total' => $stats['total_lessons'],
                'overdue' => $stats['overdue_lessons'],
            ];
        }

        $avg = $mappings->count() > 0 ? round($coverageSum / $mappings->count(), 2) : 0.0;

        $table = '<table cellpadding="4" cellspacing="0" border="0" style="border-collapse:collapse">'
            . '<thead><tr><th align="left">Class / Stream</th><th align="left">Subject</th><th align="right">Coverage</th><th align="right">Done</th><th align="right">Overdue</th></tr></thead><tbody>';
        foreach ($rows as $row) {
            $table .= '<tr><td>' . e($row['grade_stream']) . '</td><td>' . e((string) $row['subject']) . '</td><td align="right">' . number_format($row['coverage'], 2) . '%</td><td align="right">' . $row['completed'] . '/' . $row['total'] . '</td><td align="right">' . $row['overdue'] . '</td></tr>';
        }
        $table .= '</tbody></table>';

        $summary = sprintf(
            '%d mapping(s) · %d/%d lessons completed · %d overdue · average coverage <strong>%.2f%%</strong>',
            $mappings->count(),
            $completedLessons,
            $totalLessons,
            $overdueLessons,
            $avg
        );

        return [
            'content' => $summary . "\n\n" . $table,
            'payload' => [
                'available' => true,
                'mappings' => $mappings->count(),
                'total_lessons' => $totalLessons,
                'completed_lessons' => $completedLessons,
                'overdue_lessons' => $overdueLessons,
                'average_coverage' => $avg,
                'rows' => $rows,
            ],
        ];
    }

    // ------------------------------------------------------------------
    // Attendance
    // ------------------------------------------------------------------

    private function attendanceSummary(ReportScope $scope, bool $orgWide): array
    {
        $branchIds = $orgWide ? null : $scope->branchIds();
        $branchNames = $branchIds === null ? null : \App\Models\Branch::whereIn('id', $branchIds)->pluck('name')->all();

        $sessionId = \App\Models\YearSession::query()->orderByDesc('id')->value('id');
        $termId = $sessionId
            ? \App\Models\Term::query()->where('year_session_id', $sessionId)->orderByDesc('id')->value('id')
            : null;

        $rows = \App\Models\Attendance::query()
            ->when($branchNames !== null, fn ($q) => $q->whereIn('branch', $branchNames === [] ? ['— none —'] : $branchNames))
            ->when($sessionId, fn ($q) => $q->where('year_session_id', $sessionId))
            ->when($termId, fn ($q) => $q->where('term_id', $termId))
            ->get();

        if ($rows->isEmpty()) {
            return $this->note('No attendance records have been captured yet for this scope.', ['available' => false, 'records' => 0]);
        }

        $classTotal = (int) $rows->sum('class_total');
        $present = (int) $rows->sum('total_present');
        $absent = (int) $rows->sum('total_absent');
        $pctPresent = $classTotal > 0 ? round($present / $classTotal * 100, 2) : 0.0;
        $pctAbsent = $classTotal > 0 ? round($absent / $classTotal * 100, 2) : 0.0;

        return [
            'content' => sprintf(
                '%d attendance record(s) for this scope · <strong>%.2f%% present</strong> · %.2f%% absent (%d present / %d absent of %d learner-days).',
                $rows->count(),
                $pctPresent,
                $pctAbsent,
                $present,
                $absent,
                $classTotal
            ),
            'payload' => [
                'available' => true,
                'records' => $rows->count(),
                'present_percent' => $pctPresent,
                'absent_percent' => $pctAbsent,
                'total_present' => $present,
                'total_absent' => $absent,
            ],
        ];
    }

    // ------------------------------------------------------------------
    // Discipline
    // ------------------------------------------------------------------

    private function disciplineSummary(ReportScope $scope, bool $orgWide): array
    {
        $branchIds = $orgWide ? null : $scope->branchIds();
        $classIds = null;

        if ($scope->kind === 'own') {
            $classIds = \App\Models\SubjectAssignment::query()
                ->where('user_id', $scope->user->id)
                ->distinct()
                ->pluck('class_id')
                ->all();
        }

        $rows = \App\Models\Discipline::query()
            ->when($branchIds !== null && $scope->kind !== 'own', fn ($q) => $q->join('classes', 'classes.id', '=', 'disciplines.class_id')->whereIn('classes.branch_id', $branchIds ?: [0])->select('disciplines.*'))
            ->when($classIds !== null, fn ($q) => $q->whereIn('disciplines.class_id', $classIds ?: [0]))
            ->orderByDesc('disciplines.id')
            ->get();

        if ($rows->isEmpty()) {
            return $this->note('No discipline cases have been recorded yet for this scope.', ['available' => false, 'cases' => 0]);
        }

        $list = $rows->take(8)->map(fn ($d) => '— ' . $d->case_name . ($d->learner_name ? ' (' . $d->learner_name . ')' : ''))->implode("<br>\n");

        return [
            'content' => sprintf('%d discipline case(s) recorded.<br>Most recent:<br>%s', $rows->count(), $list),
            'payload' => ['available' => true, 'cases' => $rows->count()],
        ];
    }

    // ------------------------------------------------------------------
    // Formative assessments
    // ------------------------------------------------------------------

    private function assessmentsSummary(ReportScope $scope): array
    {
        $mappingIds = $scope->mappingIds();

        if ($mappingIds->isEmpty()) {
            return $this->note('No syllabus mappings exist for this scope yet, so there are no formative assessments to report.', ['available' => false, 'assessments' => 0]);
        }

        $rows = \App\Models\SyllabusAssessment::query()
            ->whereIn('syllabus_mapping_id', $mappingIds->all())
            ->orderByDesc('assessment_date')
            ->get();

        if ($rows->isEmpty()) {
            return $this->note('No formative assessments have been recorded yet for this scope.', ['available' => false, 'assessments' => 0]);
        }

        $list = $rows->take(8)->map(fn ($a) => sprintf(
            '— %s%s%s (%d topic(s))',
            $a->assessment_date->format('d/m/Y'),
            $a->assessment_type ? ' · ' . $a->assessment_type : '',
            $a->description ? ' · ' . $a->description : '',
            $a->lessons()->count()
        ))->implode("<br>\n");

        return [
            'content' => sprintf('%d formative assessment(s) recorded.<br>Most recent:<br>%s', $rows->count(), $list),
            'payload' => ['available' => true, 'assessments' => $rows->count()],
        ];
    }

    private function note(string $message, array $payload = []): array
    {
        return [
            'content' => $message,
            'payload' => array_merge(['available' => false], $payload),
        ];
    }
}
