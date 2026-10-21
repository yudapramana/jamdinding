<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kokarde Panitia A6</title>

    <!-- Memuat Font Kaligrafi/Islami dari Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@700&family=Reem+Kufi:wght@600;700&display=swap" rel="stylesheet">

    <style>
        /* Mengatur ukuran halaman cetak browser secara otomatis ke A6 */
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
        }

        body {
            font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
            background: #fff;
        }

        :root {
            --role-panitera: #0d6efd;
            --role-hakim: #6f42c1;
            --role-pendaftaran: #198754;
            --role-verifikator: #fd7e14;
            --role-admin-event: #dc3545;
        }

        .sheet {
            width: 105mm;
            /* Tinggi 147.5mm mencegah halaman kosong ekstra saat print ke PDF */
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

        /* ================= HEADER COMPACT & KALIGRAFI ================= */
        .header-container {
            width: 100%;
            text-align: center;
            margin-top: 17mm;
            /* Memberi ruang untuk logo di background */
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
            margin-bottom: 3.5mm;
            background: transparent;
            /* Diubah agar tembus pandang */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .photo-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Area nama dengan fleksibilitas 2 baris */
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

        /* ================= STATUS BANNER ROLE ================= */
        .status-banner {
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

        /* ================= INFO KONTINGEN ================= */
        .contingent {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            color: #222;
            margin-top: 2px;
            letter-spacing: 0.5px;
        }

        /* ================= FOOTER ================= */
        .footer {
            margin-top: auto;
            margin-bottom: 8mm;
            text-align: center;
            font-size: 8px;
            font-weight: 700;
            color: #4b5563;
            text-transform: uppercase;
            line-height: 1.3;
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

    // Logika ukuran font nama
    $fullName = $user?->name ?? '-';
    $nameLen = mb_strlen($fullName);
    $nameClass = $nameLen > 38 ? 'name-xs' : ($nameLen > 26 ? 'name-sm' : '');
@endphp

<body>

    <section class="sheet">
        <div class="kokarde">

            <!-- HEADER -->
            <div class="header-container">
                <div class="event-title">MTQN XLII</div>
                <div class="event-subtitle">{{ strtoupper($event?->event_location ?? '-') }}</div>
                <div class="event-platform">Musabaqah Tilawatil Qur’an Berbasis Digital</div>
            </div>

            <!-- KONTEN IDENTITAS -->
            <div class="main-content">

                <div class="photo-frame">
                    @if ($user->avatar)
                        <img src="{{ $user->avatar }}" alt="Foto">
                    @endif
                </div>

                <div class="name-wrap {{ $nameClass }}">
                    <div class="name">{{ $fullName }}</div>
                </div>

                <!-- ROLE STATUS -->
                <div class="status-banner" style="background: var(--{{ $roleClass }});">
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

            <!-- FOOTER -->
            <div class="footer">
                {{ $event?->event_name ?? 'MTQ' }} {{ $event?->event_year ?? date('Y') }} <br>
                Kementerian Agama Republik Indonesia
            </div>

        </div>
    </section>

</body>

</html>
