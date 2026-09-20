<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Alameen Academy - Calendar of Events</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        .header { text-align: center; margin-bottom: 20px; }
        .logo { width: 100px; height: auto; margin-bottom: 8px; }
        h1 { text-align: center; margin: 0 0 4px 0; color: #1f2937; font-size: 16px; }
        h2 { text-align: center; color: #374151; font-size: 13px; margin: 0 0 16px 0; }
        h3 { color: #1f2937; font-size: 12px; margin: 20px 0 8px 0; border-bottom: 2px solid #1f2937; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { border: 1px solid #9ca3af; padding: 6px 8px; }
        th { background: #1f2937; color: #fff; font-weight: bold; font-size: 10px; }
        td { font-size: 10px; }
        .page-break { page-break-before: always; }
        tbody tr:nth-child(even) { background: #f9fafb; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('images/alameen-academy-logo.png') }}" alt="Alameen Academy Logo" class="logo">
        <h1>Alameen Academy - Calendar of Events</h1>
    </div>

    @php
        $groupedEvents = $events->sortBy(['term.name', 'event_date'])->groupBy('term.name');
    @endphp

    @foreach ($groupedEvents as $term => $termEvents)
        @if (!$loop->first)
            <div class="page-break"></div>
        @endif

        <h3>{{ $term ?? 'Uncategorized' }}</h3>

        <table>
            <thead>
                <tr>
                    <th>Term</th>
                    <th>Week</th>
                    <th>Event Name</th>
                    <th>Event Date</th>
                    <th>In-Charge</th>
                    <th>Classes</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($termEvents as $event)
                    <tr>
                        <td>{{ str_replace('Term ', '', $event->term->name ?? '') ?: 'N/A' }}</td>
                        <td>{{ preg_replace('/\D/', '', $event->week->name ?? '') ?: 'N/A' }}</td>
                        <td>{{ $event->name }}</td>
                        <td>{{ $event->event_date }}</td>
                        <td>{{ $event->in_charge }}</td>
                        <td>
                            @if ($event->classes->isNotEmpty())
                                {{ $event->classes->pluck('name')->join(', ') }}
                            @else
                                <em style="color: #9ca3af;">N/A</em>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
</body>
</html>
