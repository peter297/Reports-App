<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $period_label }} Attendance Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h1 { text-align: center; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #555; padding: 5px; }
        th { background: #e5e7eb; }
        .number { text-align: right; }
    </style>
</head>
<body>
    <h1>{{ $period_label }} Attendance Summary</h1>
    <table>
        <thead>
            <tr>
                <th>Period</th>
                <th>Branch</th>
                <th>Section</th>
                <th>Attendance Records</th>
                <th>Total Learners</th>
                <th>Total Present</th>
                <th>Total Absent</th>
                <th>% Present</th>
                <th>% Absent</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['period_label'] }}</td>
                    <td>{{ $row['branch'] }}</td>
                    <td>{{ $row['section'] }}</td>
                    <td class="number">{{ $row['record_count'] }}</td>
                    <td class="number">{{ $row['class_total'] }}</td>
                    <td class="number">{{ $row['total_present'] }}</td>
                    <td class="number">{{ $row['total_absent'] }}</td>
                    <td class="number">{{ number_format($row['percentage_present'], 2) }}%</td>
                    <td class="number">{{ number_format($row['percentage_absent'], 2) }}%</td>
                </tr>
            @empty
                <tr><td colspan="9">No attendance records found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
