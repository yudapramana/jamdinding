<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kokarde Peserta A6</title>

    <!-- Memuat Font Kaligrafi/Islami dari Google Fonts
         (Segoe UI dihapus dari URL karena bukan font Google; family yang tidak valid
          bisa membuat seluruh request font gagal, termasuk Amiri) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@700&family=Reem+Kufi:wght@600;700&display=swap" rel="stylesheet">

    <style>
        @page {
            size: 105mm 148mm;
            margin: 0;
        }

        * {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            width: 105mm;
            /* tinggi sengaja tidak dikunci: tinggi tetap diatur oleh .sheet */
        }

        body {
            font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
            background: #fff;
        }

        .sheet {
            width: 105mm;
            /* sedikit di bawah 148mm: pembulatan piksel browser membuat tinggi tepat 148mm
               melewati batas halaman dan memunculkan halaman kosong */
            height: 147.5mm;
            padding: 5mm;
            overflow: hidden;
            background-image: url('{{ asset('images/bg-kokarde.png') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        /* Pindah halaman hanya SEBELUM kokarde berikutnya (bukan sesudah setiap kokarde),
           sehingga tidak ada halaman kosong di antara maupun di akhir */
        .sheet~.sheet {
            break-before: page;
            page-break-before: always;
        }

        .kokarde {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        /* ================= HEADER (BIG & TIGHT) ================= */
        .header-container {
            width: 100%;
            text-align: center;
            margin-top: 17mm;
            margin-bottom: 0px;
        }

        .event-title {
            font-family: 'Amiri', 'Reem Kufi', serif;
            font-size: 30px;
            /* sebelumnya 26px */
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
            color: #064e3b;
            line-height: 0.95;
        }

        .event-subtitle {
            font-size: 13px;
            /* sebelumnya 11.5px */
            font-weight: 800;
            margin-top: -3px;
            letter-spacing: 0.3px;
            color: #1f2937;
            line-height: 1.1;
        }

        .event-platform {
            font-size: 7.5px;
            /* sebelumnya 6.5px */
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            color: #4b5563;
            margin-top: 1px;
        }

        /* ================= BODY / KONTEN UTAMA ================= */
        .main-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex-grow: 1;
            width: 100%;
            justify-content: center;
            margin-top: -3mm;
            /* sebelumnya -8mm; disesuaikan karena elemen lebih besar */
        }

        .photo-frame {
            width: 30mm;
            /* sebelumnya 24mm */
            height: 40mm;
            /* sebelumnya 32mm */
            border: 2px solid #111;
            padding: 2px;
            background: #fff;
            margin-bottom: 2.5mm;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .photo-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .participant-number {
            font-size: 29px;
            /* sebelumnya 24px */
            font-weight: 900;
            color: #d32f2f;
            letter-spacing: 1px;
            line-height: 1;
            margin-bottom: 2px;
        }

        /* Area nama: tinggi minimal 2 baris supaya nama panjang punya ruang
           dan posisi elemen di bawahnya tidak bergeser-geser antar peserta */
        .name-wrap {
            width: 96%;
            min-height: 2.35em;
            font-size: 15px;
            /* sebelumnya 13px */
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 3px;
        }

        .name {
            width: 100%;
            font-size: 1em;
            font-weight: 900;
            text-transform: uppercase;
            text-align: center;
            line-height: 1.15;
            color: #000;
            padding: 0 1mm;
            overflow-wrap: anywhere;
            text-wrap: balance;
            /* pecah baris lebih seimbang */
        }

        /* Nama panjang: font otomatis dikecilkan sedikit agar tetap muat 2 baris */
        .name-wrap.name-sm {
            font-size: 13.5px;
        }

        .name-wrap.name-xs {
            font-size: 12px;
        }

        /* ================= STATUS BANNER ================= */
        .status-banner {
            background: #064e3b;
            color: #fff;
            width: 72%;
            text-align: center;
            font-size: 11px;
            /* sebelumnya 9.5px */
            font-weight: 900;
            letter-spacing: 2.5px;
            padding: 3px 0;
            border-radius: 4px;
            margin: 2px 0 5px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
        }

        /* ================= INFO LOMBA ================= */
        .category-badge {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            color: #1f2937;
            font-size: 9.5px;
            /* sebelumnya 8.5px */
            font-weight: 800;
            text-transform: uppercase;
            padding: 3px 11px;
            border-radius: 10px;
            text-align: center;
            letter-spacing: 0.3px;
            line-height: 1.2;
            max-width: 92%;
        }

        /* ================= FOOTER ================= */
        .footer {
            margin-top: auto;
            margin-bottom: 6mm;
            text-align: center;
        }

        .qr-label {
            font-size: 7px;
            /* sebelumnya 6px */
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
            color: #4b5563;
        }

        .qr svg {
            width: 48px;
            /* sebelumnya 42px */
            height: 48px;
        }

        @media print {

            html,
            body {
                width: 105mm;
                height: auto;
                overflow: visible;
            }
        }
    </style>
</head>

<body>

    <section class="sheet">
        <div class="kokarde">

            @php
                $fullName = $ep->participant->full_name ?? '';
                $nameLen = mb_strlen($fullName);
                $nameClass = $nameLen > 38 ? 'name-xs' : ($nameLen > 26 ? 'name-sm' : '');
            @endphp

            <!-- HEADER -->
            <div class="header-container">
                <div class="event-title">MTQN XLII</div>
                <div class="event-subtitle">{{ strtoupper($event?->event_location ?? '-') }}</div>
                <div class="event-platform">Musabaqah Tilawatil Qur’an Berbasis Digital</div>
            </div>

            <!-- KONTEN IDENTITAS -->
            <div class="main-content">

                <div class="photo-frame">
                    <img src="{{ $ep->participant->photo_url }}" alt="Foto Peserta">
                </div>

                <!-- Nomor Dada -->
                <div class="participant-number">
                    {{ $ep->participant_number ?? '-' }}
                </div>

                <div class="name-wrap {{ $nameClass }}">
                    <div class="name">{{ $fullName }}</div>
                </div>

                <!-- STATUS PESERTA -->
                <div class="status-banner">PESERTA</div>

                <div class="category-badge">
                    {{ $ep->eventGroup?->full_name ?? '' }}
                </div>

            </div>

            <!-- FOOTER QR CODE -->
            <div class="footer">
                <div class="qr-label">SCAN UNTUK AKSES</div>
                <div class="qr">
                    {!! QrCode::margin(1)->size(48)->generate(route('public.participant.show', $ep)) !!}
                </div>
            </div>

        </div>
    </section>

</body>

</html>
