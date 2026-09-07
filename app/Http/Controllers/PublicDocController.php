<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Participant;

class PublicDocController extends Controller
{
    public function stream(Request $request, Participant $participant, string $filename)
    {   
        // ==========================================
        // AUTHORIZATION → PARTICIPANT POLICY
        // ==========================================
        $this->authorize('viewDocument', $participant);

        // ==========================================
        // SANITASI FILENAME
        // ==========================================
        $safeFilename = basename($filename);

        // path relatif ke disk
        $relativePath = "documents/{$participant->uuid}/{$safeFilename}";

        $disk = Storage::disk('privatedisk');

        if (! $disk->exists($relativePath)) {
            // Mengembalikan JSON dengan status 404 (Not Found)
            // Ini jauh lebih aman untuk Vue / SPA dan mencegah reload loop
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

        // ==========================================
        // SUPPORT 304 NOT MODIFIED
        // ==========================================
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
}