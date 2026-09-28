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

        /* SHEET SEBAGAI FLEX CONTAINER VERTICAL */
        .sheet {
            display: flex;
            flex-direction: column;
            width: 297mm;
            height: 210mm;
            padding: 10mm;
            box-sizing: border-box;
            overflow: hidden;
            position: relative;
        }

        /* ================= HEADER SECTION ================= */
        .header-section {
            flex-shrink: 0;
            /* Header tidak ikut menyusut/membesar */
        }

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

        /* ================= BODY / KOTAK PESERTA ================= */
        /* Kontainer mengambil sisa tinggi kertas secara penuh */
        .participants-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-grow: 1;
            margin-top: 10px;
            margin-bottom: 20px;
            /* Sisakan sedikit ruang untuk tulisan footer */
        }

        .participant-card {
            flex: 1;
            max-width: 32%;
            /* Menjaga ukuran tetap konsisten maksimal 3 box */
            border: 3px solid #000;
            padding: 10px;
            background: #fff;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }

        /* Frame foto akan memanjang mengisi sisa tinggi dalam card */
        .photo-frame {
            width: 100%;
            flex-grow: 1;
            border: 2px solid #000;
            box-sizing: border-box;
            margin-bottom: 10px;
            background: #f8f8f8;
            overflow: hidden;
        }

        .photo-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: top center;
            /* Fokus di bagian kepala jika foto terpotong */
        }

        .name {
            font-size: 18px;
            font-weight: 900;
            text-transform: uppercase;
            line-height: 1.3;
            text-align: center;
            min-height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ================= FOOTER ================= */
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
            <!-- HEADER SECTION -->
            <div class="header-section">
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
            </div>

            <!-- ANGGOTA TIM (UKURAN FULL HEIGHT) -->
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
            {{-- <div class="footer">
                Cocokkan wajah peserta dengan foto di atas sebelum tampil
            </div> --}}
        </section>

        @if (!$loop->last)
            <div style="page-break-after: always;"></div>
        @endif
    @endforeach

</body>

</html>
