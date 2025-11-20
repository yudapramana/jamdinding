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
                'file_url' => url('storage/' . $doc->file_path),
                'file_name' => $doc->file_name,
                'status' => $doc->status,
                'verif_notes' => $doc->verif_notes
            ];
            // 'file_url' => url('storage/documents/' . $doc->file_path),
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
                'file_url' => url('storage/' . $doc->file_path),
                'file_name' => $doc->file_name,
                'status' => $doc->status,
                'verif_notes' => $doc->verif_notes
            ];
            // 'file_url' => url('storage/documents/' . $doc->file_path),
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
                    'file_name' => ['File sudah diupload. Hapus dan tambahkan kembali jika ingin upload ulang.']
                ]
            ], 422);
        }

        /**
         * Turn off the Store Local Server
         *   $filePath = $file->storeAs(
         *   'documents/' . $employee->nip,
         *   $fileName,
         *   'public'
         *   );
         */
       
        $filePath = 'documents/' . $employee->nip . '/' . $fileName;
        Gdrive::put($filePath, $request->file('file'));

        // Simpan ke database dokumen
        $document = new EmpDocument();
        $document->id_employee = $employeeId;
        $document->id_doc_type = $request->id_doc_type;
        $document->doc_number = $request->doc_number;
        $document->file_name = $fileName;
        $document->doc_date = $request->doc_date;
        $document->parameter = $request->parameter;
        $document->file_path = $filePath;
        if (isset($request->user_id)) {
            $document->status = 'Approved';
        }
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


    // public function uploadDocument(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'id_doc_type' => 'required|exists:doc_types,id',
    //         'doc_number' => 'required|string|max:255',
    //         'doc_date' => 'required|date',
    //         'parameter' => 'nullable|string|max:255',
    //         'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048', // 2MB max
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'errors' => $validator->errors()
    //         ], 422);
    //     }


    //     // Upload file
    //     $file = $request->file('file');
    //     // $filename = time() . '_' . $file->getClientOriginalName();
    //     // $path = $file->storeAs('documents', $filename, 'public');
    //     $extension = $file->getClientOriginalExtension();

    //     // return 'USERID: ' . $request->user_id;

    //     if(isset($request->user_id)) {
    //         $user = User::find($request->user_id);
    //         $employee = $user->employee;
    //     } else {
    //         $employee = Auth::user()->employee;
    //     }

    //     $employeeId = $employee->id;
    //     $docType = DocType::find($request->id_doc_type);

    //     // Buat nama file
    //     $fileName = $docType->label . ($request->parameter ?? '') . '_' . $employee->nip . '.' . $extension;
    //     // $fileName = $docType->label . $request->parameter . '_' .$employee->nip . '.'. $extension;

    //     // Cek apakah file_name sudah ada di database
    //     $fileNameExists = EmpDocument::where('file_name', $fileName)->exists();
    //     if ($fileNameExists) {
    //         return response()->json([
    //             'errors' => [
    //                 'file_name' => ['File sudah diupload. Hapus dan tambahkan kembali jika ingin upload ulang.']
    //             ]
    //         ], 422);
    //     }

    //     $filePath = $file->storeAs(
    //         'documents/'.$employee->nip,
    //         $fileName,
    //         'public'
    //     );

    //     // Simpan ke database
    //     $document = new EmpDocument();
    //     $document->id_employee = $employeeId;
    //     $document->id_doc_type = $request->id_doc_type;
    //     $document->doc_number = $request->doc_number;
    //     $document->file_name = $fileName;
    //     $document->doc_date = $request->doc_date;
    //     $document->parameter = $request->parameter;
    //     $document->file_path = $filePath;
    //     if(isset($request->user_id)) {
    //         $document->status = 'Approved';
    //     }
    //     $document->save();

    //     if(isset($request->user_id)) {
    //         $user->docs_update_state = true;
    //         $user->save();
    //     }

    //     return response()->json([
    //         'message' => 'Dokumen berhasil diupload.'
    //     ]);
    // }

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

        $oldPath           = $document->file_path; // path di Google Drive (contoh: documents/1988xxxx/AKTA_...pdf)
        $parameterChanged  = $parsed['parameter'] != $request->parameter;
        $fileUploaded      = $request->hasFile('file');

        // === Base directory per pegawai (pastikan ada di Drive)
        $baseDir = 'documents/'.$employee->nip;
        Gdrive::makeDir($baseDir);
        
        if ($parameterChanged && !$fileUploaded) {
            // === CASE: Ubah parameter saja ===
            $parsed['parameter'] = $request->parameter;

            $newFileName = buildFileName(
                $parsed['label'],
                $parsed['parameter'],
                $parsed['nip'],
                $parsed['extension']
            );

            $newPath = $baseDir.'/'.$newFileName;

            // Cek apakah file lama ada
            if ($oldPath && Storage::disk('google')->exists($oldPath)) {
                 // Ambil konten file lama (string)
                $content = Storage::disk('google')->get($oldPath);

                // Pastikan folder tujuan ada (opsional, kalau belum pasti dibuat di tempat lain)
                // Yaza bisa bikin folder:
                // use Yaza\LaravelGoogleDriveStorage\Gdrive;
                // Gdrive::makeDir(dirname($newPath));

                // Tulis ke path baru sebagai konten string
                Storage::disk('google')->put($newPath, $content);

                // Hapus yang lama
                Storage::disk('google')->delete($oldPath);

                // Update metadata dokumen
                $document->file_name = $newFileName;
                $document->file_path = $newPath;
            }

            // if (Storage::disk('public')->exists($oldPath)) {
            //     Storage::disk('public')->move($oldPath, $newPath);

            //     $document->file_name = $newFileName;
            //     $document->file_path = $newPath;
            // }
        }
        elseif ($fileUploaded) {
            // === CASE: Upload file baru (parameter bisa sama atau berubah) ===
            $parsed['parameter'] = $request->parameter;

            $extension = $request->file('file')->getClientOriginalExtension();
            $newFileName = buildFileName(
                $parsed['label'],
                $parsed['parameter'],
                $parsed['nip'],
                $extension
            );

            $newPath = $baseDir.'/'.$newFileName;
    
            // Hapus file lama jika ada
            if ($oldPath && Storage::disk('google')->exists($oldPath)) {
                Gdrive::delete($oldPath);
            }

            // Simpan file baru ke Drive
            // Gdrive::put mendukung UploadedFile langsung
            Gdrive::put($newPath, $request->file('file'));

            // Update metadata dokumen
            $document->file_name = $newFileName;
            $document->file_path = $newPath;

            // hapus file lama
            // if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            //     Storage::disk('public')->delete($oldPath);
            // }

            // simpan file baru
            // $storedPath = $request->file('file')->storeAs('documents/'.$employee->nip, $newFileName, 'public');

            // $document->file_name = $newFileName;
            // $document->file_path = $storedPath;
        }
        //////

        // Update metadata
        $document->parameter = $request->parameter;
        $document->status = isset($request->user_id) ? 'Approved' : 'Pending';
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

    // public function syncFiles()
    // {
    //     $userlogin = Auth::user();
    //     $user = User::find($userlogin ->id);
    //     $employee = $user->employee;

    //     $directory = 'documents/' . $employee->nip;
    //     $files = Storage::disk('public')->allFiles($directory);

    //     // Remove the directory prefix from each file path
    //     $filesWithoutPrefix = array_map(function ($file) use ($directory) {
    //         return str_replace($directory . '/', '', $file);
    //     }, $files);

    //     foreach ($filesWithoutPrefix as $key => $fileName) {
    //         $exploded = explode('_', $fileName);
    //         $length = count($exploded);

    //         $label = $exploded[0];
    //         $param = ($length == 3) ? $exploded[1] : null;
    //         $docType = DocType::where('label', $label)->first();
    //         if($docType) {
    //             $docTypeId = $docType->id;
    //         } else {
    //             continue;
    //             return 'apasih';
    //         }
            
    //         EmpDocument::firstOrCreate([
    //             'id_employee' => $employee->id,
    //             'id_doc_type' => $docTypeId,
    //             'parameter' => $param,
    //             'file_path' => $directory . '/' . $fileName,
    //             'file_name' => $fileName,
    //             'status' => 'Approved',
    //         ]);
    //     }

    //     $user->update([
    //         'docs_update_state' => true,
    //     ]);
    //     $employee = $user->employee;
    //     $employee->update(['docs_progress_state' => true]);

    //     return response()->json(['success' => true]);
    // }

    public function syncFiles()
    {
        $userlogin = Auth::user();
        $user = User::find($userlogin ->id);
        $employee = $user->employee;

        $directory = 'documents/'.$employee->nip;

        // Pastikan folder ada (aman dipanggil berulang)
        Gdrive::makeDir($directory);

        // Ambil daftar file 1-level di folder NIP
        // (kalau kamu pakai subfolder, ganti ke allFiles($directory))
        $files = Storage::disk('google')->files($directory);

        // Filter hanya ekstensi yang kita dukung
        $allowedExt = ['pdf', 'jpg', 'jpeg', 'png'];

        // Safety: kalau kosong, tetap update state & kembali sukses
        if (empty($files)) {
            $user->update(['docs_update_state' => true]);
            $employee->update(['docs_progress_state' => true]);
            return response()->json(['success' => true, 'synced' => 0]);
        }

        $synced = 0;

        DB::beginTransaction();
        try {
            foreach ($files as $path) {
                // Ambil nama file tanpa path
                $fileName = basename($path);

                // Lewati file/berkas tanpa ekstensi yang didukung
                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExt, true)) {
                    continue;
                }

                // Ambil nama tanpa ekstensi, lalu pecah dengan "_"
                $nameOnly = pathinfo($fileName, PATHINFO_FILENAME);
                $parts = explode('_', $nameOnly);

                // Pola: LABEL[_PARAM]_<NIP>
                // Contoh 3 part: AKTA_PARAM_1988xxxx
                // Contoh 2 part: AKTA_1988xxxx
                if (count($parts) < 2) {
                    // tidak sesuai pola minimal
                    continue;
                }

                $label = $parts[0] ?? null;
                $maybeNip = $parts[count($parts) - 1] ?? null;
                $param = (count($parts) === 3) ? ($parts[1] ?? null) : null;

                // (Opsional) validasi NIP dari nama file sama dengan NIP pegawai
                if ($maybeNip !== $employee->nip) {
                    // kalau sering beda, silakan di-nonaktifkan pengecekan ini
                    continue;
                }

                if (!$label) {
                    continue;
                }

                // Cari DocType berdasarkan label
                $docType = DocType::where('label', $label)->first();
                if (!$docType) {
                    // label tidak terdaftar → skip saja
                    continue;
                }

                // Upsert dokumen: unik per (employee, doctype, parameter)
                EmpDocument::updateOrCreate(
                    [
                        'id_employee' => $employee->id,
                        'id_doc_type' => $docType->id,
                        'parameter'   => $param,
                    ],
                    [
                        'file_path' => $directory.'/'.$fileName, // simpan path relatif di Drive
                        'file_name' => $fileName,
                        'status'    => 'Approved',
                    ]
                );

                $synced++;
            }

            // Update flags
            $user->update(['docs_update_state' => true]);
            $employee->update(['docs_progress_state' => true]);

            DB::commit();

            return response()->json([
                'success' => true,
                'synced'  => $synced,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            // Logging disarankan
            // \Log::error('syncFiles error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Sync gagal: '.$e->getMessage(),
            ], 500);
        }
    }

}
