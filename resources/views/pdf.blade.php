<!DOCTYPE html>
<html>
<head>
    <title>Calendar of Events</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            margin: 40px;
            color: #333;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #2c3e50;
            font-size: 22px;
            margin-bottom: 10px;
        }
        .header .details {
            color: #7f8c8d;
            font-size: 12px;
            margin-top: 5px;
        }
        .header .details span {
            margin-right: 20px;
        }
        .duration {
            font-size: 12px;
            color: #7f8c8d;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .summary-table th {
            background-color: #d4edda;
            color: #2c3e50;
            padding: 8px;
            border: 1px solid #a3cfbb;
            text-align: left;
            white-space: nowrap;
            font-size: 12px;
        }
        .summary-table td {
            padding: 8px;
            border: 1px solid #bdc3c7;
            text-align: left;
            white-space: nowrap;
            font-size: 12px;
        }
        .summary-table tr:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    
    <div class="header">
        <h1>MASJID AL-AMEEN ACADEMY - CALENDAR OF EVENTS</h1>
        <div class="details">
            <span><b>Calendar of Events</b></span>
            <span><b>Academic Year 2025</b></span>
        </div>
        <div class="duration">Period: Term 1 - 3</div>
    </div>
    
    <table class="summary-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Term</th>
                <th>Week</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Event Name</th>
                <th>Event Date</th>
                <th>In-Charge</th>
                <th>Classes</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($events as $index => $event)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $event->term->name ?? 'N/A' }}</td>
                    <td>{{ $event->week->name }}</td>
                    <td>{{ $event->week->start_date ?? 'N/A' }}</td>
                    <td>{{ $event->week->end_date ?? 'N/A' }}</td>
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
</body>
</html>