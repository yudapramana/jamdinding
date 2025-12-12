<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            margin: 18px 18px 22px 18px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #000;
        }

        .wrap-title {
            text-align: center;
            margin-bottom: 10px;
        }

        .t1 {
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
        }

        .t2 {
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            margin-top: 2px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px 6px;
            vertical-align: middle;
        }

        thead th {
            background: #efefef;
            font-weight: 700;
            text-transform: uppercase;
            text-align: center;
        }

        /* ✅ kolom: NO kecil, NAMA lebih kecil dari CABANG/GOLONGAN */
        .col-no {
            width: 8%;
            text-align: center;
        }

        .col-name {
            width: 38%;
        }

        /* lebih kecil */
        .col-cat {
            width: 54%;
        }

        /* lebih besar */

        td.col-name,
        td.col-cat {
            text-transform: uppercase;
        }

        td.col-name {
            word-wrap: break-word;
        }

        td.col-cat {
            word-wrap: break-word;
        }

        /* garis luar lebih tebal seperti contoh */
        table {
            border: 2px solid #000;
        }

        thead th {
            border-bottom: 2px solid #000;
        }
    </style>
</head>

<body>
    <div class="wrap-title">
        <div class="t1">{{ $title1 }}</div>
        <div class="t2">{{ $title2 }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="col-no">NO</th>
                <th class="col-name">NAMA</th>
                <th class="col-cat">CABANG/GOLONGAN</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $i => $ep)
                <tr>
                    <td class="col-no">{{ $i + 1 }}</td>
                    <td class="col-name">{{ strtoupper(optional($ep->participant)->full_name ?? '-') }}</td>
                    <td class="col-cat">{{ strtoupper(optional($ep->eventCategory)->full_name ?? '-') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
