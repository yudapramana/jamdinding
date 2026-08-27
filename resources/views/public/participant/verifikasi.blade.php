<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Verifikasi Peserta</title>

    <style>
        @page {
            size: A4;
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

        /* =========================
           SHEET — TINGGI FIXED, BUKAN 100%
        ========================= */
        .sheet {
            width: 210mm;
            height: 297mm;
            padding: 10mm;
            overflow: hidden;
            box-sizing: border-box;
        }

        .verifikasi {
            width: 100%;
            height: 277mm;
            /* 297mm - (10mm*2 padding) */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        /* ================= HEADER (COMPACT) ================= */
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
            font-size: 22px;
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
            margin-bottom: 1px;
            text-transform: uppercase;
            color: #444;
        }

        .verify-label {
            text-align: center;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 4px;
            color: #666;
        }

        /* ================= FOTO — FOKUS UTAMA ================= */
        .photo-box {
            display: flex;
            justify-content: center;
            margin: 4px 0;
        }

        .photo-frame {
            width: 14cm;
            height: 18cm;
            border: 4px solid #000;
            padding: 6px;
            background: #fff;
            box-sizing: border-box;
        }

        .photo-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* ================= NAMA & DATA (COMPACT) ================= */
        .name {
            text-align: center;
            font-size: 24px;
            font-weight: 900;
            text-transform: uppercase;
            margin: 6px 0 4px;
            line-height: 1.1;
        }

        .status {
            background: #000;
            color: #fff;
            text-align: center;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 2px;
            padding: 5px 0;
            margin: 4px 0;
        }

        .category {
            text-align: center;
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            line-height: 1.2;
            margin-top: 3px;
        }

        .contingent {
            text-align: center;
            font-size: 16px;
            font-weight: 900;
            letter-spacing: 0.5px;
            margin-top: 2px;
            text-transform: uppercase;
        }

        .footer {
            padding-top: 6px;
            text-align: center;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            color: #777;
        }
    </style>
</head>

<body>

    <section class="sheet">

        <div class="verifikasi">

            <!-- ================= HEADER ================= -->
            <div>
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
                <div class="verify-label">
                    Lembar Verifikasi Peserta
                </div>
            </div>

            <!-- ================= BODY ================= -->
            <div>

                <div class="photo-box">
                    <div class="photo-frame">
                        <img src="{{ $ep->participant->photo_url }}" alt="Foto Peserta">
                    </div>
                </div>

                <div class="name">
                    {{ $ep->participant->full_name }}
                </div>

                <div class="status">
                    PESERTA
                </div>

                <div class="category">
                    {{ $ep->eventGroup?->full_name ?? '' }}
                </div>

                <div class="contingent">
                    {{ $ep->contingent ?? '-' }}
                </div>

            </div>

            <!-- ================= FOOTER ================= -->
            <div class="footer">
                Cocokkan wajah peserta dengan foto di atas sebelum tampil
            </div>

        </div>

    </section>

</body>

</html>
