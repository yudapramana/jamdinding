<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Participant;

class PublicDocController extends Controller
{
    public function stream(Request $request, string $uuid, ?string $filename = null)
    { 
        // 1. Bersihkan UUID jika tidak sengaja tertempel ekstensi (misal: .pdf)
        $cleanUuid = preg_replace('/\.[^.\s]+$/', '', $uuid);

        // 2. Validasi format UUID yang bersih
        if (!preg_match('/^[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}$/', $cleanUuid)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Format UUID peserta tidak valid.'
            ], 422);
        }

        // 3. Cari participant berdasarkan UUID yang sudah dibersihkan
        $participant = Participant::where('uuid', $cleanUuid)->first();

        if (! $participant) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Peserta tidak ditemukan.'
            ], 404);
        }

        // 4. Cek jika filename kosong
        if (empty($filename)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Nama file tidak boleh kosong.'
            ], 422);
        }

        // ==========================================
        // AUTHORIZATION → PARTICIPANT POLICY
        // ==========================================
        $this->authorize('viewDocument', $participant);

        // ==========================================
        // SANITASI FILENAME
        // ==========================================
        $safeFilename = basename($filename);
        $relativePath = "documents/{$participant->uuid}/{$safeFilename}";

        $disk = Storage::disk('privatedisk');

        if (! $disk->exists($relativePath)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Dokumen tidak ditemukan atau sudah dihapus.'
            ], 404);
        }

        // ==========================================
        // STREAM FILE
        // ==========================================
        $absolutePath = $disk->path($relativePath);

        $response = response()->file($absolutePath, [
            'Content-Disposition'     => 'inline; filename="'.$safeFilename.'"',
            'Cache-Control'           => 'private, max-age=3600',
            'Content-Security-Policy' => "frame-ancestors 'self'",
        ]);

        try {
            $lastModTs = $disk->lastModified($relativePath);
            if ($lastModTs) {
                $response->setLastModified(
                    (new \DateTime())->setTimestamp($lastModTs)
                );

                if ($response->isNotModified($request)) {
                    return $response;
                }
            }
        } catch (\Throwable $e) {
            // abaikan jika adapter tidak mendukung
        }

        return $response;
    }

    public function invalidFormat()
    {
        return response()->json([
            'status'  => 'error',
            'message' => 'Format URL atau parameter dokumen tidak valid.'
        ], 422);
    }
}