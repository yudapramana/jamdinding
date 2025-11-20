<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\EmpDocument;
use App\Models\Employee;
use App\Models\VervalLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Yaza\LaravelGoogleDriveStorage\Gdrive;
use Log;

class EmpDocumentController extends Controller
{

    public function show(Request $request)
    {
        $path = $request->query('path');
        abort_unless($path, 404, 'Missing path');

        $after = Str::after($path, 'documents/');
        $nip   = Str::before($after, '/');

        $user   = Auth::user();
        $userNip = $user->username;

        $isOwner      = $userNip && $userNip === $nip;
        $canMultiRole = (bool)($user->can_multiple_role ?? false);

        if (!$isOwner && !$canMultiRole) {
            abort(403, 'Forbidden');
        }

        $disk = Storage::disk('gcs');
        abort_unless($disk->exists($path), 404, 'File not found');

        $mime       = $disk->mimeType($path) ?: 'application/pdf';
        $size       = $disk->size($path);
        $lastModTs  = $disk->lastModified($path);
        $lastModHttp = $lastModTs ? gmdate('D, d M Y H:i:s', $lastModTs) . ' GMT' : null;

        // ===== HTTP 304: If-Modified-Since =====
        if ($lastModHttp && $request->headers->has('If-Modified-Since')) {
            $ifModSince = strtotime($request->header('If-Modified-Since'));
            if ($ifModSince !== false && $ifModSince >= $lastModTs) {
                return response('', Response::HTTP_NOT_MODIFIED, array_filter([
                    'Last-Modified' => $lastModHttp,
                    'Cache-Control' => 'private, max-age=3600',
                ]));
            }
        }

        $filename = basename($path);

        // 🔹 Fix: kalau HEAD → jangan stream isi file
        if ($request->isMethod('HEAD')) {
            return response('', 200, array_filter([
                'Content-Type'              => $mime,
                'Content-Disposition'       => 'inline; filename="'.$filename.'"',
                'Cache-Control'             => 'private, max-age=3600',
                'Last-Modified'             => $lastModHttp,
                'Content-Length'            => $size,
                "Content-Security-Policy"   => "frame-ancestors 'self'",
            ]));
        }

        // 🔹 Normal GET → stream isi file
        $stream = $disk->readStream($path);
        abort_if($stream === false, 500, 'Failed to open stream');

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) fclose($stream);
        }, 200, array_filter([
            'Content-Type'              => $mime,
            'Content-Disposition'       => 'inline; filename="'.$filename.'"',
            'Cache-Control'             => 'private, max-age=3600',
            'Last-Modified'             => $lastModHttp,
            "Content-Security-Policy"   => "frame-ancestors 'self'",
            // 'Content-Length' => $size, // optional
        ]));
    }


    // public function show(Request $request)
    // {
    //     $path = $request->query('path');
    //     abort_unless($path, 404, 'Missing path');

    //     // return $path;
    //     $after = Str::after($path, 'documents/');
    //     $nip = Str::before($after, '/'); // "199407292022031002"

    //     $user = Auth::user();

    //     Log::info('step1');

    //     // NIP pemilik dokumen (dari relasi user->employee) / dari user->username
    //     $userNip = $user->username;

    //     Log::info('step2');

    //     // Aturan akses:
    //     // 1) Jika NIP user == NIP pada URL → izinkan (meski can_multiple_role false)
    //     // 2) Jika berbeda → hanya izinkan kalau can_multiple_role == true
    //     $isOwner      = $userNip && $userNip === $nip;
    //     $canMultiRole = (bool)($user->can_multiple_role ?? false);
    //     Log::info('step3');

    //     if (!$isOwner && !$canMultiRole) {
    //         abort(403, 'Forbidden');
    //     }
    //     Log::info('step4');


    //     $disk = Storage::disk('gcs');
    //     abort_unless($disk->exists($path), 404, 'File not found');

    //     // Ambil metadata tanpa mengunduh isi file
    //     $mime = $disk->mimeType($path) ?: 'application/pdf';
    //     $size = $disk->size($path);                // bytes (jika adapter support)
    //     $lastModTs = $disk->lastModified($path);   // unix timestamp (jika adapter support)
    //     $lastModHttp = $lastModTs ? gmdate('D, d M Y H:i:s', $lastModTs) . ' GMT' : null;
    //     Log::info('step5');

    //     // ===== HTTP 304: If-Modified-Since =====
    //     if ($lastModHttp && $request->headers->has('If-Modified-Since')) {
    //         $ifModSince = strtotime($request->header('If-Modified-Since'));
    //         if ($ifModSince !== false && $ifModSince >= $lastModTs) {
    //             return response('', Response::HTTP_NOT_MODIFIED, array_filter([
    //                 'Last-Modified' => $lastModHttp,
    //                 'Cache-Control' => 'private, max-age=3600',
    //             ]));
    //         }
    //     }
    //     Log::info('step6');

    //     // Stream langsung dari Drive (mulai kirim lebih cepat, tanpa buffer besar)
    //     $stream = $disk->readStream($path);
    //     abort_if($stream === false, 500, 'Failed to open stream');
    //     Log::info('step7');

    //     $filename = basename($path);
    //     Log::info('step8');

    //     return response()->stream(function () use ($stream) {
    //         fpassthru($stream);               // kirim apa adanya
    //         if (is_resource($stream)) fclose($stream);
    //     }, 200, array_filter([
    //         'Content-Type'        => $mime,
    //         'Content-Disposition' => 'inline; filename="'.$filename.'"',
    //         'Cache-Control'       => 'private, max-age=3600',
    //         'Last-Modified'       => $lastModHttp,
    //         // Jangan set Accept-Ranges jika backend tidak support 206 secara native
    //         // 'Content-Length'   => $size,   // boleh di-set kalau adapter mengembalikan size dengan cepat
    //         "Content-Security-Policy" => "frame-ancestors 'self'",
    //     ]));
    // }

    // public function show(Request $request)
    // {
    //     // path dikirim lewat query ?path=...
    //     $path = $request->query('path');
    //     abort_unless($path, 404, 'Missing path');

    //     // Ambil file dari Gdrive
    //     $data = Gdrive::get($path); // ->file (binary/base64), ->ext (mime), ->name (opsional)

    //     // Pastikan MIME
    //     $mime = (!empty($data->ext) && Str::contains($data->ext, '/'))
    //         ? $data->ext
    //         : 'application/pdf';

    //     // Jika file base64, decode dulu
    //     $content = $data->file;
    //     if (is_string($content) && !Str::startsWith($content, '%PDF-')) {
    //         $decoded = base64_decode($content, true);
    //         if ($decoded !== false) {
    //             $content = $decoded;
    //         }
    //     }

    //     // Nama file untuk header
    //     $filename = $data->name ?? basename($path);

    //     // (Opsional) hitung panjang konten agar viewer lebih mulus
    //     $length = is_string($content) ? strlen($content) : null;

    //     return response($content, 200, array_filter([
    //         'Content-Type'              => $mime, // Wajib: application/pdf
    //         'Content-Disposition'       => 'inline; filename="'.$filename.'"',
    //         'Content-Transfer-Encoding' => 'binary',
    //         'Accept-Ranges'             => 'bytes',           // memberi hint partial, meski tidak 206
    //         'Content-Length'            => $length,           // opsional tapi membantu
    //         'Cache-Control'             => 'private, max-age=3600',
    //         // Pastikan iframe boleh menampilkan (sesuai kebutuhan keamanan kamu)
    //         // 'X-Frame-Options'           => 'SAMEORIGIN',
    //         "Content-Security-Policy"   => "frame-ancestors 'self'",
    //     ]));
    // }

    public function index(Request $request)
    {
        // $query = EmpDocument::where('status', 'Pending')->with(['employee', 'docType'])
        //     ->when($request->search, function ($q) use ($request) {
        //         $q->whereHas('employee', function ($q) use ($request) {
        //             $q->where('full_name', 'like', '%' . $request->search . '%')
        //               ->orWhere('nip', 'like', '%' . $request->search . '%');
        //         });
        //     })
        //     ->orderByDesc('created_at');
        // $documents = $query->paginate(setting('pagination_limit'));

        // return response()->json($documents);


        $userId = auth()->id();

        $query = EmpDocument::with(['employee.workunit', 'docType'])
            ->where('status', 'Pending')
            // hanya dokumen milik user ini (assigned ke dia)
            ->where(function ($q) use ($userId) {
                $q->where('assigned_to', $userId);
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->whereHas('employee', function ($qq) use ($request) {
                    $qq->where('full_name', 'like', '%' . $request->search . '%')
                       ->orWhere('nip', 'like', '%' . $request->search . '%');
                });
            })
            ->orderByDesc('assigned_at'); // yang baru diambil muncul duluan

        $documents = $query->paginate(setting('pagination_limit'));

        return response()->json($documents);
    }

    // public function claim(Request $request)
    // {
    //     $validated = $request->validate([
    //         'count' => 'sometimes|integer|min:1|max:50',
    //     ]);
    //     $take = (int)($validated['count'] ?? 5);

    //     $userId = auth()->id();
    //     $lockTtlMinutes = 30;

    //     return DB::transaction(function () use ($userId, $lockTtlMinutes, $take) {
    //         // Ambil N dokumen Pending yang belum di-assign atau lock expired (FIFO)
    //         $docs = EmpDocument::where('status', 'Pending')
    //             ->where(function ($q) {
    //                 $q->whereNull('assigned_to')
    //                 ->orWhere('lock_expires_at', '<', now());
    //             })
    //             ->orderBy('created_at')
    //             ->lockForUpdate()
    //             ->limit($take)
    //             ->get();

    //         if ($docs->isEmpty()) {
    //             return response()->json(['message' => 'Tidak ada dokumen yang tersedia untuk di-claim'], 404);
    //         }

    //         $expiresAt = Carbon::now()->addMinutes($lockTtlMinutes);

    //         foreach ($docs as $doc) {
    //             $doc->assigned_to = $userId;
    //             $doc->assigned_at = now();
    //             $doc->lock_expires_at = $expiresAt;
    //             $doc->save();
    //         }

    //         // kembalikan dengan relasi yang diperlukan
    //         $docs->load(['employee', 'docType']);

    //         return response()->json([
    //             'success' => true,
    //             'claimed' => $docs->count(),
    //             'data'    => $docs,
    //         ]);
    //     });
    // }

    // public function claim(Request $request)
    // {
    //     $validated = $request->validate([
    //         'count' => 'sometimes|integer|min:1|max:50',
    //     ]);
    //     $take = (int)($validated['count'] ?? 5);

    //     $userId         = auth()->id();
    //     $lockTtlMinutes = 30;
    //     $nipPriority    = '199407292022031002';

    //     return DB::transaction(function () use ($userId, $lockTtlMinutes, $take, $nipPriority) {
    //         // Ambil N dokumen Pending yang eligible (unassigned atau lock kadaluarsa)
    //         // PRIORITAS: dokumen dengan employee.nip = $nipPriority, lalu FIFO by created_at
    //         $docs = EmpDocument::where('status', 'Pending')
    //             ->where(function ($q) {
    //                 $q->whereNull('assigned_to')
    //                 ->orWhere('lock_expires_at', '<', now());
    //             })
    //             ->lockForUpdate()
    //             ->orderByRaw(
    //                 // Prioritaskan dokumen yang employee.nip cocok => nilai 0, lainnya 1
    //                 "CASE WHEN EXISTS (
    //                     SELECT 1
    //                     FROM employees e
    //                     WHERE e.id = emp_documents.id_employee
    //                     AND e.nip = ?
    //                 ) THEN 0 ELSE 1 END ASC",
    //                 [$nipPriority]
    //             )
    //             ->orderBy('created_at', 'asc') // FIFO dalam masing-masing kelompok prioritas
    //             ->limit($take)
    //             ->get();

    //         if ($docs->isEmpty()) {
    //             return response()->json(['message' => 'Tidak ada dokumen yang tersedia untuk di-claim'], 404);
    //         }

    //         $expiresAt = Carbon::now()->addMinutes($lockTtlMinutes);

    //         foreach ($docs as $doc) {
    //             $doc->assigned_to     = $userId;
    //             $doc->assigned_at     = now();
    //             $doc->lock_expires_at = $expiresAt;
    //             $doc->save();
    //         }

    //         // Kembalikan dengan relasi yang diperlukan
    //         $docs->load(['employee', 'docType']);

    //         return response()->json([
    //             'success' => true,
    //             'claimed' => $docs->count(),
    //             'data'    => $docs,
    //         ]);
    //     });
    // }

   

    public function claim(Request $request)
    {
        $validated = $request->validate([
            'count'        => 'sometimes|integer|min:1|max:50',
            'id_work_unit' => 'nullable|integer|exists:work_units,id',
        ]);

        $take         = (int)($validated['count'] ?? 5);
        $idWorkUnit   = $validated['id_work_unit'] ?? null; // <— dari dropdown (boleh null)
        $userId       = auth()->id();
        $lockTtlMin   = 30;
        $nipPriority  = '199407292022031002';
        $now          = now();

        return DB::transaction(function () use ($userId, $lockTtlMin, $take, $nipPriority, $idWorkUnit, $now) {

            $q = EmpDocument::query()
                ->where('status', 'Pending')
                ->where(function ($q) use ($now) {
                    $q->whereNull('assigned_to')
                    ->orWhere('lock_expires_at', '<', $now);
                });

            // Filter by Work Unit kalau dipilih
            if ($idWorkUnit) {
                $q->whereHas('employee', function ($sub) use ($idWorkUnit) {
                    $sub->where('id_work_unit', $idWorkUnit);
                });
            }

            // Kunci baris kandidat agar anti double-claim
            // Jika DB Anda MySQL 8+ / PostgreSQL, pertimbangkan skip locked:
            // $q->lock('for update skip locked');
            $q->lockForUpdate();

            // Prioritas NIP tertentu, lalu FIFO
            $q->orderByRaw(
                "CASE WHEN EXISTS (
                    SELECT 1
                    FROM employees e
                    WHERE e.id = emp_documents.id_employee
                    AND e.nip = ?
                ) THEN 0 ELSE 1 END",
                [$nipPriority]
            )
            ->orderBy('updated_at', 'asc')
            ->orderBy('id', 'asc')   // tie-breaker yang stabil
            ->limit($take);

            $docs = $q->get();

            if ($docs->isEmpty()) {
                return response()->json(['message' => 'Tidak ada dokumen yang tersedia untuk di-claim'], 404);
            }

            $expiresAt = (clone $now)->addMinutes($lockTtlMin);

            foreach ($docs as $doc) {
                $doc->assigned_to     = $userId;
                $doc->assigned_at     = $now;
                $doc->lock_expires_at = $expiresAt;
                $doc->save();
            }

            $docs->load(['employee', 'docType']);

            return response()->json([
                'success' => true,
                'claimed' => $docs->count(),
                'data'    => $docs,
            ]);
        });
    }


    public function release(EmpDocument $empDocument)
    {
        $userId = auth()->id();

        if ($empDocument->assigned_to !== $userId) {
            return response()->json(['message' => 'Anda tidak memiliki dokumen ini'], 403);
        }

        $empDocument->update([
            'assigned_to' => null,
            'assigned_at' => null,
            'lock_expires_at' => null,
        ]);

        return response()->json(['success' => true]);
    }

    // public function remaining()
    // {
    //     $count = EmpDocument::where('status', 'Pending')
    //         ->where(function ($q) {
    //             $q->whereNull('assigned_to')
    //             ->orWhere('lock_expires_at', '<', now());
    //         })
    //         ->count();

    //     return response()->json(['remaining' => $count]);
    // }

    public function remaining(Request $request)
    {
        $validated = $request->validate([
            'id_work_unit' => 'nullable|integer|exists:work_units,id',
        ]);

        $idWorkUnit = $validated['id_work_unit'] ?? null;
        $now = now();

        $count = EmpDocument::where('status', 'Pending')
            ->where(function ($q) use ($now) {
                $q->whereNull('assigned_to')
                ->orWhere('lock_expires_at', '<', $now);
            })
            ->when($idWorkUnit, function ($q) use ($idWorkUnit) {
                $q->whereHas('employee', fn ($s) => $s->where('id_work_unit', $idWorkUnit));
            })
            ->count();

        return response()->json(['remaining' => $count]);
    }

    // public function verify(Request $request, $id)
    // {
    //     $request->validate([
    //         'status' => 'required|in:Approved,Rejected',
    //         'verif_notes' => 'nullable|string',
    //     ]);

    //     $doc = EmpDocument::findOrFail($id);
    //     $doc->status = $request->status;
    //     $doc->verif_notes = $request->verif_notes;
    //     $doc->save();

    //     $user = $doc->employee->user;
    //     $user->update(['docs_update_state' => true]);

    //     return response()->json([
    //         'message' => 'Dokumen berhasil diverifikasi.',
    //         'data' => $doc,
    //     ]);
    // }

    // public function verify(Request $request, $id)
    // {
    //     $request->validate([
    //         'status' => 'required|in:Approved,Rejected',
    //         'verif_notes' => 'nullable|string',
    //     ]);

    //     $doc = EmpDocument::findOrFail($id);
    //     $doc->status = $request->status;
    //     $doc->verif_notes = $request->verif_notes;
    //     $doc->save();

    //     // Simpan ke tabel verval_logs
    //     $vervalLog = new VervalLog();
    //     $vervalLog->id_document = $doc->id;
    //     $vervalLog->verval_status = $request->status;
    //     $vervalLog->verified_by = Auth::id(); // ID admin yang melakukan verifikasi
    //     $vervalLog->verif_notes = $request->verif_notes;
    //     $vervalLog->created_at = now();
    //     $vervalLog->save();

    //     // Update state user
    //     $user = $doc->employee->user;
    //     $user->update(['docs_update_state' => true]);

    //     return response()->json([
    //         'message' => 'Dokumen berhasil diverifikasi.',
    //         'data' => $doc,
    //     ]);
    // }

    public function verify(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:Approved,Rejected',
            'verif_notes' => 'nullable|string',
        ]);


        $userId = auth()->id();
        $doc = EmpDocument::findOrFail($id);

       // Cek apakah dokumen sudah diverifikasi sebelumnya
        if (in_array($doc->status, ['Approved', 'Rejected'])) {
            return response()->json([
                'message' => 'Dokumen sudah diverifikasi sebelumnya dan tidak dapat diverifikasi ulang.',
                'code' => 'DOCUMENT_ALREADY_VERIFIED'
            ], 409); // 409 Conflict
        }


        //
        $requestedApproved = $request->status === 'Approved';
        $path              = ltrim($doc->file_path ?? '', '/'); // contoh: documents/1994.../FILE.pdf

        // === Jika akan Approved: pindahkan file dari privatedisk -> google ===
        if ($requestedApproved && $path) {
            $srcDisk = Storage::disk('privatedisk');
            $dstDisk = Storage::disk('gcs');

            $srcExists = $srcDisk->exists($path);
            $dstExists = $dstDisk->exists($path);

            // Pastikan folder tujuan di Google Drive ada
            // Gdrive::makeDir(dirname($path));

            // Jika file belum ada di Google, dan ada di private -> lakukan copy stream
            if (!$dstExists) {
                if (!$srcExists) {
                    // Tidak ada di private maupun di google -> gagal
                    return response()->json([
                        'message' => 'File sumber tidak ditemukan untuk dipindahkan.',
                        'code'    => 'SOURCE_FILE_MISSING'
                    ], 404);
                }

                // Tulis ke google via stream (hapus dulu jika perlu untuk hindari konflik)
                $stream = $srcDisk->readStream($path);
                if ($stream === false) {
                    return response()->json([
                        'message' => 'Gagal membuka stream file sumber.',
                        'code'    => 'STREAM_OPEN_FAILED'
                    ], 500);
                }

                // Pastikan tidak ada file tujuan yang menghalangi
                if ($dstDisk->exists($path)) {
                    $dstDisk->delete($path);
                }

                $dstOk = $dstDisk->writeStream($path, $stream);
                if (is_resource($stream)) fclose($stream);

                if ($dstOk === false) {
                    return response()->json([
                        'message' => 'Gagal menulis file ke Google Drive.',
                        'code'    => 'WRITE_DEST_FAILED'
                    ], 500);
                }
            }

            // Hapus file di privatedisk bila ada
            if ($srcExists) {
                $srcDisk->delete($path);
            }
            // Catatan: $doc->file_path tetap sama (format terdahulu).
            // Jika Anda menyimpan kolom disk/driver, update di sini (mis. $doc->file_disk = 'google').
        }
        //


        DB::transaction(function () use ($doc, $request, $userId) {
            $doc->status = $request->status;
            $doc->verif_notes = $request->verif_notes;
            $doc->save();

            // Simpan ke tabel verval_logs
            VervalLog::create([
                'id_document' => $doc->id,
                'verval_status' => $request->status,
                'verified_by' => $userId,
                'verif_notes' => $request->verif_notes,
                'created_at' => now(),
            ]);

            // Update state user
            $employee = $doc->employee;
            if($request->status == 'Approved') {
                $employee->update(['docs_progress_state' => true]);
            }
            $user = $employee->user;
            $user->update(['docs_update_state' => true]);
        });
        
        

        return response()->json([
            'message' => 'Dokumen berhasil diverifikasi.',
            'data' => $doc,
        ]);
    }
}
