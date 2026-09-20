<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Alameen Academy - {{ $report->subject }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; line-height: 1.6; }
        .header { text-align: center; margin-bottom: 24px; }
        .logo { width: 100px; height: auto; margin-bottom: 8px; }
        h1 { text-align: center; margin: 0 0 4px 0; color: #1f2937; font-size: 18px; }
        .meta { color: #4b5563; font-size: 11px; margin-bottom: 20px; padding: 12px; background: #f3f4f6; border-radius: 6px; border-left: 4px solid #1f2937; }
        .meta strong { color: #1f2937; }
        .summary { font-size: 13px; line-height: 1.7; }
        .summary h1, .summary h2, .summary h3 { color: #1f2937; margin-top: 16px; }
        .summary p { margin-bottom: 10px; }
        .summary ul, .summary ol { margin-bottom: 10px; padding-left: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('images/alameen-academy-logo.png') }}" alt="Alameen Academy Logo" class="logo">
        <h1>{{ $report->subject }}</h1>
    </div>
    <div class="meta">
        <strong>Category:</strong> {{ $report->category }}<br>
        <strong>Sender:</strong> {{ $report->user?->name ?? 'N/A' }}<br>
        <strong>Date:</strong> {{ optional($report->created_at)->format('M d, Y') }}
    </div>
    <div class="summary">{!! \Illuminate\Support\Str::markdown($report->summary) !!}</div>
</body>
</html>
