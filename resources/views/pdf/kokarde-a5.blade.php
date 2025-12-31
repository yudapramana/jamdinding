<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">

    <style>
        @page {
            size: A5;
            margin: 10mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 0;
        }

        .kokarde {
            border: 2px solid #000;
            width: 100%;
            height: 100%;
            padding: 12px;
            box-sizing: border-box;
            text-align: center;
            page-break-after: always;
        }

        .event-name {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.3;
        }

        .event-year {
            font-size: 12px;
            margin-bottom: 10px;
        }

        .participant-name {
            font-size: 22px;
            font-weight: bold;
            margin: 12px 0;
            line-height: 1.2;
        }

        .category {
            font-size: 13px;
            line-height: 1.4;
            margin-bottom: 12px;
        }

        .number {
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .region {
            font-size: 13px;
            margin-top: 8px;
        }
    </style>
</head>

<body>

    @foreach ($event_participants as $item)
        <div class="kokarde">

            {{-- EVENT --}}
            <div class="event-name">
                {{ $event->event_name }}
            </div>
            <div class="event-year">
                {{ $event->event_year }}
            </div>

            {{-- NAMA PESERTA --}}
            <div class="participant-name">
                {{ $item->participant->full_name ?? '-' }}
            </div>

            {{-- CABANG & KATEGORI --}}
            <div class="category">
                {{ $item->eventBranch->name ?? '-' }}<br>
                {{ $item->eventCategory->full_name ?? '-' }}
            </div>

            {{-- NOMOR PESERTA --}}
            <div class="number">
                No. Peserta: {{ $item->participant_number ?? '-' }}
            </div>

            {{-- KONTINGEN --}}
            <div class="region">
                Kontingen {{ $region->name }}
            </div>

        </div>
    @endforeach

</body>

</html>
