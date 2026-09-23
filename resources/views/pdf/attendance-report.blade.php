<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Alameen Academy - {{ $period_label }} Attendance Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        .header { text-align: center; margin-bottom: 20px; }
        .logo { width: 100px; height: auto; margin-bottom: 8px; }
        h1 { text-align: center; margin: 0 0 4px 0; color: #1f2937; font-size: 16px; }
        .subtitle { text-align: center; color: #6b7280; font-size: 11px; margin: 0 0 16px 0; }
        h3.week-heading { color: #fff; background: #1f2937; font-size: 11px; margin: 18px 0 0 0; padding: 6px 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #9ca3af; padding: 5px 6px; }
        th { background: #1f2937; color: #fff; font-weight: bold; font-size: 9px; }
        td { font-size: 9px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .success { color: #059669; }
        .danger { color: #dc2626; }
        .total-row td { border-top: 2px solid #1f2937; font-weight: bold; background: #f3f4f6; }
        tbody tr:nth-child(even) { background: #f9fafb; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('images/alameen-academy-logo.png') }}" alt="Alameen Academy Logo" class="logo">
        <h1>Alameen Academy - {{ $period_label }} Attendance Report</h1>
    </div>
    @php
        $grandTotalLearners = 0;
        $grandTotalPresent = 0;
        $grandTotalAbsent = 0;

        foreach ($rows as $grandRow) {
            $grandTotalLearners += $grandRow['class_total'];
            $grandTotalPresent += $grandRow['total_present'];
            $grandTotalAbsent += $grandRow['total_absent'];
        }

        $grandPercentagePresent = $grandTotalLearners > 0 ? round(($grandTotalPresent / $grandTotalLearners) * 100, 2) : 0;
    @endphp
    @if (! empty($sections))
        @forelse ($sections as $section)
            <h3 class="week-heading">{{ $section['label'] }}</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Branch</th>
                        <th>Section</th>
                        <th>Class</th>
                        <th>Stream</th>
                        <th class="text-right">Records</th>
                        <th class="text-right">Total Learners</th>
                        <th class="text-right">Total Present</th>
                        <th class="text-right">Total Absent</th>
                        <th class="text-right">% Present</th>
                        <th class="text-right">% Absent</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($section['rows'] as $row)
                        <tr>
                            <td>{{ $row['period_label'] }}</td>
                            <td>{{ $row['branch'] }}</td>
                            <td>{{ $row['section'] }}</td>
                            <td>{{ $row['class_name'] }}</td>
                            <td>{{ $row['stream_name'] }}</td>
                            <td class="text-right">{{ $row['record_count'] }}</td>
                            <td class="text-right font-bold">{{ $row['class_total'] }}</td>
                            <td class="text-right success">{{ $row['total_present'] }}</td>
                            <td class="text-right danger">{{ $row['total_absent'] }}</td>
                            <td class="text-right success font-bold">{{ number_format($row['percentage_present'], 2) }}%</td>
                            <td class="text-right danger font-bold">{{ number_format($row['percentage_absent'], 2) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
                @if (count($section['rows']) > 1)
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="5" class="font-bold">{{ $section['subtotal_label'] }}</td>
                            <td class="text-right font-bold">{{ count($section['rows']) }}</td>
                            <td class="text-right font-bold">{{ $section['class_total'] }}</td>
                            <td class="text-right success font-bold">{{ $section['total_present'] }}</td>
                            <td class="text-right danger font-bold">{{ $section['total_absent'] }}</td>
                            <td class="text-right success font-bold">{{ number_format($section['percentage_present'], 2) }}%</td>
                            <td class="text-right danger font-bold">{{ number_format($section['percentage_absent'], 2) }}%</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        @empty
            <table>
                <tbody>
                    <tr><td colspan="11" style="text-align: center; color: #6b7280; font-style: italic;">No attendance records found.</td></tr>
                </tbody>
            </table>
        @endforelse
    @else
        <table>
            <thead>
                <tr>
                    <th>Period</th>
                    <th>Branch</th>
                    <th>Section</th>
                    <th>Class</th>
                    <th>Stream</th>
                    <th class="text-right">Records</th>
                    <th class="text-right">Total Learners</th>
                    <th class="text-right">Total Present</th>
                    <th class="text-right">Total Absent</th>
                    <th class="text-right">% Present</th>
                    <th class="text-right">% Absent</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $row['period_label'] }}</td>
                        <td>{{ $row['branch'] }}</td>
                        <td>{{ $row['section'] }}</td>
                        <td>{{ $row['class_name'] }}</td>
                        <td>{{ $row['stream_name'] }}</td>
                        <td class="text-right">{{ $row['record_count'] }}</td>
                        <td class="text-right font-bold">{{ $row['class_total'] }}</td>
                        <td class="text-right success">{{ $row['total_present'] }}</td>
                        <td class="text-right danger">{{ $row['total_absent'] }}</td>
                        <td class="text-right success font-bold">{{ number_format($row['percentage_present'], 2) }}%</td>
                        <td class="text-right danger font-bold">{{ number_format($row['percentage_absent'], 2) }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="11" style="text-align: center; color: #6b7280; font-style: italic;">No attendance records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif
    @if (count($rows) > 1)
        <table style="margin-top: 12px;">
            <tfoot>
                <tr class="total-row">
                    <td colspan="5" class="font-bold">GRAND TOTAL</td>
                    <td class="text-right font-bold">{{ count($rows) }}</td>
                    <td class="text-right font-bold">{{ $grandTotalLearners }}</td>
                    <td class="text-right success font-bold">{{ $grandTotalPresent }}</td>
                    <td class="text-right danger font-bold">{{ $grandTotalAbsent }}</td>
                    <td class="text-right success font-bold">{{ number_format($grandPercentagePresent, 2) }}%</td>
                    <td class="text-right danger font-bold">{{ number_format(100 - $grandPercentagePresent, 2) }}%</td>
                </tr>
            </tfoot>
        </table>
    @endif
</body>
</html>
