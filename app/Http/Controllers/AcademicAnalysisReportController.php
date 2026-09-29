<?php

namespace App\Http\Controllers;

use App\Models\AcademicAnalysis;
use App\Models\Term;
use App\Models\User;
use App\Models\YearSession;
use Barryvdh\DomPDF\Facade\Pdf;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AcademicAnalysisReportController extends Controller
{
    /**
     * @return array{user: User, branch: string, section: string}
     */
    private function authorizeSection(string $branch, string $section): array
    {
        $user = auth()->user();

        abort_unless($user, 403);

        if ($user->hasUnrestrictedAccess() || $user->isHead()) {
            return ['user' => $user, 'branch' => $branch, 'section' => $section];
        }

        if ($user->isDeputy()) {
            abort_unless($branch === $user->branch, 403);

            return ['user' => $user, 'branch' => $branch, 'section' => $section];
        }

        abort_unless(
            $user->hasRole('Coordinators')
            && $user->sectionCoordinatorAssignments()->where('branch', $branch)->where('section', $section)->exists(),
            403
        );

        return ['user' => $user, 'branch' => $branch, 'section' => $section];
    }

    private function baseQuery(User $user)
    {
        return AcademicAnalysis::query()
            ->accessibleTo($user)
            ->with(['yearSession', 'term', 'class', 'stream'])
            ->orderBy('branch')
            ->orderBy('section');
    }

    public function pdf(AcademicAnalysis $analysis): Response
    {
        $user = auth()->user();
        abort_unless($user, 403);
        abort_unless(
            $user->hasUnrestrictedAccess() || $user->isHead() || $user->isDeputy()
            || $user->can('view_academic::analysis'),
            403
        );

        $scoped = $this->baseQuery($user)->whereKey($analysis->id)->firstOrFail();
        $stats = $scoped->subjectStats();

        return Pdf::loadView('pdf.academic-analysis', [
            'mode' => 'record',
            'analysis' => $scoped,
            'stats' => $stats,
            'averages' => $scoped->averagesRow(),
        ])
            ->setPaper('a4', 'landscape')
            ->stream("academic-analysis-{$scoped->id}.pdf");
    }

    public function excel(AcademicAnalysis $analysis): StreamedResponse
    {
        $user = auth()->user();
        abort_unless($user, 403);
        abort_unless(
            $user->hasUnrestrictedAccess() || $user->isHead() || $user->isDeputy()
            || $user->can('view_academic::analysis'),
            403
        );

        $scoped = $this->baseQuery($user)->whereKey($analysis->id)->firstOrFail();

        return $this->streamExcel(
            $this->recordTitle($scoped),
            [[
                'heading' => $this->recordSubtitle($scoped),
                'headers' => $this->subjectHeaders(),
                'rows' => array_map(fn (array $s): array => $this->subjectExcelRow($s), $scoped->subjectStats()),
                'subtotal' => $this->averagesExcelRow($scoped->averagesRow(), 'AVERAGES'),
            ]],
            "academic-analysis-{$scoped->id}.xlsx"
        );
    }

    public function sectionPdf(int $yearSession, int $term, string $exam, string $branch, string $section): Response
    {
        $report = $this->sectionReport($yearSession, $term, $exam, $branch, $section);

        return Pdf::loadView('pdf.academic-analysis', $report)
            ->setPaper('a4', 'landscape')
            ->stream("academic-analysis-section-{$yearSession}-{$term}.pdf");
    }

    public function sectionExcel(int $yearSession, int $term, string $exam, string $branch, string $section): StreamedResponse
    {
        $report = $this->sectionReport($yearSession, $term, $exam, $branch, $section);

        $tables = [];

        foreach ($report['streams'] as $stream) {
            $tables[] = [
                'heading' => $stream['label'],
                'headers' => $this->subjectHeaders(),
                'rows' => array_map(fn (array $s): array => $this->subjectExcelRow($s), $stream['stats']),
                'subtotal' => $this->averagesExcelRow($stream['averages'], 'AVERAGES'),
            ];
        }

        $tables[] = [
            'heading' => 'SECTION SUMMARY - '.$section,
            'headers' => $this->subjectHeaders(),
            'rows' => array_map(fn (array $s): array => $this->subjectExcelRow($s), $report['subjects']),
            'subtotal' => $this->averagesExcelRow($report['subject_averages'], 'SECTION AVERAGE'),
        ];

        return $this->streamExcel(
            $report['title'].' - '.$report['subtitle'],
            $tables,
            "academic-analysis-section-{$yearSession}-{$term}.xlsx"
        );
    }

    public function yearPdf(int $yearSession, string $branch = 'all', string $section = 'all'): Response
    {
        $report = $this->yearReport($yearSession, $branch, $section);

        return Pdf::loadView('pdf.academic-analysis', $report)
            ->setPaper('a4', 'landscape')
            ->stream("academic-analysis-year-{$yearSession}.pdf");
    }

    public function yearExcel(int $yearSession, string $branch = 'all', string $section = 'all'): StreamedResponse
    {
        $report = $this->yearReport($yearSession, $branch, $section);

        $tables = [];

        foreach ($report['sections'] as $sectionData) {
            $tables[] = [
                'heading' => $sectionData['label'],
                'headers' => $this->subjectHeaders(),
                'rows' => array_map(fn (array $s): array => $this->subjectExcelRow($s), $sectionData['subjects']),
                'subtotal' => $this->averagesExcelRow($sectionData['averages'], 'YEAR AVERAGE'),
            ];
        }

        return $this->streamExcel($report['title'], $tables, "academic-analysis-year-{$yearSession}.xlsx");
    }

    public function schoolsPdf(int $yearSession): Response
    {
        $user = auth()->user();
        abort_unless($user?->hasUnrestrictedAccess() || $user?->isHead(), 403);

        $session = YearSession::findOrFail($yearSession);
        $analyses = $this->baseQuery($user)->where('year_session_id', $session->id)->get();

        $branches = [];

        foreach ($analyses->groupBy('branch') as $branchName => $branchAnalyses) {
            $branches[] = [
                'branch' => $branchName,
                'entries' => $branchAnalyses->count(),
                'average' => AcademicAnalysis::meanOfAverages($branchAnalyses),
                'success_rate' => AcademicAnalysis::aggregateSuccessRate($branchAnalyses),
            ];
        }

        usort($branches, fn (array $a, array $b): int => strcmp((string) $a['branch'], (string) $b['branch']));

        return Pdf::loadView('pdf.academic-analysis', [
            'mode' => 'schools',
            'title' => "All Schools Compilation - {$session->name}",
            'branches' => $branches,
            'overall_average' => AcademicAnalysis::meanOfAverages($analyses),
            'overall_success' => AcademicAnalysis::aggregateSuccessRate($analyses),
        ])
            ->setPaper('a4', 'landscape')
            ->stream("academic-analysis-schools-{$yearSession}.pdf");
    }

    /**
     * @return array<string, mixed>
     */
    private function sectionReport(int $yearSession, int $term, string $exam, string $branch, string $section): array
    {
        abort_unless(in_array($exam, AcademicAnalysis::EXAM_TYPES, true), 404);

        $user = $this->authorizeSection($branch, $section)['user'];

        $session = YearSession::findOrFail($yearSession);
        $termModel = Term::findOrFail($term);

        $analyses = $this->baseQuery($user)
            ->forTerm($session->id, $termModel->id, $exam)
            ->where('branch', $branch)
            ->where('section', $section)
            ->get();

        $classes = $analyses->map(fn (AcademicAnalysis $a): array => [
            'class' => $a->class?->name ?? 'N/A',
            'stream' => $a->stream?->name ?? 'N/A',
            'average' => $a->overallAverage(),
            'success_rate' => $a->averagesRow()['success_rate'],
        ])->values()->all();

        $subjects = AcademicAnalysis::compiledSubjectMeans($analyses);
        $subjectAverages = $this->meanStatRow($subjects);
        $subjectAverages['success_rate'] = AcademicAnalysis::aggregateSuccessRate($analyses);

        $streams = $analyses
            ->sortBy([
                fn (AcademicAnalysis $a, AcademicAnalysis $b): int => strcmp((string) $a->class?->name, (string) $b->class?->name)
                    ?: strcmp((string) $a->stream?->name, (string) $b->stream?->name),
            ])
            ->map(fn (AcademicAnalysis $a): array => [
                'label' => trim(($a->class?->name ?? 'Class').' '.($a->stream?->name ?? '')),
                'stats' => $a->subjectStats(),
                'averages' => $a->averagesRow(),
            ])
            ->values()
            ->all();

        return [
            'mode' => 'section',
            'title' => "Section Compilation - {$section}",
            'subtitle' => "{$session->name} - {$termModel->name} - {$exam} - {$branch}",
            'section_label' => "{$exam} - {$branch} - {$section}",
            'subjects' => $subjects,
            'subject_averages' => $subjectAverages,
            'classes' => $classes,
            'streams' => $streams,
            'section_average' => AcademicAnalysis::meanOfAverages($analyses),
            'section_success' => AcademicAnalysis::aggregateSuccessRate($analyses),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function yearReport(int $yearSession, string $branch, string $section): array
    {
        $user = auth()->user();
        abort_unless($user, 403);

        if ($user->isDeputy() && ! $user->hasUnrestrictedAccess() && ! $user->isHead()) {
            $branch = $user->branch ?? $branch;
        }

        if (! $user->hasUnrestrictedAccess() && ! $user->isHead() && ! $user->isDeputy()) {
            abort_unless(
                $user->hasRole('Coordinators') && $branch !== 'all' && $section !== 'all'
                && $user->sectionCoordinatorAssignments()->where('branch', $branch)->where('section', $section)->exists(),
                403
            );
        }

        $session = YearSession::findOrFail($yearSession);

        $analyses = $this->baseQuery($user)
            ->where('year_session_id', $session->id)
            ->when($branch !== 'all', fn ($query) => $query->where('branch', $branch))
            ->when($section !== 'all', fn ($query) => $query->where('section', $section))
            ->get();

        $sections = [];

        foreach ($analyses->groupBy('section') as $sectionName => $sectionAnalyses) {
            $subjects = AcademicAnalysis::compiledSubjectMeans($sectionAnalyses);
            $average = AcademicAnalysis::meanOfAverages($sectionAnalyses);
            $averages = array_merge(
                $this->meanStatRow($subjects),
                ['entries' => $sectionAnalyses->count()]
            );
            $averages['success_rate'] = AcademicAnalysis::aggregateSuccessRate($sectionAnalyses);

            $sections[] = [
                'label' => $sectionName,
                'subjects' => $subjects,
                'averages' => $averages,
                'year_average' => $average,
            ];
        }

        usort($sections, fn (array $a, array $b): int => strcmp((string) $a['label'], (string) $b['label']));

        $scope = trim(($branch !== 'all' ? $branch.' - ' : '').($section !== 'all' ? $section : 'All Sections'), ' -');

        return [
            'mode' => 'year',
            'title' => "Year Compilation - {$session->name}",
            'subtitle' => $scope !== '' ? $scope : 'All Sections',
            'sections' => $sections,
            'year_average' => AcademicAnalysis::meanOfAverages($analyses),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $subjects
     * @return array<string, float>
     */
    private function meanStatRow(array $subjects): array
    {
        $keys = ['average', 'exceeding', 'exceeding_pct', 'meeting', 'meeting_pct', 'approaching', 'approaching_pct', 'below', 'below_pct', 'total', 'success_rate'];
        $row = [];

        foreach ($keys as $key) {
            $row[$key] = count($subjects) > 0 ? round(collect($subjects)->avg($key), 2) : 0;
        }

        $row['success_rate'] = $row['total'] > 0
            ? round(($row['exceeding'] + $row['meeting']) / $row['total'] * 100, 2)
            : 0;

        return $row;
    }

    private function recordTitle(AcademicAnalysis $analysis): string
    {
        return 'Academic Analysis - '.($analysis->class?->name ?? '').' '.($analysis->stream?->name ?? '');
    }

    private function recordSubtitle(AcademicAnalysis $analysis): string
    {
        return trim(
            ($analysis->yearSession?->name ?? '').' - '.($analysis->term?->name ?? '').' - '.
            $analysis->exam_type.' - '.$analysis->branch.' - '.$analysis->section
        );
    }

    /**
     * @return array<int, string>
     */
    private function subjectHeaders(): array
    {
        return ['Learning Area', 'Average', 'EE', 'EE %', 'ME', 'ME %', 'AE', 'AE %', 'BE', 'BE %', 'Total', 'Success %'];
    }

    /**
     * @param  array<string, mixed>  $stat
     * @return array<int, mixed>
     */
    private function subjectExcelRow(array $stat): array
    {
        return [
            $stat['subject'], $stat['average'], $stat['exceeding'], $stat['exceeding_pct'],
            $stat['meeting'], $stat['meeting_pct'], $stat['approaching'], $stat['approaching_pct'],
            $stat['below'], $stat['below_pct'], $stat['total'], $stat['success_rate'],
        ];
    }

    /**
     * @param  array<string, mixed>  $averages
     * @return array<int, mixed>
     */
    private function averagesExcelRow(array $averages, string $label): array
    {
        return [
            $label, $averages['average'], $averages['exceeding'], $averages['exceeding_pct'],
            $averages['meeting'], $averages['meeting_pct'], $averages['approaching'], $averages['approaching_pct'],
            $averages['below'], $averages['below_pct'], $averages['total'], $averages['success_rate'],
        ];
    }

    /**
     * @param  array<int, array{heading: string, headers: array<int, string>, rows: array<int, array<int, mixed>>, subtotal: array<int, mixed>}>  $tables
     */
    private function streamExcel(string $title, array $tables, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($title, $tables): void {
            $writer = new XlsxWriter;
            $writer->openToBrowser($filename);

            $titleStyle = (new Style)
                ->setFontBold()->setFontName('Arial')->setFontSize(14)
                ->setCellAlignment(CellAlignment::CENTER);

            $headingStyle = (new Style)
                ->setFontBold()->setFontName('Arial')->setFontSize(11)
                ->setFontColor(Color::toARGB(Color::WHITE))
                ->setBackgroundColor(Color::toARGB('1F2937'));

            $headerStyle = (new Style)
                ->setFontBold()->setFontName('Arial')->setFontSize(10)
                ->setFontColor(Color::toARGB(Color::WHITE))
                ->setBackgroundColor(Color::toARGB(Color::DARK_BLUE))
                ->setCellAlignment(CellAlignment::CENTER);

            $dataStyle = (new Style)->setFontName('Arial')->setFontSize(10);
            $totalStyle = (new Style)->setFontBold()->setFontName('Arial')->setFontSize(10)
                ->setBackgroundColor(Color::toARGB('F3F4F6'));

            $writer->addRow(Row::fromValues(['Alameen Academy - '.$title], $titleStyle));
            $writer->addRow(Row::fromValues(['']));

            foreach ($tables as $table) {
                $writer->addRow(Row::fromValues([$table['heading']], $headingStyle));
                $writer->addRow(Row::fromValues($table['headers'], $headerStyle));

                foreach ($table['rows'] as $row) {
                    $writer->addRow(Row::fromValues($row, $dataStyle));
                }

                $writer->addRow(Row::fromValues($table['subtotal'], $totalStyle));
                $writer->addRow(Row::fromValues(['']));
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
