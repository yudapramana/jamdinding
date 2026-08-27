<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Verifikasi Peserta - {{ $branch->name ?? '' }}</title>
    <style>
        @page {
            size: A4;
            margin: 15px;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: sans-serif;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .header h2 {
            margin: 0;
            font-size: 16px;
        }

        .header p {
            margin: 2px 0;
            font-size: 11px;
            color: #444;
        }

        table.grid {
            width: 100%;
            border-collapse: collapse;
        }

        table.grid td {
            width: 25%;
            border: 1px solid #333;
            padding: 6px;
            text-align: center;
            vertical-align: top;
            page-break-inside: avoid;
        }

        .photo {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border: 1px solid #999;
        }

        .name {
            font-size: 11px;
            font-weight: bold;
            margin-top: 4px;
        }

        .meta {
            font-size: 9.5px;
            color: #333;
            margin-top: 1px;
        }

        .kontingen {
            font-size: 9px;
            color: #666;
            margin-top: 2px;
        }
    </style>
</head>

<body>

    <div class="header">
        <h2>LEMBAR VERIFIKASI PESERTA</h2>
        <p>{{ $event->event_name }} {{ $event->event_year ? '(' . $event->event_year . ')' : '' }}</p>
        <p>Cabang Lomba: <strong>{{ $branch->name ?? '-' }}</strong> &nbsp;|&nbsp; Total Peserta: {{ $rows->count() }}</p>
    </div>

    @php $chunks = $rows->chunk(4); @endphp

    <table class="grid">
        @foreach ($chunks as $chunk)
            <tr>
                @foreach ($chunk as $row)
                    <td>
                        <img class="photo" src="{{ public_path('storage/' . $row->participant->photo_url) }}" alt="foto">
                        <div class="name">{{ $row->participant->name }}</div>
                        <div class="meta">No. {{ $row->participant_number ?? '-' }}</div>
                        <div class="meta">{{ $row->eventGroup->name ?? '-' }} @if ($row->eventCategory)
                                - {{ $row->eventCategory->name }}
                            @endif
                        </div>
                        <div class="kontingen">{{ $row->participant->kontingen->name ?? ($row->participant->province->name ?? '-') }}</div>
                    </td>
                @endforeach

                {{-- isi cell kosong biar grid tetap rapi kalau jumlah peserta ganjil --}}
                @for ($i = $chunk->count(); $i < 4; $i++)
                    <td style="border: none;"></td>
                @endfor
            </tr>
        @endforeach
    </table>

</body>

</html>
