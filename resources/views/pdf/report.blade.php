<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $report->subject }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #222; line-height: 1.5; }
        h1 { color: #1f2937; margin-bottom: 4px; }
        .meta { color: #4b5563; font-size: 12px; margin-bottom: 24px; }
        .summary { font-size: 14px; }
    </style>
</head>
<body>
    <h1>{{ $report->subject }}</h1>
    <div class="meta">
        Category: {{ $report->category }}<br>
        Sender: {{ $report->user?->name ?? 'N/A' }}<br>
        Date: {{ optional($report->created_at)->format('M d, Y') }}
    </div>
    <div class="summary">{!! \Illuminate\Support\Str::markdown($report->summary) !!}</div>
</body>
</html>
