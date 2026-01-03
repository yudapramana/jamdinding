<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmpDocumentController;
use App\Http\Controllers\Admin\MasterController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\PrintController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VervalLogController;
use App\Http\Controllers\API\DocumentLogController;
use App\Http\Controllers\API\WorkUnitController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\OauthController;
use App\Http\Controllers\PublicDocController;
use App\Http\Controllers\SocialiteController;
use App\Http\Controllers\User\DocumentController;
use App\Http\Controllers\User\EmployeeDocumentController;
use App\Http\Controllers\WhatsAppController;
use App\Models\DocType;
use App\Models\EmpDocument;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;
use Illuminate\Support\Facades\Storage;
use Yaza\LaravelGoogleDriveStorage\Gdrive;
use App\Http\Controllers\Auth\PasswordResetWhatsappController;
use App\Models\Event;
use App\Models\VervalLog;
use Illuminate\Http\Request;
use App\Http\Controllers\Auth\CaptchaController;

Route::get('/health', fn() => 'OK');
Route::get('/log-test', function () {
    \Log::error('WEB LOG OK');
    abort(500, 'TEST');
});


Route::get('/env-check', function () {
    return response()->json([
        'env' => app()->environment(),
        'debug' => config('app.debug'),
        'log_channel' => config('logging.default'),
        'log_path' => storage_path('logs/laravel.log'),
    ]);
});

// Route::get('/log-test', function () {
//     \Log::error('LOG TEST FROM PRODUCTION');
//     abort(500, 'TEST ERROR');
// });

Route::get('/test-log', function () {
    Log::info('TEST LOG INFO', [
        'time' => now()->toDateTimeString(),
        'env'  => app()->environment(),
        'user' => auth()->id(),
    ]);

    Log::warning('TEST LOG WARNING');
    Log::error('TEST LOG ERROR');

    return response()->json([
        'success' => true,
        'message' => 'Log berhasil ditulis. Silakan cek storage/logs/laravel.log'
    ]);
});



/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Event\RequestEvent;




Route::get('/captcha', [CaptchaController::class, 'generate'])->name('captcha');

