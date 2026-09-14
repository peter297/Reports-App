<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use OpenSpout\Common\Entity\Row;
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
            $writer = new XlsxWriter();
            $writer->openToBrowser("attendance-{$report['period']}-report.xlsx");
            $writer->addRow(Row::fromValues([
                ucfirst($report['period']) . ' Period',
                'Branch',
                'Section',
                'Attendance Records',
                'Total Learners',
                'Total Present',
                'Total Absent',
                'Percentage Present',
                'Percentage Absent',
            ]));

            foreach ($report['rows'] as $row) {
                $writer->addRow(Row::fromValues([
                    $row['period_label'],
                    $row['branch'],
                    $row['section'],
                    $row['record_count'],
                    $row['class_total'],
                    $row['total_present'],
                    $row['total_absent'],
                    $row['percentage_present'],
                    $row['percentage_absent'],
                ]));
            }

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
            'monthly' => $attendance->attendance_date?->format('Y-m') . '|' . $attendance->branch . '|' . $attendance->section,
            'termly' => $attendance->year_session_id . '|' . $attendance->term_id . '|' . $attendance->branch . '|' . $attendance->section,
            'yearly' => $attendance->year_session_id . '|' . $attendance->branch . '|' . $attendance->section,
        };
    }

    private function periodLabel(Attendance $attendance, Collection $group): string
    {
        $period = request()->route('period');

        return match ($period) {
            'monthly' => $attendance->attendance_date?->format('F Y') ?? 'Unknown month',
            'termly' => trim(($attendance->yearSession?->name ?? 'Unknown year') . ' - ' . ($attendance->term?->name ?? 'Unknown term')),
            'yearly' => $attendance->yearSession?->name ?? 'Unknown year',
            default => $group->first()->attendance_date?->format('F Y') ?? 'Unknown period',
        };
    }
}
