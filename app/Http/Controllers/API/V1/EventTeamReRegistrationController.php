<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\EventTeam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EventTeamReRegistrationController extends Controller
{
    /**
     * Proses daftar ulang TIM (cabang grup)
     */
    public function store(Request $request, EventTeam $eventTeam)
    {
        // =============================
        // VALIDASI DOMAIN
        // =============================

        // Pastikan ini memang cabang grup
        if (! $eventTeam->eventGroup || ! $eventTeam->eventGroup->is_team) {
            return response()->json([
                'message' => 'Tim ini bukan cabang grup.',
            ], 422);
        }

        // Daftar ulang butuh minimal 2 anggota yang sudah lolos verifikasi pendaftaran awal
        // (mis. cabang Khutbah Jumat hanya 2 orang). Tim dengan 1 anggota tidak boleh.
        $minVerified = 2;

        $verifiedCount = $eventTeam->participants()
            ->where('registration_status', 'verified')
            ->count();

        if ($verifiedCount < $minVerified) {
            return response()->json([
                'message' => "Tim belum memenuhi syarat daftar ulang. Dibutuhkan minimal {$minVerified} anggota yang sudah terverifikasi (saat ini {$verifiedCount}).",
            ], 422);
        }

        // =============================
        // VALIDASI INPUT
        // =============================
        $data = $request->validate([
            'reregistration_status' => [
                'required',
                Rule::in(['not_yet', 'verified', 'rejected']),
            ],
            'reregistration_notes' => ['nullable', 'string'],
        ]);

        // Jika ACC → nomor WAJIB sudah ditetapkan
        // if ($data['reregistration_status'] === 'verified') {
        //     if (! $eventTeam->participant_number) {
        //         return response()->json([
        //             'message' => 'Nomor peserta tim belum ditetapkan.',
        //         ], 422);
        //     }
        // }

        // Jika ditolak → catatan wajib
        if (
            $data['reregistration_status'] === 'rejected' &&
            empty(trim($data['reregistration_notes'] ?? ''))
        ) {
            return response()->json([
                'message' => 'Catatan wajib diisi jika tim ditolak.',
                'errors'  => [
                    'reregistration_notes' => [
                        'Catatan wajib diisi jika status rejected.',
                    ],
                ],
            ], 422);
        }

        // =============================
        // SIMPAN DATA
        // =============================
        $eventTeam->reregistration_status = $data['reregistration_status'];
        $eventTeam->reregistration_notes  = $data['reregistration_notes'] ?? null;

        if (in_array($data['reregistration_status'], ['verified','rejected'], true)) {
            $eventTeam->reregistered_at = now();
            $eventTeam->reregistered_by = Auth::id();
        }

        if ($data['reregistration_status'] === 'not_yet') {
            $eventTeam->reregistered_at = null;
            $eventTeam->reregistered_by = null;
        }

        $eventTeam->save();

        // =============================
        // OPSIONAL: CASCADE KE ANGGOTA
        // =============================
        $eventTeam->participants()->update([
            'reregistration_status' => $eventTeam->reregistration_status,
            'reregistration_notes'  => $eventTeam->reregistration_notes,
            'reregistered_at'       => $eventTeam->reregistered_at,
            'reregistered_by'       => $eventTeam->reregistered_by,
        ]);

        return response()->json([
            'message' => 'Daftar ulang tim berhasil diproses.',
            'data' => $eventTeam->fresh([
                'event',
                'eventGroup',
                'participants.participant',
            ]),
        ]);
    }
}
