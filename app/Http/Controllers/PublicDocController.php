<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PublicDocController extends Controller
{
    public function stream(Request $request, string $nip, string $filename)
    {
        $user = Auth::user();

        // NIP pemilik dokumen (dari relasi user->employee)
        $userNip = optional($user->employee)->nip;

        // Aturan akses:
        // 1) Jika NIP user == NIP pada URL → izinkan (meski can_multiple_role false)
        // 2) Jika berbeda → hanya izinkan kalau can_multiple_role == true
        $isOwner      = $userNip && $userNip === $nip;
        $canMultiRole = (bool)($user->can_multiple_role ?? false);

        if (!$isOwner && !$canMultiRole) {
            abort(403, 'Forbidden');
        }

        // Sanitasi nama file (hindari traversal)
        $safeFilename = basename($filename);
        $relativePath = "documents/{$nip}/{$safeFilename}";

        $disk = Storage::disk('privatedisk');

        if (!$disk->exists($relativePath)) {
            return view('errors.404');
        }

        // Path absolut ke file di storage/app/public
        $absolutePath = $disk->path($relativePath);

        // Siapkan response file (inline)
        $response = response()->file($absolutePath, [
            'Content-Disposition'       => 'inline; filename="'.$safeFilename.'"',
            'Cache-Control'             => 'private, max-age=3600',
            "Content-Security-Policy"   => "frame-ancestors 'self'",
        ]);

        // Set Last-Modified + dukung 304 Not Modified
        try {
            $lastModTs = $disk->lastModified($relativePath); // unix ts
            if ($lastModTs) {
                $dt = (new \DateTime())->setTimestamp($lastModTs);
                $response->setLastModified($dt);
                if ($response->isNotModified($request)) {
                    // Symfony akan set 304 & kosongkan body
                    return $response;
                }
            }
        } catch (\Throwable $e) {
            // abaikan jika adapter tidak dukung lastModified
        }

        return $response;
    }

    private function notFoundOrForbidden(Request $request, int $status, string $message)
    {
        // HEAD request: kosongkan body, tetap kirim status
        if ($request->isMethod('HEAD')) {
            return response('', $status, ['Content-Type' => 'text/plain']);
        }

        // XHR/JSON
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => $message], $status);
        }

        // HTML biasa
        return response($message, $status);
    }
}
