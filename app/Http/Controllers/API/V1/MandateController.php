<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegionMandate;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class MandateController extends Controller
{
    /**
     * Cek status mandat wilayah untuk user yang sedang login pada event tertentu.
     *
     * Aturan:
     * - Hanya role dengan slug 'pendaftaran' yang wajib punya mandat.
     * - Role lain (superadmin, admin_event, dll) selalu dianggap 'allowed' = true.
     *
     * GET /api/v1/events/{event}/mandate-status
     */
    public function status(Event $event): JsonResponse
    {
        $user = Auth::user();

        // Role selain 'pendaftaran' tidak wajib mandat sama sekali
        if (($user->role->slug ?? null) !== 'pendaftaran') {
            return response()->json([
                'allowed'     => true,
                'region_type' => null,
                'region_id'   => null,
                'message'     => null,
            ]);
        }

        // Tentukan region_type berdasarkan level event (satu tingkat di bawah event_level)
        try {
            $regionType = $event->getContingentRegionType();
        } catch (\RuntimeException $e) {
            return response()->json([
                'allowed'     => false,
                'region_type' => null,
                'region_id'   => null,
                'message'     => 'Level event tidak valid untuk pengecekan mandat.',
            ], 200);
        }

        // Ambil region_id user sesuai region_type yang relevan
        $regionId = $user->getRegionId($regionType);

        if (!$regionId) {
            return response()->json([
                'allowed'     => false,
                'region_type' => $regionType,
                'region_id'   => null,
                'message'     => "Akun Anda tidak punya {$regionType} yang terdaftar.",
            ]);
        }

        $status = EventRegionMandate::getRegionMandateStatus($event->id, $regionType, $regionId);
        $allowed = $status === 'approved';

        $messages = [
            'not_uploaded' => 'Wilayah Anda belum upload mandat untuk event ini.',
            'uploaded'     => 'Mandat wilayah Anda sudah diupload, menunggu persetujuan (approval).',
            'approved'     => null,
            'rejected'     => 'Mandat wilayah Anda ditolak. Silakan upload ulang mandat yang valid.',
        ];

        return response()->json([
            'allowed'     => $allowed,
            'status'      => $status,
            'region_type' => $regionType,
            'region_id'   => $regionId,
            'message'     => $messages[$status] ?? null,
        ]);
    }
}