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
        }

        .title-block {
            text-align: left;
        }

        .title-big {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .subtitle {
            font-size: 11px;
            margin-top: 2px;
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

        .footer {
            margin-top: 14px;
            font-size: 10px;
            text-align: right;
        }
    </style>
</head>

<body>
    @php
        $p = $participant;
        $e = $event;
        $kabkota = optional($p->regency)->name;
        $prov = optional($p->province)->name;
        $kafilah = $prov ?: '–';
        $ttl = trim(($p->place_of_birth ?? '') . ', ' . optional($p->date_of_birth)->format('d F Y'));
        $genderText = $p->gender === 'MALE' ? 'Laki-laki' : 'Perempuan';
        $eventName = $e ? $e->nama_event ?? ($e->name ?? '-') : '-';
        $eventLevel = $e->tingkat_event ?? '-';
        $eventCity = $e->lokasi_event ?? (optional($e->regency)->name ?? '-');
        $eventYear = optional($e->tanggal_mulai)->format('Y') ?? now()->year;
    @endphp

    <div class="header">
        <div class="header-top">
            <div class="title-block">
                <div class="title-big">BIODATA PESERTA</div>
                <div class="subtitle">
                    {{ strtoupper($p->full_name) }}<br>
                    NIK: {{ $p->nik }}<br>
                    @if (!is_null($ageYear))
                        ({{ $ageYear }} Tahun)
                    @endif
                </div>
            </div>
            <div class="event-block">
                <div class="event-name">{{ strtoupper($eventName) }}</div>
                <div>Tingkat {{ strtoupper($eventLevel) }}</div>
                <div>Tahun {{ $eventYear }}</div>
                <div>di {{ strtoupper($eventCity) }}</div>
            </div>
        </div>

        <div class="header-main">
            <div class="header-main-left">
                <div><span class="label">Kafilah</span>: {{ strtoupper($kafilah) }}</div>
                <div><span class="label">Kab / Kota</span>: {{ strtoupper($kabkota ?? '-') }}</div>
            </div>
            <div class="header-main-right">
                @if (!is_null($ageYear))
                    <div>
                        <span class="label">Umur</span>:
                        {{ $ageYear }} Th {{ $ageMonth }} Bln {{ $ageDay }} Hr
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="section-title">Data Pribadi</div>
    <table>
        <tr>
            <td class="field-label">Nama Lengkap</td>
            <td class="field-separator">:</td>
            <td>{{ strtoupper($p->full_name) }}</td>
        </tr>
        <tr>
            <td class="field-label">Kafilah</td>
            <td class="field-separator">:</td>
            <td>{{ strtoupper($kafilah) }}</td>
        </tr>
        <tr>
            <td class="field-label">Kab / Kota</td>
            <td class="field-separator">:</td>
            <td>{{ strtoupper($kabkota ?? '-') }}</td>
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
            <td class="field-label">Telp.</td>
            <td class="field-separator">:</td>
            <td>{{ $p->phone_number ?: '-' }}</td>
        </tr>
        <tr>
            <td class="field-label">Alamat</td>
            <td class="field-separator">:</td>
            <td>{{ $p->full_address ?? ($p->address ?? '-') }}</td>
        </tr>
    </table>

    <div class="section-title">Keikutsertaan Lomba</div>
    <table>
        <tr>
            <td class="field-label">Cabang</td>
            <td class="field-separator">:</td>
            <td>{{ optional($eventParticipant->competitionBranch)->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="field-label">Status Pendaftaran</td>
            <td class="field-separator">:</td>
            <td>{{ strtoupper($eventParticipant->status_pendaftaran ?? 'BANKDATA') }}</td>
        </tr>
        @if ($eventParticipant->registration_notes)
            <tr>
                <td class="field-label">Catatan</td>
                <td class="field-separator">:</td>
                <td>{{ $eventParticipant->registration_notes }}</td>
            </tr>
        @endif
    </table>

    <div class="section-title">Data Rekening</div>
    <table>
        <tr>
            <td class="field-label">Rekening No</td>
            <td class="field-separator">:</td>
            <td>{{ $p->bank_account_number ?: '-' }}</td>
        </tr>
        <tr>
            <td class="field-label">Rekening Bank</td>
            <td class="field-separator">:</td>
            <td>{{ $p->bank_name ?: '-' }}</td>
        </tr>
        <tr>
            <td class="field-label">Rekening Nama</td>
            <td class="field-separator">:</td>
            <td>{{ strtoupper($p->bank_account_name ?: '-') }}</td>
        </tr>
    </table>

    <div class="footer">
        Tanggal Cetak: {{ $printedAt->format('d F Y') }}
    </div>
</body>

</html>
