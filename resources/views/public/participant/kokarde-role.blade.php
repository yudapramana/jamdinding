<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kokarde Panitia</title>

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

        .kokarde {
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            z-index: 1;
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

        /* ================= BODY ================= */
        .name {
            text-align: center;
            font-size: 26px;
            font-weight: 900;
            text-transform: uppercase;
            margin: 12px 0 20px;
        }

        /* =========================
        PHOTO PLACEHOLDER (3x4)
        ========================= */
        .photo-box {
            display: flex;
            justify-content: center;
            margin: 14px 0;
        }

        .photo-frame {
            width: 4cm;
            /* 2 */
            height: 5cm;
            /* 3 */
            border: 2px solid #000;
            padding: 3px;
        }

        .photo-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }


        /* ================= FOOTER ================= */
        .footer {
            padding-top: 23px;
            text-align: center;
            font-size: 11px;
            font-weight: 600;
            color: #fff;
            margin-bottom: -27px;
            text-transform: uppercase
        }

        .sheet {
            background-image: url('{{ asset('images/bg-kokarde.png') }}');
            background-size: cover;
            background-repeat: no-repeat;
        }

        @media screen {
            body {
                background: #ccc;
            }
        }

        @media print {
            body {
                background: #fff;
            }
        }

        /* =========================
        ROLE COLOR THEME
        ========================= */
        :root {
            --role-panitera: #0d6efd;
            /* biru */
            --role-hakim: #6f42c1;
            /* ungu */
            --role-pendaftaran: #198754;
            /* hijau */
            --role-verifikator: #fd7e14;
            /* oranye */
            --role-admin-event: #dc3545;
            /* merah */
        }

        /* STATUS BAR */
        .status {
            color: #fff;
            text-align: center;
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 2px;
            padding: 10px 0;
            margin: 18px 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ROLE LABEL */
        .role-name {
            text-align: center;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 14px;
        }

        /* CONTINGENT */
        .contingent {
            text-align: center;
            font-size: 17px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-top: 8px;
            text-transform: uppercase;
        }
    </style>
</head>


@php
    $roleSlug = $user?->role?->slug ?? 'panitera';

    $roleClassMap = [
        'panitera' => 'role-panitera',
        'dewan_hakim' => 'role-hakim',
        'pendaftaran' => 'role-pendaftaran',
        'verifikator' => 'role-verifikator',
        'admin_event' => 'role-admin-event',
    ];

    $roleClass = $roleClassMap[$roleSlug] ?? 'role-panitera';
@endphp



<body class="A5">

    <section class="sheet padding-10mm">

        <div class="kokarde">

            <!-- ================= HEADER ================= -->
            <div>
                <div class="header">
                    <img src="{{ asset('images/logo-pemda.png') }}" alt="Logo Pemda">
                    <img src="{{ asset('images/logo-kemenag.png') }}" alt="Logo Kemenag">
                </div>

                <div class="event-title">
                    MTQN XLI
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

                <!-- FOTO (BINGKAI SAJA) -->
                <div class="photo-box">
                    <div class="photo-frame"></div>
                </div>

                <!-- NAMA -->
                <div class="name">
                    {{ $user?->name ?? '-' }}
                </div>

                <!-- STATUS -->
                <div class="status" style="background: var(--{{ $roleClass }});">
                    {{ strtoupper(str_replace('_', ' ', $user?->role?->name ?? '-')) }}
                </div>

                <!-- KONTINGEN (KHUSUS ROLE TERTENTU) -->
                @if (in_array($user?->role?->slug, ['pendaftaran', 'verifikator']))
                    @php
                        $contingent = match ($event->event_level) {
                            'national' => $user?->province?->name,
                            'province' => $user?->regency?->name,
                            'regency' => $user?->district?->name,
                            'district' => $user?->village?->name,
                            default => '-',
                        };
                    @endphp

                    <div class="contingent">
                        {{ strtoupper($contingent ?? '-') }}
                    </div>
                @endif

            </div>

            <!-- ================= FOOTER ================= -->
            <div class="footer">
                {{ $event?->event_name }} {{ $event?->event_year }} <br>
                Kementerian Agama Republik Indonesia
            </div>

        </div>

    </section>

</body>

</html>
