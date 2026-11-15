<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Alameen Academy - Enrollment Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        .header { text-align: center; margin-bottom: 20px; }
        .logo { width: 100px; height: auto; margin-bottom: 8px; }
        h1 { text-align: center; margin: 0 0 4px 0; color: #1f2937; font-size: 16px; }
        .subtitle { text-align: center; color: #6b7280; font-size: 10px; margin: 0 0 16px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #9ca3af; padding: 6px 8px; vertical-align: top; }
        th { background: #e5e7eb; font-weight: bold; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .bg-light { background: #f3f4f6; }
        .total-row td { border-top: 2px solid #6b7280; font-weight: bold; background: #f9fafb; }
        .inner-table { width: 100%; border-collapse: collapse; margin: 0; }
        .inner-table th, .inner-table td { border: 1px solid #d1d5db; padding: 4px 6px; font-size: 9px; }
        .inner-table th { background: #f3f4f6; }
        .success { color: #059669; }
        .danger { color: #dc2626; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('images/alameen-academy-logo.png') }}" alt="Alameen Academy Logo" class="logo">
        <h1>Alameen Academy - Enrollment Report</h1>
    </div>
    <table>
        <thead>
            <tr>
                <th>Branch</th>
                <th>Academic Year</th>
                <th>Term</th>
                <th class="text-right">Total Learners</th>
                <th>Learners per Class</th>
                <th class="text-right">Admitted</th>
                <th class="text-right">Left</th>
                <th>Comment</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($enrollments as $enrollment)
                @php
                    $classes = $enrollment->class_breakdown ?? [];
                    $grandTotalBoys = collect($classes)->sum('boys');
                    $grandTotalGirls = collect($classes)->sum('girls');
                    $grandTotal = collect($classes)->sum('total');
                    $grandTotalAdmitted = collect($classes)->sum('new_admissions');
                    $grandTotalLeft = collect($classes)->sum('departures');
                @endphp
                <tr>
                    <td>{{ $enrollment->branch }}</td>
                    <td>{{ $enrollment->yearSession?->name }}</td>
                    <td>{{ $enrollment->term?->name }}</td>
                    <td class="text-right font-bold">{{ $enrollment->total_learners }}</td>
                    <td>
                        @if ($classes)
                            <table class="inner-table">
                                <thead>
                                    <tr>
                                        <th>Class</th>
                                        <th class="text-right">Boys</th>
                                        <th class="text-right">Girls</th>
                                        <th class="text-right">Total</th>
                                        <th class="text-right">Admitted</th>
                                        <th class="text-right">Left</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($classes as $class)
                                        <tr>
                                            <td>{{ $class['class_name'] ?? $class['class_id'] ?? 'Class' }}</td>
                                            <td class="text-right">{{ $class['boys'] ?? 0 }}</td>
                                            <td class="text-right">{{ $class['girls'] ?? 0 }}</td>
                                            <td class="text-right font-bold">{{ $class['total'] ?? ((int) ($class['boys'] ?? 0) + (int) ($class['girls'] ?? 0)) }}</td>
                                            <td class="text-right success">{{ $class['new_admissions'] ?? 0 }}</td>
                                            <td class="text-right danger">{{ $class['departures'] ?? 0 }}</td>
                                        </tr>
                                    @endforeach
                                    @if (count($classes) > 1)
                                        <tr class="total-row">
                                            <td>Total</td>
                                            <td class="text-right">{{ $grandTotalBoys }}</td>
                                            <td class="text-right">{{ $grandTotalGirls }}</td>
                                            <td class="text-right">{{ $grandTotal }}</td>
                                            <td class="text-right success">{{ $grandTotalAdmitted }}</td>
                                            <td class="text-right danger">{{ $grandTotalLeft }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        @else
                            <em>No class data</em>
                        @endif
                    </td>
                    <td class="text-right success">{{ $enrollment->new_admissions }}</td>
                    <td class="text-right danger">{{ $enrollment->departures }}</td>
                    <td>{{ $enrollment->comment }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3 style="color: #1f2937; font-size: 12px; margin: 20px 0 8px 0; border-bottom: 2px solid #1f2937; padding-bottom: 4px;">Totals by Section</h3>
    <table>
        <thead>
            <tr>
                <th>Section</th>
                <th class="text-right">Boys</th>
                <th class="text-right">Girls</th>
                <th class="text-right">Total Learners</th>
                <th class="text-right">Admitted</th>
                <th class="text-right">Left</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sectionTotals['sections'] as $sectionName => $figures)
                <tr>
                    <td class="font-bold">{{ $sectionName }}</td>
                    <td class="text-right">{{ $figures['boys'] }}</td>
                    <td class="text-right">{{ $figures['girls'] }}</td>
                    <td class="text-right font-bold">{{ $figures['total'] }}</td>
                    <td class="text-right success">{{ $figures['admitted'] }}</td>
                    <td class="text-right danger">{{ $figures['left'] }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align: center; color: #6b7280; font-style: italic;">No section data.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td class="font-bold">Overall Total</td>
                <td class="text-right font-bold">{{ $sectionTotals['overall']['boys'] }}</td>
                <td class="text-right font-bold">{{ $sectionTotals['overall']['girls'] }}</td>
                <td class="text-right font-bold">{{ $sectionTotals['overall']['total'] }}</td>
                <td class="text-right success font-bold">{{ $sectionTotals['overall']['admitted'] }}</td>
                <td class="text-right danger font-bold">{{ $sectionTotals['overall']['left'] }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
