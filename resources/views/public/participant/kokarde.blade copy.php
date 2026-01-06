<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kokarde Peserta</title>

    <!-- paper-css -->
    <link rel="stylesheet" href="https://unpkg.com/paper-css@0.4.1/paper.css">

    <style>
        @page {
            size: A5
        }

        * {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #eee;
        }

        /* =========================
           SHEET & CONTAINER
        ========================= */
        .kokarde {
            /* border: 2px solid #111;
            padding: 10mm; */
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .kokarde {
            position: relative;
            z-index: 1;
        }

        /* =========================
           HEADER
        ========================= */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header img {
            height: 72px;
            /* logo diperbesar */
        }

        /*
        .event-title {
            text-align: center;
            font-size: 47px;
            font-weight: 800;
            text-transform: uppercase;
            line-height: 1.3;
            margin: -50px 0 10px;
            font-stretch: extra-expanded;
        } */

        .event-title {
            text-align: center;
            font-size: 47px;
            font-weight: 800;
            text-transform: uppercase;
            line-height: 1.15;
            margin: -48px 0 8px;
            letter-spacing: 3px;
            /* ⬅️ huruf lebih rapat */
        }


        .event-subtitle {
            text-align: center;
            font-size: 15px;
            font-weight: 600;
            margin-top: -15px;
            margin-bottom: 10px;
        }

        .event-platform {
            text-align: center;
            font-size: 11px;
            font-weight: 600;
            margin-top: -9px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        /* =========================
           FOTO
        ========================= */
        /* =========================
        FOTO (RASIO 2:3 - PORTRAIT)
        ========================= */
        .photo-box {
            display: flex;
            justify-content: center;
            margin: 14px 0;
        }

        .photo-frame {
            width: 4cm;
            /* 2 */
            height: 6cm;
            /* 3 */
            border: 2px solid #000;
            padding: 3px;
            background: #fff;
        }

        .photo-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* =========================
           NAMA
        ========================= */
        .name {
            text-align: center;
            font-size: 22px;
            font-weight: 900;
            text-transform: uppercase;
            margin: 10px 0;
        }

        /* =========================
           STATUS
        ========================= */
        .status {
            background: #000;
            color: #fff;
            text-align: center;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 2px;
            padding: 6px 0;
            margin: 10px 0;

            /* 🔑 WAJIB AGAR BACKGROUND MUNCUL DI PRINT / PDF */
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* =========================
           INFO
        ========================= */
        .info {
            font-size: 13px;
            line-height: 1.5;
            margin-top: 6px;
        }

        .info strong {
            font-weight: 700;
        }

        /* =========================
        FOOTER QR ONLY
        ========================= */
        .footer {
            border-top: 1px dashed #444;
            padding-top: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            border-top: 2px dashed rgba(0, 0, 0, .25);
            z-index: 1;
        }

        .qr-wrapper {
            text-align: center;
        }

        .qr-label {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .qr svg {
            width: 90px;
            height: 90px;
        }


        /* =========================
        CABANG + GOLONGAN
        ========================= */
        .category {
            text-align: center;
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1.3;
            margin-top: 6px;
        }

        /* =========================
        KONTINGEN
        ========================= */
        .contingent {
            text-align: center;
            font-size: 17px;
            font-weight: 600;
            letter-spacing: 1px;
            margin-top: 4px;
            text-transform: uppercase;
        }

        /* =========================
           SCREEN ONLY
        ========================= */
        @media screen {
            body {
                background: #ccc;
            }

            .print-hint {
                text-align: center;
                margin-top: 12px;
                font-size: 13px;
                color: #444;
            }
        }

        @media print {
            body {
                background: #fff;
            }

            .print-hint {
                display: none;
            }
        }



        /* Background */
        .bg-mtq {
            position: absolute;
            inset: 0;
            z-index: 0;

            /* WARNA DASAR MTQ */
            background:
                /* motif islami (SVG inline) */
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120' viewBox='0 0 120 120'%3E%3Cg fill='none' stroke='%23006633' stroke-opacity='0.07' stroke-width='2'%3E%3Cpath d='M60 0 L120 60 L60 120 L0 60 Z'/%3E%3Cpath d='M60 20 L100 60 L60 100 L20 60 Z'/%3E%3C/g%3E%3C/svg%3E"),
                linear-gradient(180deg,
                    #f9fff9 0%,
                    #ffffff 40%,
                    #ffffff 60%,
                    #f6fbf6 100%);

            background-size: 120px 120px, cover;
            background-repeat: repeat, no-repeat;
            background-position: center;

            pointer-events: none;
        }

        .kokarde>div:first-child {
            position: relative;
            z-index: 1;
            padding-bottom: 8px;
            border-bottom: 3px solid #006633;
        }
    </style>
</head>

<body class="A5">

    <section class="sheet padding-10mm">
        <div class="bg-mtq"></div>

        <div class="kokarde">

            <!-- ================= HEADER ================= -->
            <div>
                <div class="header">
                    <!-- Logo kiri -->
                    <img src="{{ asset('images/logo-pemda.png') }}" alt="Logo Pemda">

                    <!-- Logo kanan -->
                    <img src="{{ asset('images/logo-kemenag.png') }}" alt="Logo Kemenag">
                </div>

                <div class="event-title">
                    MTQN XLI
                </div>
                <div class="event-subtitle">
                    KECAMATAN IV JURAI PESISIR SELATAN
                </div>
                <div class="event-platform">
                    Musabaqah Tilawatil Qur’an Berbasis Elektronik
                </div>
            </div>

            <!-- ================= BODY ================= -->
            <div>

                <!-- FOTO -->
                <div class="photo-box">
                    <div class="photo-frame">
                        <img src="{{ $ep->participant->photo_url }}" alt="Foto Peserta">
                    </div>
                </div>

                <!-- NAMA -->
                <div class="name">
                    {{ $ep->participant->full_name }}
                </div>

                <!-- STATUS -->
                <div class="status">
                    PESERTA
                </div>

                <!-- INFO -->
                <div class="category">
                    {{ $ep->eventGroup?->full_name ?? '' }}
                </div>

                <div class="contingent">
                    {{ $ep->contingent ?? '-' }}
                </div>
                <div>
                    &nbsp;
                </div>
                <div>
                    &nbsp;
                </div>

            </div>

            <!-- ================= FOOTER ================= -->
            <div class="footer">
                <div class="qr-wrapper">
                    <div class="qr-label">SCAN UNTUK AKSES</div>
                    <div class="qr">
                        {!! QrCode::size(90)->generate(route('public.participant.show', $ep)) !!}
                    </div>
                </div>
            </div>


        </div>

    </section>

    <div class="print-hint">
        Cetak dengan ukuran <strong>A5</strong>, orientasi <strong>Portrait</strong>, skala <strong>100%</strong>
    </div>

</body>

</html>
