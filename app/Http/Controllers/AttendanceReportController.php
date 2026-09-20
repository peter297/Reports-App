<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
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

        return response()->streamDownload(function () use ($report): void {
            $writer = new XlsxWriter;
            $writer->openToBrowser("attendance-{$report['period']}-report.xlsx");

            // --- Styles ---
            $titleStyle = (new Style)
                ->setFontBold()
                ->setFontName('Arial')
                ->setFontSize(16)
                ->setFontColor(Color::toARGB(Color::DARK_BLUE))
                ->setCellAlignment(CellAlignment::CENTER);

            $subtitleStyle = (new Style)
                ->setFontName('Arial')
                ->setFontSize(11)
                ->setFontColor(Color::toARGB('6B7280'))
                ->setCellAlignment(CellAlignment::CENTER);

            $headerStyle = (new Style)
                ->setFontBold()
                ->setFontName('Arial')
                ->setFontSize(11)
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
                'Alameen Academy - '.ucfirst($report['period']).' Attendance Report',
            ], $titleStyle));

            // Empty spacer row
            $writer->addRow(Row::fromValues(['']));

            // --- Header Row ---
            $headers = ['Period', 'Branch', 'Section', 'Records', 'Total Learners', 'Total Present', 'Total Absent', '% Present', '% Absent'];
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
                        $row['record_count'],
                        $row['class_total'],
                        $row['total_present'],
                        $row['total_absent'],
                        $row['percentage_present'],
                        $row['percentage_absent'],
                    ],
                    $dataStyle,
                    [
                        3 => $dataStyleRight,
                        4 => $dataStyleRight,
                        5 => $successStyle,
                        6 => $dangerStyle,
                        7 => $boldSuccessStyle,
                        8 => $boldDangerStyle,
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
                        'GRAND TOTAL',
                        '',
                        '',
                        count($report['rows']),
                        $grandTotalLearners,
                        $grandTotalPresent,
                        $grandTotalAbsent,
                        $grandPercentagePresent,
                        round(100 - $grandPercentagePresent, 2),
                    ],
                    $totalLabelStyle,
                    [
                        3 => $totalStyle,
                        4 => $totalStyle,
                        5 => $totalSuccessStyle,
                        6 => $totalDangerStyle,
                        7 => $totalSuccessStyle,
                        8 => $totalDangerStyle,
                    ],
                ));
            }

            // --- Set Column Widths ---
            $writer->getCurrentSheet()->setColumnWidthForRange(28, 1, 1);  // Period
            $writer->getCurrentSheet()->setColumnWidthForRange(16, 2, 2);  // Branch
            $writer->getCurrentSheet()->setColumnWidthForRange(20, 3, 3);  // Section
            $writer->getCurrentSheet()->setColumnWidthForRange(12, 4, 4);  // Records
            $writer->getCurrentSheet()->setColumnWidthForRange(14, 5, 5);  // Total Learners
            $writer->getCurrentSheet()->setColumnWidthForRange(14, 6, 6);  // Total Present
            $writer->getCurrentSheet()->setColumnWidthForRange(14, 7, 7);  // Total Absent
            $writer->getCurrentSheet()->setColumnWidthForRange(12, 8, 8);  // % Present
            $writer->getCurrentSheet()->setColumnWidthForRange(12, 9, 9);  // % Absent

            $writer->close();
        }, "attendance-{$period}-report.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return array{period: string, period_label: string, rows: array<int, array<string, int|float|string>>}
     */
    private function report(string $period): array
    {
        abort_unless(in_array($period, ['monthly', 'termly', 'yearly'], true), 404);

        $user = auth()->user();
        abort_unless($user?->hasUnrestrictedAccess() || $user?->hasRole('Coordinators'), 403);

        $attendances = Attendance::query()
            ->accessibleTo($user)
            ->with(['yearSession', 'term'])
            ->orderBy('attendance_date')
            ->get();

        $rows = $attendances
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
                    'record_count' => $group->count(),
                    'class_total' => $classTotal,
                    'total_present' => $present,
                    'total_absent' => $absent,
                    'percentage_present' => $percentagePresent,
                    'percentage_absent' => round(100 - $percentagePresent, 2),
                ];
            })
            ->sortBy(['period_label', 'branch', 'section'])
            ->values()
            ->all();

        return [
            'period' => $period,
            'period_label' => ucfirst($period),
            'rows' => $rows,
        ];
    }

    private function groupKey(Attendance $attendance, string $period): string
    {
        return match ($period) {
            'monthly' => $attendance->attendance_date?->format('Y-m').'|'.$attendance->branch.'|'.$attendance->section,
            'termly' => $attendance->year_session_id.'|'.$attendance->term_id.'|'.$attendance->branch.'|'.$attendance->section,
            'yearly' => $attendance->year_session_id.'|'.$attendance->branch.'|'.$attendance->section,
        };
    }

    private function periodLabel(Attendance $attendance, Collection $group): string
    {
        $period = request()->route('period');

        return match ($period) {
            'monthly' => $attendance->attendance_date?->format('F Y') ?? 'Unknown month',
            'termly' => trim(($attendance->yearSession?->name ?? 'Unknown year').' - '.($attendance->term?->name ?? 'Unknown term')),
            'yearly' => $attendance->yearSession?->name ?? 'Unknown year',
            default => $group->first()->attendance_date?->format('F Y') ?? 'Unknown period',
        };
    }
}
