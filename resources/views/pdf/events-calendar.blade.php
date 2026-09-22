<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Calendar of Events - {{ $monthLabel }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            margin: 0;
            padding: 0;
        }

        .header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .header td {
            vertical-align: middle;
        }

        .school {
            font-size: 20px;
            font-weight: bold;
            color: #065f46;
            margin: 0;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            color: #111827;
            margin: 0;
        }

        .month {
            font-size: 18px;
            font-weight: bold;
            color: #065f46;
            text-align: right;
            margin: 0;
        }

        .legend {
            font-size: 9px;
            color: #374151;
            margin: 0 0 8px 0;
        }

        .legend span {
            display: inline-block;
            padding: 2px 8px;
            margin-right: 6px;
            border-radius: 3px;
            color: #ffffff;
            font-weight: bold;
        }

        table.grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table.layout {
            width: 100%;
            border-collapse: collapse;
        }

        table.layout td.sidebar {
            width: 17%;
            vertical-align: top;
            padding-right: 8px;
        }

        table.layout td.calendar {
            width: 83%;
            vertical-align: top;
        }

        .sidebar-month {
            font-size: 12px;
            font-weight: bold;
            color: #065f46;
            text-align: center;
            margin: 0 0 6px 0;
        }

        .sidebar img {
            width: 100%;
        }

        table.grid th {
            background: #065f46;
            color: #ffffff;
            font-size: 11px;
            padding: 6px;
            border: 1px solid #065f46;
            text-align: center;
        }

        table.grid td {
            border: 1px solid #9ca3af;
            vertical-align: top;
            height: 92px;
            padding: 3px;
            font-size: 9px;
        }

        td.other-month {
            background: #f3f4f6;
        }

        .day-number {
            font-weight: bold;
            font-size: 11px;
            color: #111827;
            margin-bottom: 3px;
        }

        td.other-month .day-number {
            color: #9ca3af;
        }

        .event-block {
            border-radius: 3px;
            color: #ffffff;
            font-size: 8px;
            font-weight: bold;
            padding: 2px 4px;
            margin-bottom: 2px;
        }

        .event-branch {
            font-weight: normal;
            font-size: 7px;
        }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <p class="school">MASJID AL-AMEEN ACADEMY</p>
                <p class="title">CALENDAR OF EVENTS</p>
            </td>
            <td style="text-align: right;">
                <p class="month">{{ strtoupper($monthLabel) }}</p>
                @if ($branch)
                    <p class="month" style="font-size: 12px;">{{ strtoupper($branch) }}</p>
                @endif
            </td>
        </tr>
    </table>

    <p class="legend">
        @foreach ($branchColors as $name => $color)
            <span style="background: {{ $color }};">{{ $name }}</span>
        @endforeach
    </p>

    <table class="layout">
        @if ($quoteImagePath)
            <tr>
                <td class="sidebar">
                    <p class="sidebar-month">{{ strtoupper(explode(' ', $monthLabel)[0]) }}</p>
                    <img src="{{ $quoteImagePath }}" alt="Month quote">
                </td>
                <td class="calendar">
        @endif

    <table class="grid">
        <thead>
            <tr>
                <th>Sun</th>
                <th>Mon</th>
                <th>Tue</th>
                <th>Wed</th>
                <th>Thu</th>
                <th>Fri</th>
                <th>Sat</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($weeks as $week)
                <tr>
                    @foreach ($week as $day)
                        <td class="{{ $day['in_month'] ? '' : 'other-month' }}">
                            <div class="day-number">{{ $day['date']->format('d') }}</div>
                            @foreach ($day['events'] as $event)
                                @php
                                    $start = substr((string) $event->event_date, 0, 10);
                                    $end = substr((string) ($event->end_date ?? $event->event_date), 0, 10);
                                    $isContinuation = $day['date']->toDateString() > $start;
                                    $color = $branchColors[$event->branch] ?? '#6b7280';
                                @endphp
                                <div class="event-block" style="background: {{ $color }};">
                                    @if ($isContinuation)
                                        &#8627;
                                    @endif
                                    {{ $event->name }}
                                    @if ($end !== $start && ! $isContinuation)
                                        <span class="event-branch">({{ \Carbon\Carbon::parse($start)->format('d M') }} - {{ \Carbon\Carbon::parse($end)->format('d M') }})</span>
                                    @endif
                                    <div class="event-branch">{{ $event->branch }}</div>
                                </div>
                            @endforeach
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($quoteImagePath)
                </td>
            </tr>
    </table>
    @endif
</body>
</html>
