<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kokarde Peserta</title>

    <style>
        @page {
            size: A5;
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
           SHEET — UKURAN A5 FIXED
        ========================= */
        .sheet {
            width: 148mm;
            height: 210mm;
            padding: 10mm;
            box-sizing: border-box;
            overflow: hidden;
            background-image: url('{{ asset('images/bg-kokarde.png') }}');
            background-size: cover;
            background-repeat: no-repeat;
        }

        .kokarde {
            width: 100%;
            height: 190mm;
            /* 210mm - (10mm*2 padding) */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            z-index: 1;
            overflow: hidden;
        }

        /* ================= HEADER ================= */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header img {
            height: 72px;
        }

        .event-title {
            text-align: center;
            font-size: 45px;
            font-weight: 800;
            text-transform: uppercase;
            line-height: 1.15;
            margin: -48px 0 8px;
            letter-spacing: 3px;
        }

        .event-subtitle {
            text-align: center;
            font-size: 21px;
            font-weight: 600;
            margin-top: -15px;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        .event-platform {
            text-align: center;
            font-size: 11px;
            font-weight: 600;
            margin-top: -9px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: -0.5px;
        }

        /* ================= FOTO ================= */
        .photo-box {
            display: flex;
            justify-content: center;
            margin: 14px 0;
        }

        .photo-frame {
            width: 4cm;
            height: 5cm;
            border: 2px solid #000;
            padding: 3px;
            background: #fff;
            box-sizing: border-box;
        }

        .photo-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* ================= NAMA ================= */
        .name {
            text-align: center;
            font-size: 22px;
            font-weight: 900;
            text-transform: uppercase;
            margin: 10px 0;
        }

        /* ================= STATUS ================= */
        .status {
            background: #000;
            color: #fff;
            text-align: center;
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 2px;
            padding: 10px 0;
            margin: 18px 0;
        }

        /* ================= CABANG + GOLONGAN ================= */
        .category {
            text-align: center;
            font-size: 15px;
            font-weight: 900;
            text-transform: uppercase;
            line-height: 1.3;
            margin-top: 6px;
        }

        /* ================= KONTINGEN ================= */
        .contingent {
            text-align: center;
            font-size: 17px;
            font-weight: 900;
            letter-spacing: 1px;
            margin-top: 4px;
            text-transform: uppercase;
        }

        /* ================= FOOTER QR ================= */
        .footer {
            padding-top: 13px;
            display: flex;
            justify-content: center;
            align-items: center;
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
            color: #eee;
        }

        .qr svg {
            width: 90px;
            height: 90px;
        }
    </style>
</head>

<body>

    <section class="sheet">
        <div class="kokarde">

            <!-- ================= HEADER ================= -->
            <div>
                <div class="header">
                    <img src="{{ asset('images/logo-pemda.png') }}" alt="Logo Pemda">
                    <img src="{{ asset('images/logo-kemenag.png') }}" alt="Logo Kemenag">
                </div>

                <div class="event-title">
                    MTQN XLII
                </div>
                <div class="event-subtitle">
                    {{ strtoupper($event?->event_location ?? '-') }}
                </div>
                <div class="event-platform">
                    Musabaqah Tilawatil Qur’an Berbasis Digital
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
                <div class="qr-wrapper">
                    <div class="qr-label">SCAN UNTUK AKSES</div>
                    <div class="qr">
                        {!! QrCode::margin(1)->size(90)->generate(route('public.participant.show', $ep)) !!}
                    </div>
                </div>
            </div>

        </div>
    </section>

</body>

</html>
