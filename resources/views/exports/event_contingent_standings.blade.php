<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
        }

        th {
            background: #f0f0f0;
        }

        td.name {
            text-align: left;
        }
    </style>
</head>

<body>

    <h3 style="text-align:center">Klasemen Perolehan Juara Kontingen</h3>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Kontingen</th>
                <th>Juara 1</th>
                <th>Juara 2</th>
                <th>Juara 3</th>
                <th>Harapan 1</th>
                <th>Harapan 2</th>
                <th>Harapan 3</th>
                <th>Total Poin</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="name">{{ $row['contingent'] }}</td>
                    <td>{{ $row['medals'][1] ?? 0 }}</td>
                    <td>{{ $row['medals'][2] ?? 0 }}</td>
                    <td>{{ $row['medals'][3] ?? 0 }}</td>
                    <td>{{ $row['medals'][4] ?? 0 }}</td>
                    <td>{{ $row['medals'][5] ?? 0 }}</td>
                    <td>{{ $row['medals'][6] ?? 0 }}</td>
                    <td><strong>{{ $row['total_point'] }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>
