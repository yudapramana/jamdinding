<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kokarde Panitia</title>

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

        :root {
            --role-panitera: #0d6efd;
            --role-hakim: #6f42c1;
            --role-pendaftaran: #198754;
            --role-verifikator: #fd7e14;
            --role-admin-event: #dc3545;
        }

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

        /* ================= BODY ================= */
        .name {
            text-align: center;
            font-size: 26px;
            font-weight: 900;
            text-transform: uppercase;
            margin: 12px 0 20px;
        }

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
            box-sizing: border-box;
        }

        .photo-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .status {
            color: #fff;
            text-align: center;
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 2px;
            padding: 10px 0;
            margin: 18px 0;
        }

        .role-name {
            text-align: center;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-top: 14px;
        }

        .contingent {
            text-align: center;
            font-size: 17px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-top: 8px;
            text-transform: uppercase;
        }

        /* ================= FOOTER ================= */
        .footer {
            padding-top: 23px;
            text-align: center;
            font-size: 11px;
            font-weight: 600;
            color: #fff;
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
                <div class="photo-box">
                    <div class="photo-frame"></div>
                </div>

                <div class="name">
                    {{ $user?->name ?? '-' }}
                </div>

                <div class="status" style="background: var(--{{ $roleClass }});">
                    {{ strtoupper(str_replace('_', ' ', $user?->role?->name ?? '-')) }}
                </div>

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
