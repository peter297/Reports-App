<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Syllabus Mapping — {{ $mapping->subject->name }} ({{ $mapping->grade_stream }})</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { font-family: DejaVu Sans, sans-serif; font-size: 8px; }
        h1 { font-size: 13px; margin: 0 0 2px; }
        .meta { color: #444; margin-bottom: 8px; }
        .meta span { margin-right: 12px; }
        .summary { margin-bottom: 8px; }
        .summary span { display: inline-block; background: #f0f0f0; border: 1px solid #ddd; border-radius: 4px; padding: 3px 8px; margin-right: 6px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #1f2937; color: #fff; padding: 4px 3px; text-align: left; font-size: 7px; text-transform: uppercase; }
        td { border-bottom: 1px solid #e5e7eb; padding: 3px; vertical-align: top; }
        tr.strand td { background: #374151; color: #fff; font-weight: bold; text-transform: uppercase; padding: 3px 6px; }
        tr.substrand td { background: #e5e7eb; font-weight: bold; font-size: 7px; text-transform: uppercase; }
        tr.completed td { background: #f0fdf4; }
        .center { text-align: center; }
        .right { text-align: right; }
        .danger { color: #b91c1c; font-weight: bold; }
        .success { color: #047857; font-weight: bold; }
        .muted { color: #6b7280; }
        .footer { margin-top: 8px; color: #6b7280; font-size: 7px; }
    </style>
</head>
<body>
    <h1>Syllabus Mapping Outline — {{ $mapping->subject->name }}</h1>
    <div class="meta">
        <span><strong>Grade:</strong> {{ $mapping->grade_stream }}</span>
        <span><strong>Term:</strong> {{ $mapping->term->name }} · {{ $mapping->yearSession->name }}</span>
        <span><strong>Campus:</strong> {{ $mapping->branch ?? 'School-wide' }}</span>
        <span><strong>Teacher:</strong> {{ $teacher?->name ?? '—' }}</span>
        <span><strong>Status:</strong> {{ $mapping->status->label() }}</span>
    </div>

    <div class="summary">
        <span>Total: {{ $stats['total_lessons'] }}</span>
        <span>Completed: {{ $stats['completed_lessons'] }}</span>
        <span>Pending: {{ $stats['pending_lessons'] }}</span>
        <span>Overdue: {{ $stats['overdue_lessons'] }}</span>
        <span>Late: {{ $stats['late_lessons'] }}</span>
        <span>Coverage: {{ number_format($stats['coverage_percent'], 2) }}%</span>
        @if ($stats['gaps'] !== [])
            <span class="danger">Gaps: {{ implode(', ', array_slice($stats['gaps'], 0, 20)) }}</span>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 4%">Lesson No.</th>
                <th style="width: 5%">Out Of</th>
                <th style="width: 13%">Theme / Strand</th>
                <th style="width: 13%">Substrand</th>
                <th style="width: 21%">Lesson Topic</th>
                <th style="width: 8%">Date of Completion</th>
                <th style="width: 7%">Late by</th>
                <th style="width: 6%">% Coverage</th>
                <th style="width: 11%">Teacher's Comment</th>
                <th style="width: 7%">Date of Assessment</th>
                <th style="width: 9%">Coordinator's Comment</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($groups as $strandId => $strandGroup)
                <tr class="strand"><td colspan="11">{{ $strandGroup['strand']->name }}@if ($strandGroup['strand']->expected_completion_date) — Expected: {{ $strandGroup['strand']->expected_completion_date->format('d/m/Y') }}@else — No expected date @endif</td></tr>
                @foreach ($strandGroup['substrands'] as $substrandId => $substrandGroup)
                    <tr class="substrand"><td colspan="11">{{ $substrandGroup['substrand']->name }}</td></tr>
                    @foreach ($substrandGroup['lessons'] as $lesson)
                        @php
                            $p = $progress[$lesson->id] ?? ['completion' => null, 'comment' => null];
                            $completed = ! empty($p['completion']);
                            $strandExpected = $strandGroup['strand']->expected_completion_date;
                            $lateDays = $strandExpected && $completed
                                ? (int) $strandExpected->copy()->startOfDay()->diffInDays(\Carbon\Carbon::parse($p['completion'])->startOfDay(), false)
                                : null;
                            $coverage = $mapping->total_lessons > 0 && $completed ? round(($lesson->lesson_number / $mapping->total_lessons) * 100, 2) : 0.0;
                        @endphp
                        <tr class="{{ $completed ? 'completed' : '' }}">
                            <td class="center">{{ $lesson->lesson_number }}</td>
                            <td class="center">{{ $mapping->total_lessons }}</td>
                            <td>{{ $strandGroup['strand']->name }}</td>
                            <td>{{ $substrandGroup['substrand']->name }}</td>
                            <td>{{ $lesson->topic }}</td>
                            <td class="center">{{ $p['completion'] ? \Carbon\Carbon::parse($p['completion'])->format('d/m/Y') : '—' }}</td>
                            <td class="center {{ $lateDays > 0 ? 'danger' : 'muted' }}">{{ $lateDays === null ? '—' : ($lateDays > 0 ? $lateDays.' day(s) late' : 'On time') }}</td>
                            <td class="center {{ $completed ? 'success' : 'muted' }}">{{ number_format($coverage, 2) }}%</td>
                            <td>{{ $p['comment'] ?? '' }}</td>
                            <td class="center">{{ $assessmentDates[$lesson->id] ?? '—' }}</td>
                            <td>{{ $coordinatorComments[$strandGroup['strand']->id] ?? '—' }}</td>
                        </tr>
                    @endforeach
                @endforeach
            @endforeach
        </tbody>
    </table>

    <div class="footer">Generated {{ now()->format('d/m/Y H:i') }} by {{ auth()->user()?->name ?? 'system' }} · {{ config('app.name') }}</div>
</body>
</html>
