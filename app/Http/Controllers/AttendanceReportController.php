<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceReportController extends Controller
{
    public function pdf(string $period): Response
    {
        $report = $this->report($period);

        return Pdf::loadView('pdf.attendance-report', $report)
            ->setPaper('a4', 'landscape')
            ->stream("attendance-{$period}-report.pdf");
    }

    public function excel(string $period): StreamedResponse
    {
        $report = $this->report($period);

        return $this->streamExcel($report, "attendance-{$period}-report.xlsx");
    }

    /**
     * Date range PDF report.
     */
    public function pdfByDate(Request $request): Response
    {
        $report = $this->reportByDate($request);

        return Pdf::loadView('pdf.attendance-report', $report)
            ->setPaper('a4', 'landscape')
            ->stream("attendance-{$report['period']}-report.pdf");
    }

    /**
     * Date range Excel report.
     */
    public function excelByDate(Request $request): StreamedResponse
    {
        $report = $this->reportByDate($request);

        return $this->streamExcel($report, "attendance-{$report['period']}-report.xlsx");
    }

    /**
     * Selected records PDF report.
     */
    public function pdfByIds(Request $request): Response
    {
        $report = $this->reportByIds($request);

        return Pdf::loadView('pdf.attendance-report', $report)
            ->setPaper('a4', 'landscape')
            ->stream('attendance-selected-report.pdf');
    }

    /**
     * Selected records Excel report.
     */
    public function excelByIds(Request $request): StreamedResponse
    {
        $report = $this->reportByIds($request);

        return $this->streamExcel($report, 'attendance-selected-report.xlsx');
    }

    /**
     * Build report grouped by period.
     */
    private function report(string $period): array
    {
        abort_unless(in_array($period, ['weekly', 'monthly', 'termly', 'yearly'], true), 404);

        $attendances = $this->queryAttendances();

        $rows = $this->buildRows($attendances, $period);

        return [
            'period' => $period,
            'period_label' => ucfirst($period),
            'rows' => $rows,
        ];
    }

    /**
     * Build report filtered by date range.
     */
    private function reportByDate(Request $request): array
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        abort_unless($dateFrom && $dateTo, 400);

        $attendances = $this->queryAttendances()
            ->whereDate('attendance_date', '>=', $dateFrom)
            ->whereDate('attendance_date', '<=', $dateTo);

        $rows = $this->buildRows($attendances, 'date_range');

        $label = \Carbon\Carbon::parse($dateFrom)->format('d M Y').' - '.\Carbon\Carbon::parse($dateTo)->format('d M Y');

        return [
            'period' => "date-range-{$dateFrom}-to-{$dateTo}",
            'period_label' => $label,
            'rows' => $rows,
        ];
    }

    /**
     * Build report for selected record IDs.
     */
    private function reportByIds(Request $request): array
    {
        $ids = $request->query('ids');
        abort_unless($ids, 400);

        $idArray = is_array($ids) ? $ids : explode(',', $ids);
        $idArray = array_map('intval', $idArray);
        $idArray = array_filter($idArray);

        abort_unless(count($idArray) > 0, 400);

        $attendances = $this->queryAttendances()
            ->whereIn('id', $idArray);

        $rows = $this->buildRows($attendances, 'selected');

        return [
            'period' => 'selected',
            'period_label' => 'Selected Records',
            'rows' => $rows,
        ];
    }

    /**
     * Base query with auth scoping and eager loads.
     */
    private function queryAttendances()
    {
        $user = auth()->user();
        abort_unless($user?->hasUnrestrictedAccess() || $user?->hasRole('Coordinators'), 403);

        return Attendance::query()
            ->accessibleTo($user)
            ->with(['yearSession', 'term', 'week', 'class', 'streams'])
            ->orderBy('attendance_date');
    }

    /**
     * Group attendance records and build report rows.
     *
     * @return array<int, array<string, int|float|string>>
     */
    private function buildRows($attendances, string $period): array
    {
        $collection = $attendances->get();

        return $collection
            ->groupBy(fn (Attendance $attendance): string => $this->groupKey($attendance, $period))
            ->map(function (Collection $group): array {
                $first = $group->first();
                $classTotal = (int) $group->sum('class_total');
                $present = (int) $group->sum('total_present');
                $absent = (int) $group->sum('total_absent');
                $percentagePresent = $classTotal > 0 ? round(($present / $classTotal) * 100, 2) : 0;

                return [
                    'period_label' => $this->periodLabel($first, $group),
                    'branch' => (string) $first->branch,
                    'section' => (string) $first->section,
                    'class_name' => $first->class?->name ?? 'N/A',
                    'stream_name' => $first->streams?->name ?? 'N/A',
                    'record_count' => $group->count(),
                    'class_total' => $classTotal,
                    'total_present' => $present,
                    'total_absent' => $absent,
                    'percentage_present' => $percentagePresent,
                    'percentage_absent' => round(100 - $percentagePresent, 2),
                ];
            })
            ->sortBy(['period_label', 'branch', 'section', 'class_name', 'stream_name'])
            ->values()
            ->all();
    }

    private function groupKey(Attendance $attendance, string $period): string
    {
        return match ($period) {
            'weekly' => $attendance->year_session_id.'|'.$attendance->term_id.'|'.$attendance->week_id.'|'.$attendance->branch.'|'.$attendance->section.'|'.$attendance->class_id.'|'.$attendance->stream_id,
            'monthly' => $attendance->attendance_date?->format('Y-m').'|'.$attendance->branch.'|'.$attendance->section.'|'.$attendance->class_id.'|'.$attendance->stream_id,
            'termly' => $attendance->year_session_id.'|'.$attendance->term_id.'|'.$attendance->branch.'|'.$attendance->section.'|'.$attendance->class_id.'|'.$attendance->stream_id,
            'yearly' => $attendance->year_session_id.'|'.$attendance->branch.'|'.$attendance->section.'|'.$attendance->class_id.'|'.$attendance->stream_id,
            default => $attendance->attendance_date?->format('Y-m-d').'|'.$attendance->branch.'|'.$attendance->section.'|'.$attendance->class_id.'|'.$attendance->stream_id,
        };
    }

    private function periodLabel(Attendance $attendance, Collection $group): string
    {
        $period = request()->route('period') ?? 'date_range';

        return match ($period) {
            'weekly' => trim(
                ($attendance->yearSession?->name ?? 'Unknown year').' - '.
                ($attendance->term?->name ?? 'Unknown term').' - '.
                ($attendance->week?->name ?? 'Unknown week')
            ),
            'monthly' => $attendance->attendance_date?->format('F Y') ?? 'Unknown month',
            'termly' => trim(($attendance->yearSession?->name ?? 'Unknown year').' - '.($attendance->term?->name ?? 'Unknown term')),
            'yearly' => $attendance->yearSession?->name ?? 'Unknown year',
            'selected' => $attendance->attendance_date?->format('d M Y').' - '.($attendance->class?->name ?? '').' '.($attendance->streams?->name ?? ''),
            default => $attendance->attendance_date?->format('d M Y') ?? 'Unknown date',
        };
    }

    /**
     * Shared Excel streaming logic.
     */
    private function streamExcel(array $report, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($report): void {
            $writer = new XlsxWriter;
            $writer->openToBrowser($filename);

            // --- Styles ---
            $titleStyle = (new Style)
                ->setFontBold()
                ->setFontName('Arial')
                ->setFontSize(16)
                ->setFontColor(Color::toARGB(Color::DARK_BLUE))
                ->setCellAlignment(CellAlignment::CENTER);

            $headerStyle = (new Style)
                ->setFontBold()
                ->setFontName('Arial')
                ->setFontSize(10)
                ->setFontColor(Color::toARGB(Color::WHITE))
                ->setBackgroundColor(Color::toARGB(Color::DARK_BLUE))
                ->setCellAlignment(CellAlignment::CENTER)
                ->setBorder(new Border(
                    new BorderPart(Border::BOTTOM, Color::toARGB(Color::DARK_BLUE), Border::WIDTH_THIN, Border::STYLE_SOLID),
                ));

            $dataStyle = (new Style)
                ->setFontName('Arial')
                ->setFontSize(10)
                ->setCellAlignment(CellAlignment::LEFT)
                ->setBorder(new Border(
                    new BorderPart(Border::BOTTOM, Color::toARGB('D1D5DB'), Border::WIDTH_THIN, Border::STYLE_SOLID),
                ));

            $dataStyleRight = (new Style)
                ->setFontName('Arial')
                ->setFontSize(10)
                ->setCellAlignment(CellAlignment::RIGHT)
                ->setBorder(new Border(
                    new BorderPart(Border::BOTTOM, Color::toARGB('D1D5DB'), Border::WIDTH_THIN, Border::STYLE_SOLID),
                ));

            $successStyle = (new Style)
                ->setFontName('Arial')
                ->setFontSize(10)
                ->setFontColor(Color::toARGB(Color::GREEN))
                ->setCellAlignment(CellAlignment::RIGHT)
                ->setBorder(new Border(
                    new BorderPart(Border::BOTTOM, Color::toARGB('D1D5DB'), Border::WIDTH_THIN, Border::STYLE_SOLID),
                ));

            $dangerStyle = (new Style)
                ->setFontName('Arial')
                ->setFontSize(10)
                ->setFontColor(Color::toARGB(Color::RED))
                ->setCellAlignment(CellAlignment::RIGHT)
                ->setBorder(new Border(
                    new BorderPart(Border::BOTTOM, Color::toARGB('D1D5DB'), Border::WIDTH_THIN, Border::STYLE_SOLID),
                ));

            $boldSuccessStyle = (new Style)
                ->setFontBold()
                ->setFontName('Arial')
                ->setFontSize(10)
                ->setFontColor(Color::toARGB(Color::GREEN))
                ->setCellAlignment(CellAlignment::RIGHT)
                ->setBorder(new Border(
                    new BorderPart(Border::BOTTOM, Color::toARGB('D1D5DB'), Border::WIDTH_THIN, Border::STYLE_SOLID),
                ));

            $boldDangerStyle = (new Style)
                ->setFontBold()
                ->setFontName('Arial')
                ->setFontSize(10)
                ->setFontColor(Color::toARGB(Color::RED))
                ->setCellAlignment(CellAlignment::RIGHT)
                ->setBorder(new Border(
                    new BorderPart(Border::BOTTOM, Color::toARGB('D1D5DB'), Border::WIDTH_THIN, Border::STYLE_SOLID),
                ));

            $totalLabelStyle = (new Style)
                ->setFontBold()
                ->setFontName('Arial')
                ->setFontSize(10)
                ->setBackgroundColor(Color::toARGB('F3F4F6'))
                ->setCellAlignment(CellAlignment::LEFT)
                ->setBorder(new Border(
                    new BorderPart(Border::TOP, Color::toARGB(Color::DARK_BLUE), Border::WIDTH_MEDIUM, Border::STYLE_SOLID),
                    new BorderPart(Border::BOTTOM, Color::toARGB(Color::DARK_BLUE), Border::WIDTH_MEDIUM, Border::STYLE_SOLID),
                ));

            $totalStyle = (new Style)
                ->setFontBold()
                ->setFontName('Arial')
                ->setFontSize(10)
                ->setBackgroundColor(Color::toARGB('F3F4F6'))
                ->setCellAlignment(CellAlignment::RIGHT)
                ->setBorder(new Border(
                    new BorderPart(Border::TOP, Color::toARGB(Color::DARK_BLUE), Border::WIDTH_MEDIUM, Border::STYLE_SOLID),
                    new BorderPart(Border::BOTTOM, Color::toARGB(Color::DARK_BLUE), Border::WIDTH_MEDIUM, Border::STYLE_SOLID),
                ));

            $totalSuccessStyle = (new Style)
                ->setFontBold()
                ->setFontName('Arial')
                ->setFontSize(10)
                ->setFontColor(Color::toARGB(Color::GREEN))
                ->setBackgroundColor(Color::toARGB('F3F4F6'))
                ->setCellAlignment(CellAlignment::RIGHT)
                ->setBorder(new Border(
                    new BorderPart(Border::TOP, Color::toARGB(Color::DARK_BLUE), Border::WIDTH_MEDIUM, Border::STYLE_SOLID),
                    new BorderPart(Border::BOTTOM, Color::toARGB(Color::DARK_BLUE), Border::WIDTH_MEDIUM, Border::STYLE_SOLID),
                ));

            $totalDangerStyle = (new Style)
                ->setFontBold()
                ->setFontName('Arial')
                ->setFontSize(10)
                ->setFontColor(Color::toARGB(Color::RED))
                ->setBackgroundColor(Color::toARGB('F3F4F6'))
                ->setCellAlignment(CellAlignment::RIGHT)
                ->setBorder(new Border(
                    new BorderPart(Border::TOP, Color::toARGB(Color::DARK_BLUE), Border::WIDTH_MEDIUM, Border::STYLE_SOLID),
                    new BorderPart(Border::BOTTOM, Color::toARGB(Color::DARK_BLUE), Border::WIDTH_MEDIUM, Border::STYLE_SOLID),
                ));

            // --- Title Row ---
            $writer->addRow(Row::fromValues([
                'Alameen Academy - '.$report['period_label'].' Attendance Report',
            ], $titleStyle));

            $writer->addRow(Row::fromValues(['']));

            // --- Header Row ---
            $headers = ['Period', 'Branch', 'Section', 'Class', 'Stream', 'Records', 'Total Learners', 'Total Present', 'Total Absent', '% Present', '% Absent'];
            $writer->addRow(Row::fromValues($headers, $headerStyle));

            // --- Data Rows ---
            $grandTotalLearners = 0;
            $grandTotalPresent = 0;
            $grandTotalAbsent = 0;

            foreach ($report['rows'] as $row) {
                $grandTotalLearners += $row['class_total'];
                $grandTotalPresent += $row['total_present'];
                $grandTotalAbsent += $row['total_absent'];

                $writer->addRow(Row::fromValuesWithStyles(
                    [
                        $row['period_label'],
                        $row['branch'],
                        $row['section'],
                        $row['class_name'],
                        $row['stream_name'],
                        $row['record_count'],
                        $row['class_total'],
                        $row['total_present'],
                        $row['total_absent'],
                        $row['percentage_present'],
                        $row['percentage_absent'],
                    ],
                    $dataStyle,
                    [
                        5 => $dataStyleRight,
                        6 => $dataStyleRight,
                        7 => $dataStyleRight,
                        8 => $successStyle,
                        9 => $dangerStyle,
                        10 => $boldSuccessStyle,
                        11 => $boldDangerStyle,
                    ],
                ));
            }

            // --- Grand Total Row ---
            if (count($report['rows']) > 1) {
                $grandPercentagePresent = $grandTotalLearners > 0
                    ? round(($grandTotalPresent / $grandTotalLearners) * 100, 2)
                    : 0;

                $writer->addRow(Row::fromValuesWithStyles(
                    [
                        'GRAND TOTAL', '', '', '', '',
                        count($report['rows']),
                        $grandTotalLearners, $grandTotalPresent, $grandTotalAbsent,
                        $grandPercentagePresent, round(100 - $grandPercentagePresent, 2),
                    ],
                    $totalLabelStyle,
                    [
                        5 => $totalStyle, 6 => $totalStyle, 7 => $totalStyle,
                        8 => $totalSuccessStyle, 9 => $totalDangerStyle,
                        10 => $totalSuccessStyle, 11 => $totalDangerStyle,
                    ],
                ));
            }

            // --- Column Widths ---
            $writer->getCurrentSheet()->setColumnWidthForRange(28, 1, 1);
            $writer->getCurrentSheet()->setColumnWidthForRange(16, 2, 2);
            $writer->getCurrentSheet()->setColumnWidthForRange(18, 3, 3);
            $writer->getCurrentSheet()->setColumnWidthForRange(14, 4, 4);
            $writer->getCurrentSheet()->setColumnWidthForRange(14, 5, 5);
            $writer->getCurrentSheet()->setColumnWidthForRange(10, 6, 6);
            $writer->getCurrentSheet()->setColumnWidthForRange(14, 7, 7);
            $writer->getCurrentSheet()->setColumnWidthForRange(14, 8, 8);
            $writer->getCurrentSheet()->setColumnWidthForRange(14, 9, 9);
            $writer->getCurrentSheet()->setColumnWidthForRange(12, 10, 10);
            $writer->getCurrentSheet()->setColumnWidthForRange(12, 11, 11);

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
