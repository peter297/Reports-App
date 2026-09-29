<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Alameen Academy - {{ $mode === 'record' ? 'Academic Analysis' : $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        .header { text-align: center; margin-bottom: 20px; }
        .logo { width: 100px; height: auto; margin-bottom: 8px; }
        h1 { text-align: center; margin: 0 0 4px 0; color: #1f2937; font-size: 16px; }
        .subtitle { text-align: center; color: #374151; font-size: 11px; margin: 0 0 4px 0; }
        h3.band { color: #fff; background: #1f2937; font-size: 11px; margin: 18px 0 0 0; padding: 6px 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #9ca3af; padding: 5px 6px; }
        th { background: #1f2937; color: #fff; font-weight: bold; font-size: 9px; }
        td { font-size: 9px; }
        .band-head th { background: #1f2937; border-color: #1f2937; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .success { color: #059669; }
        .total-row td { border-top: 2px solid #1f2937; font-weight: bold; background: #f3f4f6; }
        tbody tr:nth-child(even) { background: #f9fafb; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('images/alameen-academy-logo.png') }}" alt="Alameen Academy Logo" class="logo">
        @if ($mode === 'record')
            <h1>Alameen Academy - Academic Analysis</h1>
            <p class="subtitle">{{ $analysis->yearSession?->name }} - {{ $analysis->term?->name }} - {{ $analysis->exam_type }}</p>
            <p class="subtitle">{{ $analysis->branch }} - {{ $analysis->section }} - {{ $analysis->class?->name }} {{ $analysis->stream?->name }}</p>
        @else
            <h1>Alameen Academy - {{ $title }}</h1>
            @if (! empty($subtitle ?? null))
                <p class="subtitle">{{ $subtitle }}</p>
            @endif
        @endif
    </div>

    @if ($mode === 'record')
        @include('pdf.academic-subject-table', [
            'rows' => $stats,
            'avgRow' => $averages,
            'avgLabel' => 'AVERAGES',
            'headLabel' => $analysis->exam_type,
        ])
    @endif

    @if ($mode === 'section')
        @foreach ($streams as $stream)
            @include('pdf.academic-subject-table', [
                'rows' => $stream['stats'],
                'avgRow' => $stream['averages'],
                'avgLabel' => 'AVERAGES',
                'headLabel' => $stream['label'],
            ])
        @endforeach
        <h3 class="band">SECTION SUMMARY - {{ $section_label }}</h3>
        @include('pdf.academic-subject-table', [
            'rows' => $subjects,
            'avgRow' => $subject_averages,
            'avgLabel' => 'SECTION AVERAGE',
            'headLabel' => $section_label,
        ])
        <h3 class="band">CLASSES - AVERAGE PERFORMANCE</h3>
        <table class="my-2">
            <thead>
                <tr>
                    <th>Class</th>
                    <th>Stream</th>
                    <th class="text-right">Average</th>
                    <th class="text-right">Success Rate %</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($classes as $class)
                    <tr>
                        <td>{{ $class['class'] }}</td>
                        <td>{{ $class['stream'] }}</td>
                        <td class="text-right">{{ number_format($class['average'], 2) }}</td>
                        <td class="text-right success font-bold">{{ number_format($class['success_rate'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="text-align: center; color: #6b7280; font-style: italic;">No class analyses found.</td></tr>
                @endforelse
            </tbody>
            @if (count($classes) > 0)
                <tfoot>
                    <tr class="total-row">
                        <td colspan="2" class="font-bold">SECTION AVERAGE</td>
                        <td class="text-right font-bold">{{ number_format($section_average, 2) }}</td>
                        <td class="text-right success font-bold">{{ number_format($section_success, 2) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    @endif

    @if ($mode === 'year')
        @foreach ($sections as $sectionData)
            <h3 class="band">{{ strtoupper($sectionData['label']) }} - YEAR AVERAGE: {{ number_format($sectionData['year_average'], 2) }}</h3>
            <table>
                <thead>
                    <tr>
                        <th>LEARNING AREA (L.A.)</th>
                        <th class="text-right">L.A. AVERAGE</th>
                        <th class="text-right">EE %</th>
                        <th class="text-right">ME %</th>
                        <th class="text-right">AE %</th>
                        <th class="text-right">BE %</th>
                        <th class="text-right">Success %</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sectionData['subjects'] as $row)
                        <tr>
                            <td>{{ $row['subject'] }}</td>
                            <td class="text-right">{{ number_format($row['average'], 2) }}</td>
                            <td class="text-right">{{ number_format($row['exceeding_pct'], 2) }}</td>
                            <td class="text-right">{{ number_format($row['meeting_pct'], 2) }}</td>
                            <td class="text-right">{{ number_format($row['approaching_pct'], 2) }}</td>
                            <td class="text-right">{{ number_format($row['below_pct'], 2) }}</td>
                            <td class="text-right success font-bold">{{ number_format($row['success_rate'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" style="text-align: center; color: #6b7280; font-style: italic;">No subject data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endforeach
        @if (count($sections) > 0)
            <table style="margin-top: 12px;">
                <tfoot>
                    <tr class="total-row">
                        <td class="font-bold">YEAR AVERAGE</td>
                        <td class="text-right font-bold">{{ number_format($year_average, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        @endif
    @endif

    @if ($mode === 'schools')
        <table>
            <thead>
                <tr>
                    <th>Branch</th>
                    <th class="text-right">Analyses</th>
                    <th class="text-right">Average Performance</th>
                    <th class="text-right">Success Rate %</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($branches as $branchRow)
                    <tr>
                        <td class="font-bold">{{ $branchRow['branch'] }}</td>
                        <td class="text-right">{{ $branchRow['entries'] }}</td>
                        <td class="text-right">{{ number_format($branchRow['average'], 2) }}</td>
                        <td class="text-right success font-bold">{{ number_format($branchRow['success_rate'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="text-align: center; color: #6b7280; font-style: italic;">No analyses found.</td></tr>
                @endforelse
            </tbody>
            @if (count($branches) > 0)
                <tfoot>
                    <tr class="total-row">
                        <td class="font-bold">ALL SCHOOLS</td>
                        <td class="text-right font-bold">{{ array_sum(array_column($branches, 'entries')) }}</td>
                        <td class="text-right font-bold">{{ number_format($overall_average, 2) }}</td>
                        <td class="text-right success font-bold">{{ number_format($overall_success, 2) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    @endif
</body>
</html>
