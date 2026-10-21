<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kokarde Peserta A6</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@700&family=Reem+Kufi:wght@600;700&display=swap" rel="stylesheet">

    <style>
        /* ================= SETUP KERTAS A6 ================= */
        @page {
            size: 105mm 148mm;
            /* Kunci ukuran kertas A6 */
            margin: 0mm;
            /* Hilangkan margin default browser */
        }

        * {
            -webkit-print-color-adjust: exact !important;
            /* Paksa print background */
            print-color-adjust: exact !important;
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            width: 105mm;
            background: #fff;
            font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
        }

        .sheet {
            width: 105mm;
            height: 147.5mm;
            /* Sedikit di bawah 148mm untuk mencegah halaman kosong ekstra di browser */
            padding: 5mm;
            overflow: hidden;
            background-image: url('{{ asset('images/bg-kokarde.png') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        /* Hanya page-break sebelum elemen berikutnya */
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

        /* ================= HEADER ================= */
        .header-container {
            width: 100%;
            text-align: center;
            margin-top: 13mm;
            /* Dinaikkan ke atas selayang */
            margin-bottom: 0px;
        }

        .event-title {
            font-family: 'Amiri', 'Reem Kufi', serif;
            font-size: 30px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
            color: #064e3b;
            line-height: 0.95;
        }

        .event-subtitle {
            font-size: 13px;
            font-weight: 800;
            margin-top: -3px;
            letter-spacing: 0.3px;
            color: #1f2937;
            line-height: 1.1;
        }

        .event-platform {
            font-size: 7.5px;
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
        }

        .photo-frame {
            width: 33mm;
            height: 44mm;
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
            font-weight: 900;
            color: #d32f2f;
            letter-spacing: 1px;
            line-height: 1;
            margin-bottom: 2px;
        }

        .name-wrap {
            width: 96%;
            min-height: 2.35em;
            font-size: 15px;
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
        }

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
            margin-bottom: 5mm;
            text-align: center;
        }

        .qr-label {
            font-size: 7px;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
            color: #4b5563;
        }

        .qr svg {
            width: 63px;
            height: 63px;
        }

        /* ================= MEDIA PRINT RULES ================= */
        @media print {

            html,
            body {
                width: 105mm;
                height: 148mm;
                margin: 0;
                padding: 0;
            }

            .sheet {
                width: 105mm;
                height: 148mm;
                /* Paskan tepat di 148mm saat diprint */
                margin: 0;
                box-shadow: none;
                /* Hilangkan bayangan saat cetak agar bersih */
            }

            .photo-frame,
            .status-banner {
                box-shadow: none !important;
                /* Mencegah shadow bocor jadi tinta kotor */
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
                    <!-- Ukuran disinkronkan ke 63 untuk ketajaman sempurna -->
                    {!! QrCode::margin(1)->size(63)->generate(route('public.participant.show', $ep)) !!}
                </div>
            </div>

        </div>
    </section>

</body>

</html>
