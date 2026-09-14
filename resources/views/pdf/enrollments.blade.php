<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Enrollment Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h1 { text-align: center; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #555; padding: 5px; vertical-align: top; }
        th { background: #e5e7eb; }
        ul { margin: 0; padding-left: 14px; }
    </style>
</head>
<body>
    <h1>Enrollment Report</h1>
    <table>
        <thead>
            <tr>
                <th>Branch</th>
                <th>Academic Year</th>
                <th>Term</th>
                <th>Total Learners</th>
                <th>Learners per Class</th>
                <th>New Admissions</th>
                <th>Learners Left</th>
                <th>Comment</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($enrollments as $enrollment)
                <tr>
                    <td>{{ $enrollment->branch }}</td>
                    <td>{{ $enrollment->yearSession?->name }}</td>
                    <td>{{ $enrollment->term?->name }}</td>
                    <td>{{ $enrollment->total_learners }}</td>
                    <td>
                        <ul>
                            @foreach ($enrollment->class_breakdown ?? [] as $class)
                                <li>
                                    {{ $class['class_name'] ?? $class['class_id'] ?? 'Class' }}:
                                    Boys {{ $class['boys'] ?? 0 }},
                                    Girls {{ $class['girls'] ?? 0 }},
                                    Total {{ $class['total'] ?? ((int) ($class['boys'] ?? 0) + (int) ($class['girls'] ?? 0)) }},
                                    New Admissions {{ $class['new_admissions'] ?? 0 }},
                                    Learners Left {{ $class['departures'] ?? 0 }}
                                </li>
                            @endforeach
                        </ul>
                    </td>
                    <td>{{ $enrollment->new_admissions }}</td>
                    <td>{{ $enrollment->departures }}</td>
                    <td>{{ $enrollment->comment }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
