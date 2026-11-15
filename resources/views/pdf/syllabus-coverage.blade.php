<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Syllabus Coverage Report</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { font-family: DejaVu Sans, sans-serif; font-size: 8px; }
        h1 { font-size: 13px; margin: 0 0 4px; }
        .meta { color: #444; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #1f2937; color: #fff; padding: 4px 6px; text-align: left; font-size: 7px; text-transform: uppercase; }
        td { border-bottom: 1px solid #e5e7eb; padding: 4px 6px; }
        .right { text-align: right; }
        .center { text-align: center; }
        .success { color: #047857; font-weight: bold; }
        .danger { color: #b91c1c; font-weight: bold; }
        .footer { margin-top: 8px; color: #6b7280; font-size: 7px; }
    </style>
</head>
<body>
    <h1>Syllabus Coverage Report</h1>
    <div class="meta">
        @if ($filters['year'] ?? null)<span>Year: {{ $filters['year'] }}</span>@endif
        @if ($filters['term'] ?? null)<span> · Term: {{ $filters['term'] }}</span>@endif
        @if ($filters['branch'] ?? null)<span> · Campus: {{ $filters['branch'] }}</span>@endif
        @if ($filters['class'] ?? null)<span> · Grade: {{ $filters['class'] }}</span>@endif
        @if ($filters['subject'] ?? null)<span> · Subject: {{ $filters['subject'] }}</span>@endif
        @if ($filters['teacher'] ?? null)<span> · Teacher: {{ $filters['teacher'] }}</span>@endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Grade</th>
                <th>Subject</th>
                <th>Campus</th>
                <th>Teacher</th>
                <th class="right">Total</th>
                <th class="right">Completed</th>
                <th class="right">Pending</th>
                <th class="right">Overdue</th>
                <th class="right">Late</th>
                <th class="right">Coverage</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['grade'] }}</td>
                    <td>{{ $row['subject'] }}</td>
                    <td>{{ $row['branch'] ?? 'School-wide' }}</td>
                    <td>{{ $row['teacher'] }}</td>
                    <td class="right">{{ $row['total_lessons'] }}</td>
                    <td class="right">{{ $row['completed_lessons'] }}</td>
                    <td class="right">{{ $row['pending_lessons'] }}</td>
                    <td class="right {{ $row['overdue_lessons'] > 0 ? 'danger' : '' }}">{{ $row['overdue_lessons'] }}</td>
                    <td class="right">{{ $row['late_lessons'] }}</td>
                    <td class="right {{ $row['coverage_percent'] >= 80 ? 'success' : '' }}">{{ number_format($row['coverage_percent'], 2) }}%</td>
                    <td>{{ $row['status'] }}</td>
                </tr>
            @empty
                <tr><td colspan="11" class="center">No mappings match the selected filters.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Generated {{ now()->format('d/m/Y H:i') }} by {{ auth()->user()?->name ?? 'system' }} · {{ config('app.name') }}</div>
</body>
</html>
