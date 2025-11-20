@php use Illuminate\Support\Str; @endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Status Pengisian SIGARDA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Bootstrap 3 agar selaras dengan halaman lain --}}
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <style>
        table.table-smaller {
            font-size: 12px;
        }

        .label-status {
            font-size: 90%;
        }

        .toolbar {
            margin-bottom: 12px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="page-header" style="margin-top:20px;">
            <h3 class="text-muted">Kementerian Agama Kab. Pesisir Selatan</h3>
            <h2 style="margin-top:5px;">Status Pengisian SIGARDA</h2>
            <p class="lead" style="margin-bottom:0;">
                Total: <span class="badge">{{ $total }}</span>
                &middot; Sudah isi: <span class="badge">{{ $done }}</span>
                &middot; Belum isi: <span class="badge">{{ $pending }}</span>
            </p>
        </div>

        {{-- Tombol filter --}}
        <div class="toolbar">
            <div class="btn-group">
                <a href="{{ url('/sigarda-status') }}" class="btn btn-default {{ empty($filter) ? 'btn-primary' : '' }}">
                    Semua <span class="badge">{{ $total }}</span>
                </a>
                <a href="{{ url('/sigarda-status?status=done') }}" class="btn btn-default {{ $filter === 'done' ? 'btn-primary' : '' }}">
                    Sudah Isi <span class="badge">{{ $done }}</span>
                </a>
                <a href="{{ url('/sigarda-status?status=pending') }}" class="btn btn-default {{ $filter === 'pending' ? 'btn-primary' : '' }}">
                    Belum Isi <span class="badge">{{ $pending }}</span>
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-condensed table-striped table-hover table-smaller">
                <thead>
                    <tr>
                        <th style="width:60px;">#</th>
                        <th>Nama</th>
                        <th>NIP</th>
                        <th>Satuan Kerja</th>
                        <th style="width:140px;" class="text-center">Status SIGARDA</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $idx => $e)
                        <tr>
                            <td>{{ ($employees->currentPage() - 1) * $employees->perPage() + $idx + 1 }}</td>
                            <td>{{ $e->nama }}</td>
                            <td>{{ $e->nip }}</td>
                            <td>{{ $e->satuan_kerja }}</td>
                            <td class="text-center">
                                @if ($e->has_docs)
                                    <span class="label label-success label-status">Sudah Isi</span>
                                @else
                                    <span class="label label-default label-status">Belum Isi</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">Tidak ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="text-center">
            {{ $employees->links() }}
        </div>

        <footer class="footer">
            <p>&copy; {{ date('Y') }} Pranata Komputer YD</p>
        </footer>
    </div>
</body>

</html>
