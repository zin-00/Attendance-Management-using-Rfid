<!DOCTYPE html>
<html>
<head>
    <title>Attendance Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
        }
        h2 {
            text-align: center;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        p {
            text-align: center;
            font-size: 14px;
            color: #555;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid black;
            padding: 10px;
            text-align: left;
            font-size: 14px;
        }
        th {
            background-color: #f2f2f2;
            text-transform: uppercase;
        }
        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .status-present { color: green; font-weight: bold; }
        .status-absent { color: red; font-weight: bold; }
        .status-late { color: orange; font-weight: bold; }
        .type-in { color: blue; font-weight: bold; }
        .type-out { color: purple; font-weight: bold; }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            text-align: center;
            color: #777;
        }
    </style>
</head>
<body>

    <h2>Attendance Report</h2>
    <p>Date: {{ now()->format('F d, Y') }}</p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Employee Name</th>
                <th>Date & Time</th>
                <th>Status</th>
                <th>Type</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($attendances as $index => $attendance)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $attendance->employee->first_name }} {{ $attendance->employee->last_name }}</td>
                <td>{{ $attendance->created_at->format('F d, Y h:i A') }}</td>
                <td class="status-{{ strtolower($attendance->status) }}">{{ ucfirst($attendance->status) }}</td>
                <td class="type-{{ strtolower($attendance->type) }}">{{ ucfirst($attendance->type) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generated on {{ now()->format('F d, Y h:i A') }}
    </div>

</body>
</html>
