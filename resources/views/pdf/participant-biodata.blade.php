<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Biodata Peserta</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 11px;
            margin: 20px;
        }

        .header {
            border: 1px solid #000;
            padding: 10px 12px;
            margin-bottom: 10px;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
        }

        .title-big {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .subtitle {
            font-size: 11px;
            margin-top: 2px;
            line-height: 1.35;
        }

        .event-block {
            text-align: right;
            font-size: 10px;
            line-height: 1.3;
        }

        .event-name {
            font-weight: bold;
            text-transform: uppercase;
        }

        .header-main {
            margin-top: 8px;
            border-top: 1px solid #000;
            padding-top: 6px;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            gap: 10px;
        }

        .header-main-left div,
        .header-main-right div {
            margin-bottom: 2px;
        }

        .label {
            display: inline-block;
            min-width: 90px;
            font-weight: bold;
        }

        .section-title {
            margin: 12px 0 6px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            text-decoration: underline;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        td {
            padding: 4px 4px;
            vertical-align: top;
        }

        .field-label {
            width: 28%;
        }

        .field-separator {
            width: 2%;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border: 1px solid #000;
            font-size: 10px;
            text-transform: uppercase;
        }

        .footer {
            margin-top: 14px;
            font-size: 10px;
            text-align: right;
        }
    </style>
</head>

<body>
    @php
        /** @var \App\Models\Participant $p */
        /** @var \App\Models\Event $e */
        /** @var \App\Models\EventParticipant $ep */

        $p = $participant;
        $e = $event;
        $ep = $eventParticipant;

        // ====== EVENT (sesuai pola Vue: event_name, event_year, event_location, event_level) ======
        $eventName = $e->event_name ?? ($e->name ?? '-');
        $eventYear = $e->event_year ?? (optional($e->tanggal_mulai)->format('Y') ?? now()->year);
        $eventLevel = $e->event_level ?? '-';
        $eventLoc = $e->event_location ?? '-';

        // ====== WILAYAH PESERTA (pakai relasi dulu, fallback ke *_name) ======
        $prov = optional($p->province)->name ?? $p->province_name;
        $kabkot = optional($p->regency)->name ?? $p->regency_name;
        $kec = optional($p->district)->name ?? $p->district_name;
        $desa = optional($p->village)->name ?? $p->village_name;

        // Default "kafilah": berdasarkan event_level (kalau mau), fallback provinsi
        // Anda bisa ubah logika ini sesuai kebutuhan kontingen.
        $kafilah = $prov ?: '–';

        // TTL
        $ttlDate = $p->date_of_birth ? $p->date_of_birth->format('d F Y') : '-';
        $ttl = trim(($p->place_of_birth ?? '-') . ', ' . $ttlDate);

        $genderText = $p->gender === 'MALE' ? 'Laki-laki' : 'Perempuan';

        // Address full (pakai accessor full_address kalau ada)
        $alamat = $p->full_address ?? ($p->fullAddress ?? ($p->address ?? '-'));

        // ====== Keikutsertaan Lomba (sesuai schema: event_branch/group/category) ======
        $branchName = optional($ep->eventBranch)->branch_name ?? (optional($ep->eventBranch)->name ?? '-');
        $groupName = optional($ep->eventGroup)->group_name ?? (optional($ep->eventGroup)->name ?? '-');
        $categoryName = optional($ep->eventCategory)->full_name ?? (optional($ep->eventCategory)->category_name ?? (optional($ep->eventCategory)->name ?? '-'));

        // ====== Status Registration (sesuai migration) ======
        $statusMap = [
            'bank_data' => 'Bank Data',
            'process' => 'Proses',
            'verified' => 'Diterima',
            'need_revision' => 'Perbaiki',
            'rejected' => 'Tolak',
            'disqualified' => 'Mundur',
        ];
        $regStatusKey = $ep->registration_status ?? 'bank_data';
        $regStatusLabel = $statusMap[$regStatusKey] ?? strtoupper($regStatusKey);

        // Umur (dikirim dari controller)
        $ageText = !is_null($ageYear) ? $ageYear . ' Th ' . $ageMonth . ' Bln ' . $ageDay . ' Hr' : null;

        // Tanggal cetak
        $printed = $printedAt ?? now();
    @endphp

    <div class="header">
        <div class="header-top">
            <div>
                <div class="title-big">BIODATA PESERTA</div>
                <div class="subtitle">
                    <strong>{{ strtoupper($p->full_name ?? '-') }}</strong><br>
                    NIK: {{ $p->nik ?? '-' }}<br>
                    @if ($ageText)
                        ({{ $ageYear }} Tahun)
                    @endif
                </div>
            </div>

            <div class="event-block">
                <div class="event-name">{{ strtoupper($eventName) }}</div>
                <div>Tingkat {{ strtoupper($eventLevel) }}</div>
                <div>Tahun {{ $eventYear }}</div>
                <div>Lokasi: {{ strtoupper($eventLoc) }}</div>
            </div>
        </div>

        <div class="header-main">
            <div class="header-main-left">
                <div><span class="label">Kafilah</span>: {{ strtoupper($kafilah) }}</div>
                <div><span class="label">Kab / Kota</span>: {{ strtoupper($kabkot ?? '-') }}</div>
            </div>
            <div class="header-main-right">
                @if ($ageText)
                    <div><span class="label">Umur</span>: {{ $ageText }}</div>
                @endif
                @if ($ep->contingent)
                    <div><span class="label">Kontingen</span>: {{ strtoupper($ep->contingent) }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="section-title">Data Pribadi</div>
    <table>
        <tr>
            <td class="field-label">Nama Lengkap</td>
            <td class="field-separator">:</td>
            <td>{{ strtoupper($p->full_name ?? '-') }}</td>
        </tr>
        <tr>
            <td class="field-label">NIK</td>
            <td class="field-separator">:</td>
            <td>{{ $p->nik ?? '-' }}</td>
        </tr>
        <tr>
            <td class="field-label">Tempat / Tgl Lahir</td>
            <td class="field-separator">:</td>
            <td>{{ $ttl }}</td>
        </tr>
        <tr>
            <td class="field-label">Jenis Kelamin</td>
            <td class="field-separator">:</td>
            <td>{{ $genderText }}</td>
        </tr>
        <tr>
            <td class="field-label">Pendidikan</td>
            <td class="field-separator">:</td>
            <td>{{ $p->education ?? '-' }}</td>
        </tr>
        <tr>
            <td class="field-label">Telp.</td>
            <td class="field-separator">:</td>
            <td>{{ $p->phone_number ?: '-' }}</td>
        </tr>
        <tr>
            <td class="field-label">Alamat</td>
            <td class="field-separator">:</td>
            <td>{{ $alamat }}</td>
        </tr>
        <tr>
            <td class="field-label">Provinsi</td>
            <td class="field-separator">:</td>
            <td>{{ $prov ?: '-' }}</td>
        </tr>
        <tr>
            <td class="field-label">Kab / Kota</td>
            <td class="field-separator">:</td>
            <td>{{ $kabkot ?: '-' }}</td>
        </tr>
        <tr>
            <td class="field-label">Kecamatan</td>
            <td class="field-separator">:</td>
            <td>{{ $kec ?: '-' }}</td>
        </tr>
        <tr>
            <td class="field-label">Desa / Nagari</td>
            <td class="field-separator">:</td>
            <td>{{ $desa ?: '-' }}</td>
        </tr>
    </table>

    <div class="section-title">Keikutsertaan Lomba</div>
    <table>
        <tr>
            <td class="field-label">Cabang</td>
            <td class="field-separator">:</td>
            <td>{{ $branchName }}</td>
        </tr>
        <tr>
            <td class="field-label">Golongan</td>
            <td class="field-separator">:</td>
            <td>{{ $groupName }}</td>
        </tr>
        <tr>
            <td class="field-label">Kategori</td>
            <td class="field-separator">:</td>
            <td>{{ $categoryName }}</td>
        </tr>
        <tr>
            <td class="field-label">Status Pendaftaran</td>
            <td class="field-separator">:</td>
            <td>
                <span class="badge">{{ $regStatusLabel }}</span>
                @if ($ep->register_at)
                    <span style="font-size:10px;">&nbsp;({{ optional($ep->register_at)->format('d F Y H:i') }})</span>
                @endif
            </td>
        </tr>

        @if (!empty($ep->registration_notes))
            <tr>
                <td class="field-label">Catatan</td>
                <td class="field-separator">:</td>
                <td>{{ $ep->registration_notes }}</td>
            </tr>
        @endif
    </table>

    <div class="section-title">Data Rekening</div>
    <table>
        <tr>
            <td class="field-label">Nama Bank</td>
            <td class="field-separator">:</td>
            <td>{{ $p->bank_name ?: '-' }}</td>
        </tr>
        <tr>
            <td class="field-label">No. Rekening</td>
            <td class="field-separator">:</td>
            <td>{{ $p->bank_account_number ?: '-' }}</td>
        </tr>
        <tr>
            <td class="field-label">Atas Nama</td>
            <td class="field-separator">:</td>
            <td>{{ strtoupper($p->bank_account_name ?: '-') }}</td>
        </tr>
    </table>

    <div class="footer">
        Tanggal Cetak: {{ $printed->format('d F Y') }}
    </div>
</body>

</html>
