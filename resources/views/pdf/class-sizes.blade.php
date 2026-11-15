<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Alameen Academy - Class Sizes Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        .header { text-align: center; margin-bottom: 20px; }
        .logo { width: 100px; height: auto; margin-bottom: 8px; }
        h1 { text-align: center; margin: 0 0 4px 0; color: #1f2937; font-size: 16px; }
        .subtitle { text-align: center; color: #374151; font-size: 12px; margin: 0 0 4px 0; }
        h3.section-heading { color: #fff; background: #1f2937; font-size: 11px; margin: 18px 0 0 0; padding: 6px 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #9ca3af; padding: 5px 6px; }
        th { background: #1f2937; color: #fff; font-weight: bold; font-size: 9px; }
        td { font-size: 9px; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .total-row td { border-top: 2px solid #1f2937; font-weight: bold; background: #f3f4f6; }
        tbody tr:nth-child(even) { background: #f9fafb; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('images/alameen-academy-logo.png') }}" alt="Alameen Academy Logo" class="logo">
        <h1>Alameen Academy - Class Sizes Report</h1>
        <p class="subtitle">Academic Year: {{ $session->name }}</p>
        <p class="subtitle">Term: {{ $term->name }}</p>
    </div>
    @forelse ($sections as $section)
        <h3 class="section-heading">{{ $section['label'] }}</h3>
        <table>
            <thead>
                <tr>
                    <th>Branch</th>
                    <th>Class</th>
                    <th>Stream</th>
                    <th class="text-right">Boys</th>
                    <th class="text-right">Girls</th>
                    <th class="text-right">Total Learners</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($section['rows'] as $row)
                    <tr>
                        <td>{{ $row->branch }}</td>
                        <td>{{ $row->class?->name ?? 'N/A' }}</td>
                        <td>{{ $row->stream?->name ?? 'N/A' }}</td>
                        <td class="text-right">{{ $row->total_boys }}</td>
                        <td class="text-right">{{ $row->total_girls }}</td>
                        <td class="text-right font-bold">{{ $row->class_total }}</td>
                    </tr>
                @endforeach
            </tbody>
            @if (count($section['rows']) > 1)
                <tfoot>
                    <tr class="total-row">
                        <td colspan="3" class="font-bold">Section Subtotal</td>
                        <td class="text-right font-bold">{{ $section['boys'] }}</td>
                        <td class="text-right font-bold">{{ $section['girls'] }}</td>
                        <td class="text-right font-bold">{{ $section['total'] }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    @empty
        <table>
            <tbody>
                <tr><td colspan="6" style="text-align: center; color: #6b7280; font-style: italic;">No class size records found for this year and term.</td></tr>
            </tbody>
        </table>
    @endforelse
    @if ($rowCount > 1)
        <table style="margin-top: 12px;">
            <tfoot>
                <tr class="total-row">
                    <td colspan="3" class="font-bold">GRAND TOTAL</td>
                    <td class="text-right font-bold">{{ $grandBoys }}</td>
                    <td class="text-right font-bold">{{ $grandGirls }}</td>
                    <td class="text-right font-bold">{{ $grandTotal }}</td>
                </tr>
            </tfoot>
        </table>
    @endif
</body>
</html>