Route::get('/testgrup', function () {

    $filePath = database_path('seeders/data/competition_branches.csv');

    // Pastikan file CSV ada
    if (!file_exists($filePath)) {
        $this->command->error("CSV file not found at: {$filePath}");
        return;
    }

    // Ambil data ID dari tabel lain untuk referensi cepat (Wajib)
    $groups = DB::table('master_competition_groups')->pluck('id', 'name')->toArray();
    $categories = DB::table('master_competition_categories')->pluck('id', 'name')->toArray();

    $dataToInsert = [];
    $order = 1;

    if (($handle = fopen($filePath, 'r')) !== FALSE) {
        // Ambil header baris pertama untuk identifikasi kolom
        $headers = fgetcsv($handle, 1000, ';'); 
        
        // Tentukan index berdasarkan header (asumsi urutan header sama dengan input)
        $groupIndex = array_search('Cabang', $headers);
        $categoryIndex = array_search('Kategori', $headers);
        $typeIndex = array_search('Jenis', $headers);
        $branchNameIndex = array_search('Golongan', $headers);

        while (($row = fgetcsv($handle, 1000, ';')) !== FALSE) {
            // Pastikan baris memiliki jumlah kolom yang diharapkan
            if (count($row) < 1) continue; 

            $groupName = $row[$groupIndex];
            return $groupName;
            $categoryName = $row[$categoryIndex];
            $type = $row[$typeIndex]; // 'Putra' atau 'Putri'
            $branchName = $row[$branchNameIndex]; // Nama Golongan

            $groupId = $groups[$groupName] ?? null;
            $categoryId = $categories[$categoryName] ?? null;

            // Validasi: Pastikan group dan category ada
            if (is_null($groupId) || is_null($categoryId)) {
                $this->command->warn("Group or Category not found for: $groupName - $categoryName (Skipping)");
                continue;
            }
            
            // Logika format: 'Beregu' -> grup, selain itu -> individu
            $format = (strtolower($categoryName) === 'beregu') ? 'grup' : 'individu';
            
            // Logika untuk membuat kode unik (Contoh: FAHM_AL_QURAN_BEREGU_PUTRA)
            $baseCode = strtoupper(str_replace([' ', '+', '-', '\''], '_', $branchName));
            $code = trim(preg_replace('/__+/', '_', $baseCode), '_');

            // Pastikan tipe adalah salah satu dari ENUM
            $validType = in_array($type, ['Putra', 'Putri']) ? $type : 'Putra';

            $dataToInsert[] = [
                'code' => $code,
                'master_group_id' => $groupId,
                'master_category_id' => $categoryId,
                'type' => $validType,
                'format' => $format,
                'name' => $branchName,
                'order_number' => $order++,
                'description' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        fclose($handle);
    }
});

Route::get('/api/events/by-key/{key}', function (Request $request, $key) {

    $event = \App\Models\Event::where('event_key', $key)->first();

    if (!$event) {
        return response()->json([
            'success' => false,
            'message' => 'Event tidak ditemukan.',
            'data' => null
        ], 404);
    }


    return response()->json([
        'success' => true,
        'message' => 'Event ditemukan.',
        'data' => $event
    ], 200);
});

Route::get('/sigarda-employees-status', function (Request $request) {
    $filter = $request->query('status'); // null | 'done' | 'pending'

    // Ringkasan jumlah
    $total = DB::table('employees')->where('employment_category', 'ACTIVE')->count();

    $done = DB::table('employees as e')
        ->where('e.employment_category', 'ACTIVE') // ✅ hanya pegawai aktif
        ->whereExists(function ($q) {
            $q->select(DB::raw(1))
              ->from('emp_documents as d')
              ->whereColumn('d.id_employee', 'e.id')
              ->whereNull('d.deleted_at');
        })
        ->count();

    $pending = $total - $done;

    // Query utama: left join + hitung dokumen aktif per pegawai
    $base = DB::table('employees as e')
        ->leftJoin('emp_documents as d', function ($j) {
            $j->on('d.id_employee', '=', 'e.id')
              ->whereNull('d.deleted_at');
        })
        ->select(
            'e.id',
            'e.full_name',
            'e.nip',
            'e.job_title',
            DB::raw('COUNT(d.id) as docs_count')
        )
        ->where('e.employment_category', 'ACTIVE') // ✅ hanya pegawai aktif
        ->groupBy('e.id', 'e.full_name', 'e.nip', 'e.job_title')
        ->orderBy('e.nip', 'asc'); // ✅ urutkan berdasarkan NIP naik

    // Terapkan filter status
    if ($filter === 'done') {
        $base->havingRaw('COUNT(d.id) > 0');
    } elseif ($filter === 'pending') {
        $base->havingRaw('COUNT(d.id) = 0');
    }

    $rows = $base->orderBy('e.full_name', 'asc')->get();

    // Build HTML sederhana (tanpa Blade)
    $currentUrl = url('/sigarda-employees-status');
    $activeAll = empty($filter) ? 'btn-primary' : 'btn-default';
    $activeDone = $filter === 'done' ? 'btn-primary' : 'btn-default';
    $activePending = $filter === 'pending' ? 'btn-primary' : 'btn-default';

    $htmlRows = '';
    $no = 1;
    foreach ($rows as $r) {
        $statusLabel = ($r->docs_count > 0)
            ? '<span class="label label-success">Sudah Isi</span>'
            : '<span class="label label-default">Belum Isi</span>';

        $htmlRows .= '<tr>'
            . '<td style="width:60px;">' . $no++ . '</td>'
            . '<td>' . e($r->full_name) . '</td>'
            . '<td>' . e($r->nip) . '</td>'
            . '<td>' . e($r->job_title) . '</td>'
            . '<td class="text-center" style="width:140px;">' . $statusLabel . '</td>'
            . '</tr>';
    }

    $html = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Status Pengisian SIGARDA — Employees</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 3 agar selaras dengan halaman yang sudah ada -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <style>
        table.table-smaller { font-size: 12px; }
        .toolbar { margin: 12px 0; }
        .page-header { margin-top: 20px; }
    </style>
</head>
<body>
<div class="container">
    <div class="page-header">
        <h3 class="text-muted">Kementerian Agama Kab. Pesisir Selatan</h3>
        <h2 style="margin-top:5px;">Status Pengisian SIGARDA (Employees)</h2>
        <p class="lead" style="margin-bottom:0;">
            Total: <span class="badge">{$total}</span>
            &middot; Sudah isi: <span class="badge">{$done}</span>
            &middot; Belum isi: <span class="badge">{$pending}</span>
        </p>
    </div>

    <div class="toolbar">
        <div class="btn-group">
            <a href="{$currentUrl}" class="btn {$activeAll}">
                Semua <span class="badge">{$total}</span>
            </a>
            <a href="{$currentUrl}?status=done" class="btn {$activeDone}">
                Sudah Isi <span class="badge">{$done}</span>
            </a>
            <a href="{$currentUrl}?status=pending" class="btn {$activePending}">
                Belum Isi <span class="badge">{$pending}</span>
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-condensed table-striped table-hover table-smaller">
            <thead>
                <tr>
                    <th style="width:60px;">#</th>
                    <th>Full Name</th>
                    <th>NIP</th>
                    <th>Job Title</th>
                    <th class="text-center" style="width:140px;">Status SIGARDA</th>
                </tr>
            </thead>
            <tbody>
                {$htmlRows}
            </tbody>
        </table>
    </div>

    <footer class="footer">
        <p>&copy; {date('Y')} Pranata Komputer YD</p>
    </footer>
</div>
</body>
</html>
HTML;

    return response($html);
})->middleware(['auth']);



Route::get('/count-unique-employees', function () {
    $uniqueEmployees = DB::table('emp_documents')
        ->distinct('id_employee')
        ->count('id_employee');

    return response()->json([
        'unique_employee_count' => $uniqueEmployees
    ]);
})->middleware(['auth']);

Route::get('/update-progress-dokumen', function (Request $request) {
    // --- Opsi via query ---
    $onlyFlagged      = filter_var($request->input('only_flagged', 'true'), FILTER_VALIDATE_BOOLEAN);
    $employmentStatus = $request->input('employment_status'); // 'PNS' / 'PPPK' (opsional)
    $chunkSize        = max(50, (int) $request->input('chunk', 300)); // default 300, minimal 50

    // --- Optimasi RAM ---
    DB::disableQueryLog();

    $q = Employee::query()
        ->select(['id', 'docs_progress_state', 'employment_status']); // ambil kolom minimal

    if ($onlyFlagged) {
        $q->where('docs_progress_state', true);
    }
    if (!empty($employmentStatus)) {
        $q->where('employment_status', $employmentStatus);
    }

    $startedAt = microtime(true);
    $updated   = 0;
    $failed    = 0;
    $seen      = 0;

    $q->chunkById($chunkSize, function ($employees) use (&$updated, &$failed, &$seen) {
        foreach ($employees as $emp) {
            $seen++;
            try {
                // Mengakses accessor akan menghitung & saveQuietly() sesuai kode Anda
                $val = $emp->progress_dokumen;
                $updated++;
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('Recalc progress_dokumen gagal', [
                    'employee_id' => $emp->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        }
    });

    $elapsed = round(microtime(true) - $startedAt, 3);

    return response()->json([
        'success'           => true,
        'message'           => 'Update progress_dokumen selesai.',
        'only_flagged'      => $onlyFlagged,
        'employment_status' => $employmentStatus,
        'chunk'             => $chunkSize,
        'scanned'           => $seen,
        'updated'           => $updated,
        'failed'            => $failed,
        'elapsed_sec'       => $elapsed,
    ]);
})->middleware(['auth']);


Route::get('/auto-verval', function (Request $request) {
    // ===== Konfigurasi =====
    $assigneeId  = 1472; // user target auto-assign & verified_by
    $take        = (int) $request->input('count', 5);

    $idWorkUnit  = $request->input('id_work_unit'); // nullable
    $lockTtlMin  = 30;
    $nipPriority = '199407292022031002'; // tetap pakai prioritas NIP seperti claim()
    $now         = now();

    // ===== Langkah 1: Ambil kandidat dokumen (mirip claim) & lock baris =====
    $docs = DB::transaction(function () use ($take, $idWorkUnit, $now, $nipPriority, $assigneeId, $lockTtlMin) {
        $q = EmpDocument::query()
            ->where('status', 'Pending')
            ->where(function ($q) use ($now) {
                $q->whereNull('assigned_to')
                  ->orWhere('lock_expires_at', '<', $now);
            });

        if (!empty($idWorkUnit)) {
            $q->whereHas('employee', function ($sub) use ($idWorkUnit) {
                $sub->where('id_work_unit', $idWorkUnit);
            });
        }

        // Kunci baris kandidat agar aman dari double-claim
        $q->lockForUpdate();

        // Prioritas NIP tertentu, lalu FIFO stabil
        $q->orderByRaw(
            "CASE WHEN EXISTS (
                SELECT 1 FROM employees e
                WHERE e.id = emp_documents.id_employee
                  AND e.nip = ?
            ) THEN 0 ELSE 1 END",
            [$nipPriority]
        )
        ->orderBy('created_at', 'asc')
        ->orderBy('id', 'asc')
        ->limit($take);

        $docs = $q->get();
        if ($docs->isEmpty()) {
            return collect(); // biar mudah ditangani di luar
        }

        $expiresAt = (clone $now)->addMinutes($lockTtlMin);
        foreach ($docs as $doc) {
            $doc->assigned_to     = $assigneeId;
            $doc->assigned_at     = $now;
            $doc->lock_expires_at = $expiresAt;
            $doc->save();
        }

        // siapkan relasi yang dibutuhkan untuk langkah verifikasi
        $docs->load(['employee.user', 'docType']);

        return $docs;
    });

    if ($docs->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'Tidak ada dokumen Pending yang tersedia untuk auto-verval.',
            'claimed' => 0,
            'processed' => 0,
            'approved_ids' => [],
            'skipped_ids'  => [],
        ], 404);
    }

    // ===== Langkah 2: Verifikasi tiap dokumen (Approved) =====
    $approvedIds = [];
    $skippedIds  = [];
    foreach ($docs as $doc) {
        // Skip jika ternyata status sudah bukan Pending (perlindungan ekstra)
        if (in_array($doc->status, ['Approved', 'Rejected'])) {
            $skippedIds[] = $doc->id;
            continue;
        }

        // ==== Bagian "move file" jika Approved (mengikuti verify()) ====
        $path = ltrim($doc->file_path ?? '', '/'); // ex: documents/1994.../FILE.pdf
        if (!empty($path)) {
            $srcDisk = Storage::disk('privatedisk');
            $dstDisk = Storage::disk('gcs');

            $srcExists = $srcDisk->exists($path);
            $dstExists = $dstDisk->exists($path);

            if (!$dstExists) {
                if (!$srcExists) {
                    // Jika sumber tidak ada, tandai skip (atau bisa diputuskan Reject)
                    $skippedIds[] = $doc->id;
                    continue;
                }

                $stream = $srcDisk->readStream($path);
                if ($stream === false) {
                    $skippedIds[] = $doc->id;
                    continue;
                }

                if ($dstDisk->exists($path)) {
                    $dstDisk->delete($path);
                }

                $dstOk = $dstDisk->writeStream($path, $stream);
                if (is_resource($stream)) fclose($stream);

                if ($dstOk === false) {
                    $skippedIds[] = $doc->id;
                    continue;
                }
            }

            if ($srcExists) {
                $srcDisk->delete($path);
            }
        }

        // ==== Update status + log (mengikuti verify()) ====
        DB::transaction(function () use ($doc, $assigneeId) {
            $doc->status      = 'Approved';
            $doc->verif_notes = 'Auto-approved via /auto-system-verval';
            $doc->save();

            VervalLog::create([
                'id_document'   => $doc->id,
                'verval_status' => 'Approved',
                'verified_by'   => $assigneeId,
                'verif_notes'   => $doc->verif_notes,
                'created_at'    => now(),
            ]);

            // Update state employee & user (sama seperti verify())
            $employee = $doc->employee;
            if ($employee) {
                $employee->update(['docs_progress_state' => true]);
                if ($employee->user) {
                    $employee->user->update(['docs_update_state' => true]);
                }
            }
        });

        $approvedIds[] = $doc->id;
    }

    return response()->json([
        'success'      => true,
        'message'      => 'Auto-verval selesai diproses.',
        'claimed'      => $docs->count(),
        'processed'    => count($approvedIds) + count($skippedIds),
        'approved_ids' => $approvedIds,
        'skipped_ids'  => $skippedIds, // contoh: file sumber hilang / gagal stream, atau status sudah non-Pending
    ]);
})->middleware(['auth']);


Route::get('/password/wa/request', [PasswordResetWhatsappController::class, 'showRequestForm'])->name('password.wa.request');
Route::get('/password/wa/reset',   [PasswordResetWhatsappController::class, 'showResetForm'])->name('password.wa.reset');
Route::post('/password/wa/reset',  [PasswordResetWhatsappController::class, 'resetPassword'])->name('password.wa.reset.submit');


Route::post('/wa/send', [WhatsAppController::class, 'send']);
Route::get('/wa/sendget', [WhatsAppController::class, 'sendGet']);

// Route::get('/verval-log-champion', function () {

//     // Query: hitung jumlah verval per user (verifikator)
//     $logs = DB::select("
//         SELECT 
//             u.id AS user_id,
//             u.name,
//             COUNT(v.id) AS total_vervals
//         FROM verval_logs v
//         JOIN users u ON u.id = v.verified_by
//         GROUP BY u.id, u.name
//         ORDER BY total_vervals DESC
//     ");

//     // Bangun HTML tampilan klasemen
//     $html = "
//     <html>
//     <head>
//         <title>Klasemen Verval Log Champion</title>
//         <style>
//             body {
//                 font-family: Arial, sans-serif;
//                 background: #f7f8fa;
//                 padding: 40px;
//             }
//             h2 {
//                 text-align: center;
//                 margin-bottom: 25px;
//                 color: #333;
//             }
//             table {
//                 border-collapse: collapse;
//                 margin: 0 auto;
//                 background: #fff;
//                 width: 80%;
//                 box-shadow: 0 2px 8px rgba(0,0,0,0.1);
//                 border-radius: 8px;
//                 overflow: hidden;
//             }
//             th, td {
//                 padding: 12px 16px;
//                 text-align: left;
//                 border-bottom: 1px solid #e0e0e0;
//             }
//             th {
//                 background-color: #007B5E;
//                 color: #fff;
//                 font-weight: bold;
//             }
//             tr:nth-child(even) {
//                 background-color: #f9f9f9;
//             }
//             tr:hover {
//                 background-color: #f1fff3;
//             }
//             .rank {
//                 text-align: center;
//                 font-weight: bold;
//             }
//             .champion {
//                 background-color: gold !important;
//                 font-weight: bold;
//                 color: #000;
//             }
//         </style>
//     </head>
//     <body>
//         <h2>🏆 Klasemen Verval Log Champion</h2>
//         <table>
//             <thead>
//                 <tr>
//                     <th>Peringkat</th>
//                     <th>Nama Verifikator</th>
//                     <th>Total Verval</th>
//                 </tr>
//             </thead>
//             <tbody>
//     ";

//     $rank = 1;
//     foreach ($logs as $row) {
//         $highlight = $rank === 1 ? 'class="champion"' : '';
//         $html .= "
//             <tr {$highlight}>
//                 <td class='rank'>{$rank}</td>
//                 <td>{$row->name}</td>
//                 <td>{$row->total_vervals}</td>
//             </tr>
//         ";
//         $rank++;
//     }

//     $html .= "
//             </tbody>
//         </table>
//     </body>
//     </html>
//     ";

//     return $html;
// });


Route::get('/show-verval-champion-hide', function () {

    $counts = DB::select("
        SELECT 
            u.id AS user_id,
            u.name,
            COUNT(d.id) AS total_documents
        FROM emp_documents d
        JOIN users u ON u.id = d.assigned_to
        GROUP BY u.id, u.name
        ORDER BY total_documents DESC
    ");

    // Bangun HTML
    $html = "
    <html>
    <head>
        <title>Klasemen Verval Champion</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #f9f9f9;
                padding: 40px;
            }
            h2 {
                text-align: center;
                margin-bottom: 20px;
                color: #333;
            }
            table {
                border-collapse: collapse;
                margin: 0 auto;
                background: #fff;
                box-shadow: 0 2px 6px rgba(0,0,0,0.1);
                border-radius: 8px;
                overflow: hidden;
                width: 70%;
            }
            th, td {
                border-bottom: 1px solid #ddd;
                padding: 12px 16px;
                text-align: left;
            }
            th {
                background-color: #4CAF50;
                color: white;
            }
            tr:nth-child(even) {
                background-color: #f2f2f2;
            }
            tr:hover {
                background-color: #e6ffe6;
            }
            .rank {
                text-align: center;
                font-weight: bold;
            }
            .champion {
                background-color: gold !important;
                color: #000;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <h2>🏆 Klasemen Verval Champion</h2>
        <table>
            <thead>
                <tr>
                    <th>Peringkat</th>
                    <th>Nama</th>
                    <th>Total Dokumen</th>
                </tr>
            </thead>
            <tbody>
    ";

    $rank = 1;
    foreach ($counts as $row) {
        $highlight = $rank === 1 ? 'class="champion"' : '';
        $html .= "
            <tr {$highlight}>
                <td class='rank'>{$rank}</td>
                <td>{$row->name}</td>
                <td>{$row->total_documents}</td>
            </tr>
        ";
        $rank++;
    }

    $html .= "
            </tbody>
        </table>
    </body>
    </html>
    ";

    return $html;
});



Route::get('/show-pending-documents', function() {

    $rows = DB::table('work_units as wu')
        ->leftJoin('employees as e', function ($join) {
            $join->on('e.id_work_unit', '=', 'wu.id')
                ->whereNull('e.deleted_at');
        })
        ->leftJoin('emp_documents as d', function ($join) {
            $join->on('d.id_employee', '=', 'e.id')
                ->whereNull('d.deleted_at')
                ->where('d.status', '=', 'Pending');
        })
        ->whereNull('wu.deleted_at')
        ->groupBy('wu.id', 'wu.unit_code', 'wu.unit_name')
        ->orderBy('wu.unit_code') // atau unit_name
        ->select([
            'wu.id',
            'wu.unit_code',
            'wu.unit_name',
            DB::raw('COUNT(d.id) AS pending_count'),
        ])
        ->get();

    return $rows;
});


Route::get('oauth/google', [OauthController::class, 'redirectToProvider'])->name('oauth.google');  
Route::get('oauth/google/callback', [OauthController::class, 'handleProviderCallback'])->name('oauth.google.callback');

// Untuk redirect ke Google
Route::get('/login/google/redirect', [SocialiteController::class, 'redirect'])
    ->name('google.redirect');

// Untuk callback dari Google
Route::get('/login/google/callback', [SocialiteController::class, 'callback'])
    ->name('google.callback');

// Route::middleware('auth')->group(function () {
//     Route::get('/secure/documents/{nip}/{filename}', [PublicDocController::class, 'stream'])
//         ->where(['nip' => '\d+', 'filename' => '[^/]+'])
//         ->name('secure.docs.stream');
// });

Route::middleware('auth')->group(function () {
    Route::get('/secure/documents/{participant}/{filename}', [PublicDocController::class, 'stream'])
        ->where([
            'participant' => '\d+',
            'filename' => '[^/]+'
        ])
        ->name('secure.docs.stream');
});



Route::get('/get-gdrive-file', function() {
    $path = '199407292022031002/AKYBS_199407292022031002.pdf';
    $data = Gdrive::get($path); // ->file (binary), ->ext (mime), ->name (opsional)

    // Pastikan mime-nya benar
    $mime = ($data->ext && str_contains($data->ext, '/'))
        ? $data->ext
        : 'application/pdf';

    return Response::make($data->file, 200, [
        'Content-Type'              => $mime,                 // penting: application/pdf
        'Content-Disposition'       => 'inline; filename="AKYBS_199407292022031002.pdf"',
        'Content-Transfer-Encoding' => 'binary',
        'Accept-Ranges'             => 'bytes',
        'Cache-Control'             => 'private, max-age=3600',
    ]);
});




Route::get('/show-duplicates', function(){
   
    
        // Ambil daftar file_name yang duplikat (belum soft-deleted)
        $dups = DB::table('emp_documents')
            ->select('file_name')
            ->whereNull('deleted_at')
            ->groupBy('file_name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('file_name');

       return $dups;
});


Route::get('/delete-duplicates', function(){
   
    DB::transaction(function () {
        // Ambil daftar file_name yang duplikat (belum soft-deleted)
        $dups = DB::table('emp_documents')
            ->select('file_name')
            ->whereNull('deleted_at')
            ->groupBy('file_name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('file_name');

        foreach ($dups as $name) {
            // Urutkan terbaru dulu (created_at desc), biarkan yang pertama tetap ada
            $rows = EmpDocument::where('file_name', $name)
                ->whereNull('deleted_at')
                ->orderByDesc('created_at')
                ->orderByDesc('id') // tie-breaker
                ->get();

            // Buang elemen pertama (terbaru) → sisanya di-soft delete
            $rows->skip(1)->each->delete();
        }
    });
});


Route::get('/delete-duplicates', function(){
   
    DB::transaction(function () {
        // Ambil daftar file_name yang duplikat (belum soft-deleted)
        $dups = DB::table('emp_documents')
            ->select('file_name')
            ->whereNull('deleted_at')
            ->groupBy('file_name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('file_name');

        foreach ($dups as $name) {
            // Urutkan terbaru dulu (created_at desc), biarkan yang pertama tetap ada
            $rows = EmpDocument::where('file_name', $name)
                ->whereNull('deleted_at')
                ->orderByDesc('created_at')
                ->orderByDesc('id') // tie-breaker
                ->get();

            // Buang elemen pertama (terbaru) → sisanya di-soft delete
            $rows->skip(1)->each->delete();
        }
    });
});




Route::get('/checkfile', function(){
    $nip = '199407292022031002';
    $user = User::where('username', $nip)->with('employee')->first();
    $employee = $user->employee;


    $document = EmpDocument::where('id_employee', $employee->id)->first();
    $log = '';


     // Cek di disk yang benar
    if (!Storage::exists($document->file_path)) {
        // Log::warning("File not found for delete: {$document->file_path}");
        $log .= "File not found on local for delete: {$document->file_path}";
        $log .= "<br>";
    } else {
        $log .= "File FOUND on local for delete: {$document->file_path}";
        $log .= "<br>";

    }

    
    // Cek di disk yang benar
    if (!Storage::disk('public')->exists($document->file_path)) {
        // Log::warning("File not found for delete: {$document->file_path}");
        $log .= "File not found on public for delete: {$document->file_path}";
        $log .= "<br>";

    } else {
        $log .= "File FOUND on public for delete: {$document->file_path}";
        $log .= "<br>";

    }

    return $log;

   
});


Route::get('/getfiles', function(){
    $nip = '199407292022031002';
    $user = User::where('username', $nip)->with('employee')->first();
    $employee = $user->employee;


    $empDocs = EmpDocument::where('id_employee', $employee->id)->get();

    return $empDocs;
});


Route::get('/checkfiles', function () {

    $nip = '199407292022031002';
    $user = User::where('username', $nip)->with('employee')->first();
    $employee = $user->employee;

    $directory = 'documents/' . $employee->nip;
    $files = Storage::disk('public')->allFiles($directory);

    // Remove the directory prefix from each file path
    $filesWithoutPrefix = array_map(function ($file) use ($directory) {
        return str_replace($directory . '/', '', $file);
    }, $files);

    foreach ($filesWithoutPrefix as $key => $fileName) {
        $exploded = explode('_', $fileName);
        $length = count($exploded);

        $label = $exploded[0];
        $param = ($length == 3) ? $exploded[1] : null;
        $docTypeId = DocType::where('label', $label)->first()->id;
        
        EmpDocument::firstOrCreate([
            'id_employee' => $employee->id,
            'id_doc_type' => $docTypeId,
            'parameter' => $param,
            'file_path' => $directory . '/' . $fileName,
            'file_name' => $fileName,
            'status' => 'Approved',
        ]);
    }

    $user->update(['docs_update_state' => true]);

    return 'done';

});

Route::get('/set-admin', function() {

    // $nipadmin = '199407292022031002';
    // $user = User::where('username', $nipadmin)->first();

    // $user->update([
    //     'role' => App\Enums\RoleType::SUPERADMIN->value,
    //     'can_multiple_role' => true
    // ]);

    // $user->roles()->syncWithoutDetaching([
    //     Role::where('name', 'SUPERADMIN')->first()->id,
    //     Role::where('name', 'ADMIN')->first()->id,
    //     Role::where('name', 'REVIEWER')->first()->id,
    // ]);

    // return $user->fresh();

    $nipadmins = [
        '199407292022031002', // Yud
        '198005152005011007', // Jarmil
        '198810252023211021', // Fauzhi
        '197505152005012003', // Anna
        '198305042014111002', // Koko
        '199307222023211014', // Azka
        '198006222014112002', // Sri
        '198605052023212065', // Ija
        '197904092023212012', // Amak
        '199009152023211019', // Kiki
    ];

    $users = User::whereIn('username', $nipadmins)->get();

    foreach ($users as $key => $user) {
        if($user->username == '199407292022031002') {
            $user->update([
                'role' => App\Enums\RoleType::SUPERADMIN->value,
                'can_multiple_role' => true
            ]);
    
            $user->roles()->syncWithoutDetaching([
                Role::where('name', 'SUPERADMIN')->first()->id,
                Role::where('name', 'ADMIN')->first()->id,
                Role::where('name', 'REVIEWER')->first()->id,
            ]);
        } else {
            $user->update([
                'role' => App\Enums\RoleType::REVIEWER->value,
                'can_multiple_role' => true
            ]);
    
            $user->roles()->syncWithoutDetaching([
                Role::where('name', 'REVIEWER')->first()->id,
            ]);
        }
        
        
    }

    return 'done';
});

Route::get('/employee-to-user-faster', function () {
    ini_set('max_execution_time', '300');
    DB::connection()->disableQueryLog();

    // hash sekali saja → hemat CPU
    $defaultHashed = Hash::make('GantiPassword123!'); // TODO: paksa user ganti di login pertama

    $now = now();
    $total = 0;

    // Ambil kolom minimal, yang belum punya user
    \App\Models\Employee::doesntHave('user')
        ->select('id', 'full_name', 'email', 'nip')
        ->whereNotNull('email')   // opsional: hanya yang punya email
        ->chunkById(1000, function ($chunk) use (&$total, $defaultHashed, $now) {
            $rows = [];
            foreach ($chunk as $e) {
                $rows[] = [
                    'id_employee' => $e->id,
                    'name'        => $e->full_name,
                    'email'       => $e->email,
                    'username'    => $e->nip,
                    'password'    => $defaultHashed,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }

            // upsert berdasarkan id_employee (unik)
            \App\Models\User::withoutEvents(function () use ($rows) {
                \App\Models\User::upsert(
                    $rows,
                    ['id_employee'],                                   // uniqueBy
                    ['name', 'email', 'username', 'password','updated_at'] // update columns
                );
            });

            $total += count($rows);
        });

    return response()->json(['status' => 'ok', 'affected' => $total]);
});

Route::get('/employee-to-user', function() {
    ini_set('max_execution_time', '300'); //300 seconds = 5 minutes

    $employees = Employee::doesntHave('user')->get();
        foreach ($employees as $key => $employee) {

            User::updateOrCreate(
                [
                    'email' =>  $employee->email,
                    'username' => $employee->nip
                ],
                [
                    'name' => $employee->full_name,
                    'password' => Hash::make($employee->nip),
                    'id_employee' => $employee->id
                ]
            );
        }

        return 'success';
});

Route::get('/print-lckb/{monthYear}', [PrintController::class, 'document']);
Route::get('/preview-lckb/{monthYear}/{user_id}', [PrintController::class, 'preview']);

Route::get('/logout_all', function () {
    \App\Models\User::each(function ($u) {
        Auth::login($u);
        Auth::logout();
    });

    return 'done';
});


Route::get('/terms-and-conditions', function () {
    return view('tnc');
});


Route::get('/terms-of-service', function () {
    return view('tos');
});

Route::get('/privacy-policy', function () {
    return view('privacy_policy');
});

Route::get('/landing', function () {
    if (Auth::check()) {
        return redirect('/admin/dashboard');
    }

    $events = Event::where('is_active', true)->get();

    return view('admin.layouts.landing', [
        'events' => $events,
    ]);
})->name('landing');

Route::get('/', function () {
    // ambil semua event aktif (atau semua, kalau mau tampilkan juga yg nonaktif)
    // $events = \App\Models\Event::where('is_active', true)
    //     ->with('province', 'regency', 'district')
    //     ->orderBy('tanggal_mulai', 'desc')
    //     ->get();

    // return view('landing', compact('events'));

    return redirect('landing');
});



Route::get('/get-password', function () {
    return 'apasih';
    // bcrypt('12345678');
});

Route::get('/all-users', function () {
    $users = \App\Models\User::all()->pluck('nip_name')->sort();
    // $users = $users->toArray();
    // return $users;

    $html = '';
    foreach ($users as $user) {

        $bod = substr($user, 0, 8);
        $dob = DateTime::createFromFormat('Ymd', $bod);
        // return $ymd;
        $today   = new DateTime('today');
        $year = $dob->diff($today)->y;
        $month = $dob->diff($today)->m;
        $day = $dob->diff($today)->d;

        $tgllahir = $year." tahun"." ".sprintf('%02d', $month)." bulan"." ".sprintf('%02d', $day)." hari";

        $html .= $tgllahir . '    -    ' .$user . '<br>';
    }

    return $html;
});

Route::middleware('auth')->group(function () {

    

    Route::get('api/preview/pdf', [EmpDocumentController::class, 'show'])
        ->name('pdf.preview');

    Route::get('/api/verval-logs', [VervalLogController::class, 'index']);


    

    Route::get('/api/reports', [ReportController::class, 'index']);

    Route::get('/api/stats/all', [DashboardController::class, 'all']);

    Route::get('/api/stats/reports', [DashboardController::class, 'reports']);
    Route::get('/api/fusers', [UserController::class, 'fetch']);

    Route::get('/api/fetch-orgs', [OrganizationController::class, 'fetch']);
    Route::get('/api/orgs', [OrganizationController::class, 'index']);
    Route::post('api/orgs', [OrganizationController::class, 'store']);
    Route::put('api/orgs/{org}', [OrganizationController::class, 'update']);
    Route::delete('/api/orgs/{org}', [OrganizationController::class, 'destroy']);
    Route::delete('/api/orgs', [OrganizationController::class, 'bulkDelete']);


    Route::get('/api/users', [UserController::class, 'index']);
    Route::post('api/users', [UserController::class, 'store']);
    Route::put('api/users/{user}', [UserController::class, 'update']);
    Route::delete('/api/users/{user}', [UserController::class, 'destroy']);
    Route::patch('/api/users/{user}/change-role', [UserController::class, 'changeRole']);
    Route::delete('/api/users', [UserController::class, 'bulkDelete']);

    Route::get('/api/reports', [ReportController::class, 'index']);
    Route::post('/api/reports/create', [ReportController::class, 'store']);
    Route::get('/api/reports/{work}/edit', [ReportController::class, 'edit']);
    Route::put('/api/reports/{work}/edit', [ReportController::class, 'update']);
    Route::delete('/api/reports/{work}', [ReportController::class, 'destroy']);

    Route::delete('/api/parent-reports/{report}', [ReportController::class, 'destroyParent']);


    Route::get('/api/settings', [SettingController::class, 'index']);
    Route::post('/api/settings', [SettingController::class, 'update']);

    Route::get('/api/profile', [ProfileController::class, 'index']);
    Route::get('/api/docs-update-state', [ProfileController::class, 'docsUpdateState']);
    Route::put('/api/profile', [ProfileController::class, 'update']);
    Route::post('/api/upload-profile-image', [ProfileController::class, 'uploadImage']);
    Route::post('/api/change-user-password', [ProfileController::class, 'changePassword']);




    Route::get('/api/employee/documents', [EmployeeDocumentController::class, 'index']);
    Route::post('/api/employee/documents', [EmployeeDocumentController::class, 'store']);
    Route::patch('/employee/documents/{id}/status', [EmployeeDocumentController::class, 'updateStatus']);
    Route::delete('/employee/documents/{id}', [EmployeeDocumentController::class, 'destroy']);

    Route::get('/api/my-documents', [DocumentController::class, 'myDocuments']);
    Route::post('/api/upload-document', [DocumentController::class, 'uploadDocument']);
    Route::post('/api/reupload-document/{id}', [DocumentController::class, 'reupload']);
    Route::get('/api/user-documents/{userId}', [DocumentController::class, 'documentsByUserId']);
    Route::get('/api/sync-files', [DocumentController::class, 'syncFiles']);

    Route::post('api/documents/{id}/request-change', [DocumentController::class, 'requestChange']);


    Route::prefix('/api/work-units')->group(function () {
        Route::get('{id}/employees', [WorkUnitController::class, 'fetchEmployee']);


        // Get tree data
        Route::get('/tree', [WorkUnitController::class, 'tree']);
        Route::get('/monitor', [WorkUnitController::class, 'monitor']);
        Route::get('/self-monitor', [WorkUnitController::class, 'selfMonitor']);
        Route::get('/fetch', [WorkUnitController::class, 'fetch']);
    
        // CRUD Routes
        Route::get('/', [WorkUnitController::class, 'index']);           // Optional: List semua unit (flat)
        Route::post('/', [WorkUnitController::class, 'store']);          // Create new unit
        Route::get('/{id}', [WorkUnitController::class, 'show']);        // Optional: Get single unit
        Route::put('/{id}', [WorkUnitController::class, 'update']);      // Update unit
        Route::delete('/{id}', [WorkUnitController::class, 'destroy']);  // Delete unit
    });


    Route::get('/api/emp-documents', [EmpDocumentController::class, 'index']);
    Route::post('api/emp-documents/claim', [EmpDocumentController::class, 'claim']);
    Route::put('/api/emp-documents/{id}/verify', [EmpDocumentController::class, 'verify']);
    Route::post('api/emp-documents/{empDocument}/release', [EmpDocumentController::class, 'release']);
    Route::get('/api/document-log/{id}', [DocumentLogController::class, 'show']);
    Route::get('/api/emp-documents/remaining', [EmpDocumentController::class, 'remaining']);


});

Route::get('{view}', ApplicationController::class)->where('view', '(.*)')->middleware('auth');
