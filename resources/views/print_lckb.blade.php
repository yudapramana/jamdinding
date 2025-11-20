<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>PrintLCKB</title>

    <style>
        .titlelaporan {
            font-size: x-large;
            font-weight: bold;
        }

        table,
        th,
        td {
            border: 1px solid black;
            border-collapse: collapse;
        }

        td {
            padding-left: 5px !important;
        }


        .ttdfooter {
            text-align: center;
            border: none !important;
        }

        .ttdfooter>tr>td {
            text-align: center;
            border: none !important;
        }

        .ttdfooter tr td {
            text-align: center;
            border: none !important;
        }
    </style>
</head>

<body>
    <div style="text-align: center; margin-bottom: 0; padding-bottom:0;">
        <img width="67" src="images/logo_kemenag.png" style="text-align: center; margin-bottom: 0; padding-bottom:0;" />
    </div>

    <p style="align:center; text-align:center; margin-bottom: 5pt; padding-bottom:0; margin-top:3pt">
        <span class="titlelaporan"> LAPORAN CAPAIAN KINERJA BULANAN</span> <br>
        <span style="font-weight: bold">KEMENTERIAN AGAMA REPUBLIK INDONESIA</span> <br>
        <span>Bulan: {{ $month }} | Tahun : {{ $year }}</span> <br>
        {{-- <span style="font-size: xx-small">&nbsp;</span> --}}
    </p>


    <table style="width:100%; font-size:10pt;">
        <tr>
            <td width="15%">NAMA / NIP</td>
            <td width="3%">&nbsp;:&nbsp;</td>
            <td>
                <p style="margin-left: 20px; padding-left:20px;">{{ $user->name }} / {{ $user->username }} </p>
            </td>
            <td width="20%" rowspan="4" style="align-content: flex-start; align:left; vertical-align:top">Keterangan:</td>
        </tr>
        <tr>
            <td>JABATAN</td>
            <td>&nbsp;:&nbsp;</td>
            <td> <span style="border-spacing: 0 50px;">{{ $user->jabatan }} </span></td>
        </tr>
        <tr>
            <td>SATUAN KERJA</td>
            <td>&nbsp;:&nbsp;</td>

            <td> <span style="border-spacing: 0 50px;">{{ $user->org_name }} </span></td>
        </tr>

    </table>

    <span style="font-size: xx-small">&nbsp;</span>

    <table style="width:100%; font-size:10pt; text-align:center">
        <thead>

            <tr style="background-color:rgb(165, 162, 162)">
                <th rowspan="2">NO</th>
                <th rowspan="2">PEKERJAAN / URAIAN TUGAS</th>
                <th colspan="2">HASIL</th>
                <th rowspan="2">BUKTI DOKUMEN</th>
            </tr>
            <tr style="background-color:rgb(165, 162, 162)">
                <th>VOL</th>
                <th>UNIT</th>
            </tr>
            <tr style="background-color:rgb(165, 162, 162);">
                <th style="font-size: 9pt;">1</th>
                <th style="font-size: 9pt;">2</th>
                <th style="font-size: 9pt;">3</th>
                <th style="font-size: 9pt;">4</th>
                <th style="font-size: 9pt;">5</th>
            </tr>

        </thead>

        <tbody style="font-weight:normal !important;">

            @foreach ($works as $key => $work)
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td style="text-align: left; align:left;">
                        <span style="font-weight: bolder">{{ ltrim($work->work_name) }}</span> <br>
                        <span style="font-size:x-small">
                            <pre>{{ ltrim($work->work_detail_merge) }}</pre>
                        </span>
                    </td>
                    <td>{{ $work->total_volume }}</td>
                    <td style="text-align: left;">{{ $work->unit }}</td>
                    <td>{{ $work->evidence }}</td>

                </tr>
            @endforeach

        </tbody>

    </table>

    <span>&nbsp;</span>
    <div style=" page-break-inside: avoid;">
        <table class="ttdfooter" style="width:100%; page-break-inside: avoid !important; font-size:10pt " autosize="1">
            <tr style="page-break-inside: avoid !important;">
                <td width="29%"> {{ $user->nama_pemeriksa != '-' ? 'Atasan langsung' : '' }}</td>
                <td></td>

                <td></td>
                <td></td>
                <td></td>
                <td>Pembuat Laporan</td>
            </tr>
            <tr style="page-break-inside: avoid !important;">
                <td>&nbsp;</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td width="39%"><span style="font-weight: bold"><u>{{ $user->nama_pemeriksa != '-' ? $user->nama_pemeriksa : '' }}</u></span><br>
                    {{ $user->nama_pemeriksa != '-' ? 'NIP.' . $user->nip_pemeriksa : '' }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td width="39%"><span style="font-weight: bold"><u>{{ $user->name }}</u></span><br>
                    NIP.{{ $user->username }}
                </td>
            </tr>

        </table>
    </div>

</body>

</html>
