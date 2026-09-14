<!DOCTYPE html>
<html>
<head>
    <title>Calendar of Events</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            table-layout: auto;
        }
        th, td {
            border: 1px solid black;
            padding: 10px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f2f2f2;
            white-space: nowrap;
        }
        td {
            word-wrap: break-word;
        }
        .page-break {
            page-break-before: always; /* Forces a new page */
        }
    </style>
</head>
<body>
    <h2>MASJID AL-AMEEN ACADEMY - CALENDAR OF EVENTS | TERM 1-3 2025</h2>
    <h3>ACADEMIC CALENDAR OF EVENTS</h3>

    @php
        $groupedEvents = $events->sortBy(['term.name', 'event_date'])->groupBy('term.name');
    @endphp

    @foreach ($groupedEvents as $term => $termEvents)
        @if (!$loop->first)
            <div class="page-break"></div>
        @endif

        <h3>Term {{ str_replace('Term ', '', $term) }} Events</h3>

        <table>
            <thead>
                <tr>
                    <th>Term</th>
                    <th>Week</th>
                    <!--<th>Start Date</th>-->
                    <!--<th>End Date</th>-->
                    <th>Event Name</th>
                    <th>Event Date</th>
                    <th>In-Charge</th>
                    <th>Classes</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($termEvents as $event)
                    <tr>
                        <td>{{ str_replace('Term ', '', $event->term->name) ?? 'N/A' }}</td>
                        <td>{{ preg_replace('/\D/', '', $event->week->name) ?? 'N/A' }}</td>
                        <!--<td>{{ $event->week->start_date ?? 'N/A' }}</td>-->
                        <!--<td>{{ $event->week->end_date ?? 'N/A' }}</td>-->
                        <td>{{ $event->name }}</td>
                        <td>{{ $event->event_date }}</td>
                        <td>{{ $event->in_charge }}</td>
                        <td>
                            @if ($event->classes->isNotEmpty())
                                {{ $event->classes->pluck('name')->join(', ') }}
                            @else
                                N/A
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
</body>
</html>
