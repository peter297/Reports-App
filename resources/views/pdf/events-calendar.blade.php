<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Calendar of Events - {{ $title }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            margin: 0;
            padding: 0;
        }

        .page-break {
            page-break-before: always;
        }

        .header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .header td {
            vertical-align: middle;
        }

        .school {
            font-size: 18px;
            font-weight: bold;
            color: #065f46;
            margin: 0;
        }

        .title {
            font-size: 22px;
            font-weight: bold;
            color: #111827;
            margin: 0;
        }

        .month {
            font-size: 17px;
            font-weight: bold;
            color: #111827;
            text-align: right;
            margin: 0;
        }

        .hijri-month {
            font-size: 12px;
            font-weight: bold;
            color: #065f46;
            text-align: right;
            margin: 0;
        }

        .legend {
            font-size: 8px;
            color: #374151;
            margin: 0 0 6px 0;
        }

        .legend span {
            display: inline-block;
            padding: 2px 8px;
            margin-right: 6px;
            border-radius: 3px;
            color: #ffffff;
            font-weight: bold;
        }

        table.layout {
            width: 100%;
            border-collapse: collapse;
        }

        table.layout td.sidebar {
            width: 16%;
            vertical-align: top;
            padding-right: 8px;
        }

        table.layout td.calendar {
            vertical-align: top;
        }

        .sidebar-month {
            font-size: 11px;
            font-weight: bold;
            color: #065f46;
            text-align: center;
            margin: 0 0 4px 0;
        }

        .sidebar img {
            width: 100%;
        }

        table.grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table.grid th {
            background: #065f46;
            color: #ffffff;
            font-size: 10px;
            padding: 4px;
            border: 1px solid #065f46;
            text-align: center;
        }

        table.grid td {
            border: 1px solid #9ca3af;
            vertical-align: top;
            padding: 2px 3px;
            font-size: 8px;
        }

        td.other-month {
            background: #f3f4f6;
        }

        .day-number {
            font-weight: bold;
            font-size: 10px;
            color: #111827;
            margin-bottom: 2px;
        }

        td.other-month .day-number {
            color: #9ca3af;
        }

        .hijri-day {
            font-size: 8px;
            font-weight: bold;
            color: #059669;
            margin-bottom: 2px;
        }

        .hijri-dot {
            display: inline-block;
            width: 6px;
            height: 6px;
            background: #059669;
            border-radius: 3px;
            margin-right: 2px;
        }

        .event-block {
            border-radius: 3px;
            color: #ffffff;
            font-size: 7px;
            font-weight: bold;
            padding: 2px 3px;
            margin-bottom: 2px;
        }

        .event-branch {
            font-weight: normal;
            font-size: 6px;
        }
    </style>
</head>
<body>
    @foreach ($months as $calendar)
        @if (! $loop->first)
            <div class="page-break"></div>
        @endif
        @php
            $weekCount = max(count($calendar['weeks']), 1);
            $gridHeight = 620;
            $rowHeight = (int) floor($gridHeight / $weekCount);
            $sidebarImageHeight = $gridHeight - 22;
        @endphp
        <table class="header">
            <tr>
                <td>
                    <p class="school">MASJID AL-AMEEN ACADEMY</p>
                    <p class="title">CALENDAR OF EVENTS</p>
                </td>
                <td style="text-align: right;">
                    <p class="month">{{ strtoupper($calendar['label']) }}</p>
                    <p class="hijri-month">{{ $calendar['hijri_label'] }}</p>
                    @if ($branch)
                        <p class="hijri-month">{{ strtoupper($branch) }}</p>
                    @endif
                </td>
            </tr>
        </table>

        <p class="legend">
            @foreach ($branchColors as $name => $color)
                <span style="background: {{ $color }};">{{ $name }}</span>
            @endforeach
            <span style="background: #059669;"><span class="hijri-dot" style="background: #ffffff;"></span> Hijri date</span>
        </p>

        <table class="layout">
            <tr>
                @if ($calendar['quoteImagePath'])
                    <td class="sidebar">
                        <p class="sidebar-month">{{ strtoupper(explode(' ', $calendar['label'])[0]) }}</p>
                        <img src="{{ $calendar['quoteImagePath'] }}" style="width: 100%; height: {{ $sidebarImageHeight }}px;" alt="Month quote">
                    </td>
                @endif
                <td class="calendar">
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
                            @foreach ($calendar['weeks'] as $week)
                                <tr>
                                    @foreach ($week as $day)
                                        <td class="{{ $day['in_month'] ? '' : 'other-month' }}" style="height: {{ $rowHeight }}px;">
                                            <div class="day-number">{{ $day['date']->format('d') }}</div>
                                            <div class="hijri-day"><span class="hijri-dot"></span>{{ $day['hijri_day'] }}</div>
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
                </td>
            </tr>
        </table>
    @endforeach
</body>
</html>
