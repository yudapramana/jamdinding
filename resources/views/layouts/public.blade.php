<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Detail Peserta MTQ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f4f6f9;
        }

        .page-title {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .card-header {
            background: #ffffff;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .info-table th {
            width: 32%;
            font-weight: 500;
            color: #6c757d;
            vertical-align: top;
            padding: .45rem .75rem;
        }

        .info-table td {
            padding: .45rem .75rem;
        }

        .photo-wrapper {
            max-width: 160px;
            margin: 0 auto;
        }

        .photo-wrapper img {
            width: 100%;
            aspect-ratio: 3 / 4;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }

        .file-row {
            font-size: 0.875rem;
        }
    </style>
</head>

<body>

    <div class="container my-4" style="max-width: 980px;">

        <!-- HEADER -->
        <div class="card shadow-sm mb-3">
            <div class="card-body py-3 d-flex justify-content-between align-items-center">
                <div>
                    <div class="page-title">
                        <i class="fa-solid fa-id-card me-2 text-success"></i>
                        Detail Peserta
                    </div>
                    <div class="text-muted small">
                        {{ $ep->event?->event_name }} {{ $ep->event?->event_year }}
                    </div>
                </div>

                <span class="badge bg-success px-3 py-2">
                    {{ $ep->eventCategory?->category_name ?? '-' }}
                </span>
            </div>
        </div>

        <div class="row g-3">

            <!-- BIODATA -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <i class="fa-solid fa-user me-1"></i> Biodata Peserta
                    </div>

                    <div class="card-body p-0">
                        <table class="table table-borderless info-table mb-0">
                            <tbody>
                                <tr>
                                    <th>Nama</th>
                                    <td class="fw-semibold text-uppercase">
                                        {{ $ep->participant?->full_name ?? '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>NIK</th>
                                    <td class="font-monospace">
                                        {{ $ep->participant?->nik ?? '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Tempat / Tgl Lahir</th>
                                    <td>
                                        {{ strtoupper($ep->participant?->place_of_birth ?? '-') }},
                                        <span class="fw-semibold text-danger">
                                            {{ optional($ep->participant?->date_of_birth)->format('d-m-Y') ?? '-' }}
                                        </span>
                                        @if ($ep->age_year !== null)
                                            <span class="text-muted">
                                                ({{ $ep->age_year }}T {{ $ep->age_month }}B {{ $ep->age_day }}H)
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Jenis Kelamin</th>
                                    <td>
                                        {{ $ep->participant?->gender === 'MALE' ? 'LAKI-LAKI' : 'PEREMPUAN' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Cabang / Golongan</th>
                                    <td>
                                        {{ $ep->eventGroup?->full_name ?? ($ep->eventBranch?->full_name ?? '-') }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Kontingen</th>
                                    <td class="fw-semibold text-success">
                                        {{ $ep->contingent ?? ($ep->participant?->district_name ?? ($ep->participant?->regency_name ?? ($ep->participant?->province_name ?? '-'))) }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Alamat</th>
                                    <td>{{ $ep->participant?->address ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Pendidikan</th>
                                    <td>{{ $ep->participant?->education ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Rekening</th>
                                    <td>
                                        {{ $ep->participant?->bank_account_number ?? '-' }}<br>
                                        {{ $ep->participant?->bank_account_name ?? '-' }}<br>
                                        <span class="text-muted">{{ $ep->participant?->bank_name ?? '-' }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Tgl Terbit KTP</th>
                                    <td class="fw-semibold text-danger">
                                        {{ optional($ep->participant?->tanggal_terbit_ktp)->format('d-m-Y') ?? '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Tgl Terbit KK</th>
                                    <td class="fw-semibold text-danger">
                                        {{ optional($ep->participant?->tanggal_terbit_kk)->format('d-m-Y') ?? '-' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- FOTO & BERKAS -->
            <div class="col-lg-4">

                <!-- FOTO -->
                <div class="card shadow-sm mb-3">
                    <div class="card-header text-center">
                        <i class="fa-solid fa-image me-1"></i> Foto Peserta
                    </div>

                    <div class="card-body text-center">
                        <div class="photo-wrapper">
                            @if ($ep->participant?->photo_url)
                                <img src="{{ $ep->participant->photo_url }}" alt="Foto Peserta">
                            @else
                                <div class="text-muted small">Tidak ada foto</div>
                            @endif
                        </div>
                    </div>
                </div>



            </div>
        </div>

        <!-- FOOTER -->
        <div class="text-center text-muted small mt-4">
            © {{ date('Y') }} Kementerian Agama – Sistem Informasi MTQ
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
