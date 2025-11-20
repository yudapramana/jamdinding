<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\DocType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Document;
use App\Models\EmpDocument;
use App\Models\User;
use App\Models\VervalLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Helpers\FileHelper;
use Log;
use Yaza\LaravelGoogleDriveStorage\Gdrive;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentController extends Controller
{

    public function requestChange($id, Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $document = EmpDocument::query()->findOrFail($id);

        // --- Ambil path file yang tersimpan di dokumen
        // Pastikan field yang dipakai konsisten (mis. file_path)
        $path = ltrim((string) ($document->file_path ?? ''), '/');
        if ($path === '') {
            return response()->json([
                'message' => 'Path file tidak tersedia pada dokumen.',
                'code'    => 'FILE_PATH_EMPTY',
            ], 404);
        }

        // === Reversal: pindahkan file dari google (gcs) -> privatedisk
        $srcDisk = Storage::disk('gcs');
        $dstDisk = Storage::disk('privatedisk');

        $srcExists = $srcDisk->exists($path);
        $dstExists = $dstDisk->exists($path);

        if (!$srcExists && !$dstExists) {
            // Tidak ada di kedua sisi
            return response()->json([
                'message' => 'File sumber tidak ditemukan di Google maupun di Private.',
                'code'    => 'SOURCE_FILE_MISSING',
            ], 404);
        }

        // Jika file belum ada di privatedisk dan ada di gcs -> copy stream ke privatedisk
        if (!$dstExists && $srcExists) {
            $stream = $srcDisk->readStream($path);
            if ($stream === false) {
                return response()->json([
                    'message' => 'Gagal membuka stream file sumber dari Google.',
                    'code'    => 'STREAM_OPEN_FAILED',
                ], 500);
            }

            // Pastikan tidak ada file tujuan yang menghalangi (double check)
            if ($dstDisk->exists($path)) {
                $dstDisk->delete($path);
            }

            $dstOk = $dstDisk->writeStream($path, $stream);
            if (is_resource($stream)) fclose($stream);

            if ($dstOk === false) {
                return response()->json([
                    'message' => 'Gagal menulis file ke Private Storage.',
                    'code'    => 'WRITE_DEST_FAILED',
                ], 500);
            }
        }

        // Hapus file di Google bila ada (supaya sumber tunggal: private)
        // if ($srcExists) {
        //     $srcDisk->delete($path);
        // }

        // === Ubah status dokumen menjadi Pending (idempotent jika sudah Pending/Rejected)
        if ($document->status !== 'Pending') {
            $document->status = 'Pending';
        }

        $document->save();

        // === Simpan ke verval_logs
        $vervalLog = new VervalLog();
        $vervalLog->id_document   = $document->id;
        $vervalLog->verval_status = 'Request Change';                 // sesuai kebutuhan
        $vervalLog->verified_by   = Auth::id();                       // admin/user pengaju
        $vervalLog->verif_notes   = $request->input('reason');        // alasan dari form
        $vervalLog->save();

        return response()->json([
            'message' => 'File dipindahkan ke Private Storage, status diubah ke Pending, dan log verval tercatat.',
            'data'    => [
                'document_id' => $document->id,
                'status'      => $document->status,
                'file_path'   => $document->file_path,
                // 'file_disk' => $document->file_disk ?? null,
            ],
        ]);
    }



    public function documentsByUserId($userId)
    {
        $user = User::find($userId);
        $id_employee = $user->id_employee;
        $documents = EmpDocument::where('id_employee', $id_employee)->with('doctype')->get()->map(function ($doc) {
            return [
                'id' => $doc->id,
                'id_doc_type' => $doc->id_doc_type,
                'doc_number' => $doc->doc_number,
                'doc_date' => $doc->doc_date,
                'parameter' => $doc->parameter,
                'file_path' => $doc->file_path,
                'file_url' => url('secure/' . $doc->file_path),
                'file_name' => $doc->file_name,
                'status' => $doc->status,
                'verif_notes' => $doc->verif_notes
            ];
        });

        return response()->json([
            'data' => $documents,
            'user' => $user
        ]);
    }


    /**
     * List documents uploaded by the logged-in user
     */
    public function myDocuments()
    {
        $user = auth()->user();
        $id_employee = $user->id_employee;
        $documents = EmpDocument::where('id_employee', $id_employee)->with('doctype')->get()->map(function ($doc) {
            return [
                'id' => $doc->id,
                'id_doc_type' => $doc->id_doc_type,
                'doc_number' => $doc->doc_number,
                'doc_date' => $doc->doc_date,
                'parameter' => $doc->parameter,
                'file_path' => $doc->file_path,
                'file_url' => url('secure/' . $doc->file_path),
                'file_name' => $doc->file_name,
                'status' => $doc->status,
                'verif_notes' => $doc->verif_notes
            ];
        });

        $user = User::find($user->id);
        $user->update(['docs_update_state' => false]);

        return response()->json([
            'data' => $documents
        ]);
    }

    /**
     * Upload new document
     */
    public function uploadDocument(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_doc_type' => 'required|exists:doc_types,id',
            'doc_number' => 'nullable|string|max:255',
            'doc_date' => 'nullable|date',
            'parameter' => 'nullable|string|max:255',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        // Upload file
        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();

        if (isset($request->user_id)) {
            $user = User::find($request->user_id);
            $employee = $user->employee;
        } else {
            $employee = Auth::user()->employee;
        }

        $employeeId = $employee->id;
        $docType = DocType::find($request->id_doc_type);

        $fileName = $docType->label . ($request->parameter ? ('_' . $request->parameter) : '') . '_' . $employee->nip . '.' . $extension;

        // Cek apakah file_name sudah ada di database
        $fileNameExists = EmpDocument::where('file_name', $fileName)->exists();
        if ($fileNameExists) {
            return response()->json([
                'errors' => [
                    'file_name' => ['File sudah diupload dengan nama yang sama. Perbarui item jika ingin upload ulang.']
                ]
            ], 422);
        }

        $filePath = $file->storeAs(
            'documents/' . $employee->nip,
            $fileName,
            'privatedisk'
        );

        // Simpan ke database dokumen
        $document = new EmpDocument();
        $document->id_employee = $employeeId;
        $document->id_doc_type = $request->id_doc_type;
        $document->doc_number = $request->doc_number;
        $document->file_name = $fileName;
        $document->doc_date = $request->doc_date;
        $document->parameter = $request->parameter;
        $document->file_path = $filePath;
        // if (isset($request->user_id)) {
        //     $document->status = 'Approved';
        // }
        $document->status = 'Pending';
        $document->save();

        // Simpan ke verval_logs
        $vervalLog = new VervalLog();
        $vervalLog->id_document = $document->id;
        $vervalLog->verval_status = isset($request->user_id) ? 'Uploaded by Admin' : 'Uploaded';
        $vervalLog->verified_by = Auth::id(); // Admin yang melakukan upload atau user itu sendiri
        $vervalLog->verif_notes = null; // Tidak ada catatan saat upload
        $vervalLog->save();

        // Update state jika oleh admin
        if (isset($request->user_id)) {
            $user->docs_update_state = true;
            $user->save();
            $employee->update(['docs_progress_state' => true]);
        }

        return response()->json([
            'message' => 'Dokumen berhasil diupload.'
        ]);
    }

    public function reupload(Request $request, $id)
    {
        $document = EmpDocument::findOrFail($id);
        $employee = $document->employee;
        $validator = Validator::make($request->all(), [
            'doc_number' => 'nullable|string|max:255',
            'doc_date' => 'nullable|date',
            'parameter'  => 'nullable|string|max:255',
            'file'       => 'sometimes|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        //////
        // Parse Nama File
        $parsed = parseFileName($document->file_name);

        $oldPath = $document->file_path;
        $baseDir = 'documents/'.$employee->nip;

        // Normalisasi parameter
        $paramReq = trim((string) ($request->parameter ?? ''));
        $paramOld = trim((string) ($parsed['parameter'] ?? ''));

        $parameterChanged = $paramOld !== $paramReq;
        $fileUploaded     = $request->hasFile('file');

        if ($parameterChanged && !$fileUploaded) {
            // === (1) Ubah parameter saja: RENAME (move) ===
            $parsed['parameter'] = $paramReq;

            $newFileName = buildFileName(
                $parsed['label'],
                $parsed['parameter'],
                $parsed['nip'],
                $parsed['extension'] // pakai extension lama
            );
            $newPath = $baseDir.'/'.$newFileName;

            // Cek duplikat nama file (wajib untuk perubahan parameter)
            if (EmpDocument::where('file_name', $newFileName)->exists()) {
                return response()->json([
                    'errors' => [
                        'file_name' => ['File sudah diupload dengan nama yang sama. Perbarui item jika ingin upload ulang.']
                    ]
                ], 422);
            }

            if ($oldPath && Storage::disk('privatedisk')->exists($oldPath)) {
                Storage::disk('privatedisk')->move($oldPath, $newPath);
                $document->file_name = $newFileName;
                $document->file_path = $newPath;
            }

        } elseif ($fileUploaded && !$parameterChanged) {
            // === (2) Ubah file saja: REPLACE isi (tanpa cek duplikat) ===
            $extension   = $request->file('file')->getClientOriginalExtension();
            $newFileName = buildFileName(
                $parsed['label'],
                $paramOld,          // parameter tetap
                $parsed['nip'],
                $extension          // extension baru dari file upload
            );
            $newPath = $baseDir.'/'.$newFileName;

            // Hapus file lama bila ada (boleh sama/beda nama)
            if ($oldPath && Storage::disk('privatedisk')->exists($oldPath)) {
                Storage::disk('privatedisk')->delete($oldPath);
            }

            // Simpan file baru
            $storedPath = $request->file('file')->storeAs($baseDir, $newFileName, 'privatedisk');

            $document->file_name = $newFileName;
            $document->file_path = $storedPath;

        } elseif ($fileUploaded && $parameterChanged) {
            // === (3) Ubah file & parameter: DELETE lama → SIMPAN baru (cek duplikat) ===
            $parsed['parameter'] = $paramReq;

            $extension   = $request->file('file')->getClientOriginalExtension();
            $newFileName = buildFileName(
                $parsed['label'],
                $parsed['parameter'],
                $parsed['nip'],
                $extension
            );
            $newPath = $baseDir.'/'.$newFileName;

            // Cek duplikat nama file (karena parameter berubah)
            if (EmpDocument::where('file_name', $newFileName)->exists()) {
                return response()->json([
                    'errors' => [
                        'file_name' => ['File sudah diupload dengan nama yang sama. Perbarui item jika ingin upload ulang.']
                    ]
                ], 422);
            }

            if ($oldPath && Storage::disk('privatedisk')->exists($oldPath)) {
                Storage::disk('privatedisk')->delete($oldPath);
            }

            $storedPath = $request->file('file')->storeAs($baseDir, $newFileName, 'privatedisk');

            $document->file_name = $newFileName;
            $document->file_path = $storedPath;

        } else {
            // === Tidak ada perubahan (tidak upload & parameter sama) ===
            // Boleh dikosongkan atau return info
            // return response()->json(['message' => 'Tidak ada perubahan'], 200);
        }
        //////

        // Update metadata
        $document->parameter = $request->parameter;
        // $document->status = isset($request->user_id) ? 'Approved' : 'Pending';
        $document->status = 'Pending';
        $document->verif_notes = null;
        $document->save();

         // Simpan ke verval_logs
         $vervalLog = new VervalLog();
         $vervalLog->id_document = $document->id;
         $vervalLog->verval_status = isset($request->user_id) ? 'Reuploaded by Admin' : 'Reuploaded';
         $vervalLog->verified_by = Auth::id(); // Admin yang melakukan upload atau user itu sendiri
         $vervalLog->verif_notes = null; // Tidak ada catatan saat upload
         $vervalLog->save();


         // Update state jika oleh admin
        if (isset($request->user_id)) {
            $employee->update(['docs_progress_state' => true]);
            $user = $employee->user;
            $user->docs_update_state = true;
            $user->save();
        }

        return response()->json([
            'message' => 'Reupload berhasil.',
            'data'    => $document
        ]);
    }

    public function syncFiles(Request $request)
    {
        // Ambil user_id dari request, kalau tidak ada gunakan Auth::id()
        $user_id = $request->input('user_id', Auth::id());

        $user = User::findOrFail($user_id);
        $employee = $user->employee;
        $empStatus = $employee->employment_status;

        $empDocs = EmpDocument::where('id_employee', $employee->id)->get();

        foreach ($empDocs as $key => $doc) {

            $docType = DocType::where('id', $doc->id_doc_type)->first();
            if($docType->status != $empStatus) {
                $docTypeNew = DocType::where([
                    'label' => $docType->label,
                    'status' => $empStatus
                ])->first();

                if($docTypeNew){
                    $doc->update([
                        'id_doc_type' => $docTypeNew->id
                    ]);
                }
            }
        }

        $user->update([
            'docs_update_state' => true,
        ]);
        $employee = $user->employee;
        $employee->update(['docs_progress_state' => true]);

        return response()->json(['success' => true]);
    }

    // public function syncFiles()
    // {
    //     $userlogin = Auth::user();
    //     $user = User::find($userlogin ->id);
    //     $employee = $user->employee;

    //     $directory = 'documents/' . $employee->nip;
    //     $files = Storage::disk('privatedisk')->allFiles($directory);

    //     // Remove the directory prefix from each file path
    //     $filesWithoutPrefix = array_map(function ($file) use ($directory) {
    //         return str_replace($directory . '/', '', $file);
    //     }, $files);

    //     return $filesWithoutPrefix;
    //     return 'return before foreach';

    //     foreach ($filesWithoutPrefix as $key => $fileName) {
    //         $exploded = explode('_', $fileName);
    //         $length = count($exploded);

    //         $label = $exploded[0];
    //         $param = ($length == 3) ? $exploded[1] : null;
    //         $docType = DocType::where([
    //             'label' => $label,
    //             'status' => $employee->employment_status
    //         ])->first();

    //         if($docType) {
    //             $docTypeId = $docType->id;
    //         } else {
    //             continue;
    //         }
            
    //         // EmpDocument::firstOrCreate([
    //         //     'id_employee' => $employee->id,
    //         //     'id_doc_type' => $docTypeId,
    //         //     'parameter' => $param,
    //         //     'file_path' => $directory . '/' . $fileName,
    //         //     'file_name' => $fileName,
    //         //     'status' => 'Approved',
    //         // ]);
    //         EmpDocument::updateOrCreate(
    //             [
    //                 'id_employee' => $employee->id,
    //                 'id_doc_type' => $docType->id,
    //                 'parameter'   => $param,
    //             ],
    //             [
    //                 'file_path' => $directory.'/'.$fileName, // simpan path relatif di Drive
    //                 'file_name' => $fileName,
    //                 'status'    => 'Approved',
    //             ]
    //         );
    //     }

    //     $user->update([
    //         'docs_update_state' => true,
    //     ]);
    //     $employee = $user->employee;
    //     $employee->update(['docs_progress_state' => true]);

    //     return response()->json(['success' => true]);
    // }

}
