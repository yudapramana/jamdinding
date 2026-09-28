<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Verifikasi Tim {{ $event->event_name }}</title>

    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }

        * {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        .sheet {
            width: 297mm;
            height: 210mm;
            padding: 10mm;
            box-sizing: border-box;
            overflow: hidden;
            position: relative;
        }

        /* HEADER */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header img {
            height: 55px;
        }

        .event-title {
            text-align: center;
            font-size: 20px;
            font-weight: 800;
            text-transform: uppercase;
            line-height: 1.1;
            margin: 2px 0 1px;
            letter-spacing: 1.5px;
        }

        .event-subtitle {
            text-align: center;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 5px;
            text-transform: uppercase;
            color: #444;
        }

        .team-info {
            text-align: center;
            background: #000;
            color: #fff;
            padding: 8px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        /* GRID PESERTA */
        .participants-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
            margin-top: 15px;
        }

        .participant-card {
            width: 4.5cm;
            /* Ukuran bisa disesuaikan dengan jumlah maksimal peserta per regu */
            text-align: center;
            border: 2px solid #000;
            padding: 5px;
            background: #fff;
            box-sizing: border-box;
        }

        .photo-frame {
            width: 100%;
            height: 6cm;
            border: 2px solid #000;
            box-sizing: border-box;
            margin-bottom: 6px;
        }

        .photo-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .name {
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
            line-height: 1.2;
        }

        .footer {
            position: absolute;
            bottom: 10mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            color: #777;
        }
    </style>
</head>

<body>

    @foreach ($groupedRows as $groupKey => $teamMembers)
        @php
            $firstMember = $teamMembers->first();
            $categoryName = $firstMember->eventCategory?->category_name ?? $firstMember->eventGroup?->full_name;
            $contingent = $firstMember->contingent ?? '-';
        @endphp

        <section class="sheet">
            <!-- HEADER -->
            <div class="header">
                <img src="{{ asset('images/logo-pemda.png') }}" alt="Logo Pemda">
                <img src="{{ asset('images/logo-kemenag.png') }}" alt="Logo Kemenag">
            </div>

            <div class="event-title">
                {{ $event->event_name }}
            </div>
            <div class="event-subtitle">
                {{ strtoupper($event?->event_location ?? '-') }}
            </div>

            <div class="team-info">
                LEMBAR VERIFIKASI TIM / BEREGU <br>
                {{ $firstMember->eventGroup?->branch_name ?? '-' }} - {{ $categoryName }} <br>
                KAFILAH: {{ $contingent }}
            </div>

            <!-- ANGGOTA TIM -->
            <div class="participants-container">
                @foreach ($teamMembers as $ep)
                    <div class="participant-card">
                        <div class="photo-frame">
                            <img src="{{ $ep->participant->photo_url }}" alt="Foto Peserta">
                        </div>
                        <div class="name">
                            {{ $ep->participant->full_name }}
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- FOOTER -->
            <div class="footer">
                Cocokkan wajah peserta dengan foto di atas sebelum tampil
            </div>
        </section>

        @if (!$loop->last)
            <div style="page-break-after: always;"></div>
        @endif
    @endforeach

</body>

</html>
