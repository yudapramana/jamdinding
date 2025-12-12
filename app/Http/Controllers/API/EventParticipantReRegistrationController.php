<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EventParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EventParticipantReRegistrationController extends Controller
{
    

    public function store(Request $request, EventParticipant $eventParticipant)
    {
        // Optional: policy
        // $this->authorize('reRegister', $eventParticipant);

        // Hanya boleh daftar ulang kalau pendaftaran awal SUDAH TERVERIFIKASI
        if ($eventParticipant->registration_status !== 'verified') {
            return response()->json([
                'message' => 'Peserta belum terverifikasi pada pendaftaran awal. Tidak dapat diproses daftar ulang.',
            ], 422);
        }

        $data = $request->validate([
            'reregistration_status' => [
                'required',
                Rule::in(['not_yet', 'verified', 'rejected']),
            ],
            'reregistration_notes' => ['nullable', 'string'],
        ]);

        // Jika ditolak, notes wajib
        if ($data['reregistration_status'] === 'rejected' && empty(trim($data['reregistration_notes'] ?? ''))) {
            return response()->json([
                'message' => 'Catatan wajib diisi jika daftar ulang ditolak.',
                'errors'  => ['reregistration_notes' => ['Catatan wajib diisi jika status rejected.']],
            ], 422);
        }

        // not_yet: biasanya hanya untuk reset (opsional). Jika kamu tidak mau reset, blok saja.
        $eventParticipant->reregistration_status = $data['reregistration_status'];
        $eventParticipant->reregistration_notes  = $data['reregistration_notes'] ?? null;

        // Metadata: set saat ada keputusan (verified / rejected)
        if (in_array($data['reregistration_status'], ['verified', 'rejected'], true)) {
            $eventParticipant->reregistered_at = now();
            $eventParticipant->reregistered_by = Auth::id();
        }

        // Kalau reset ke not_yet, bersihkan metadata (opsional)
        if ($data['reregistration_status'] === 'not_yet') {
            $eventParticipant->reregistered_at = null;
            $eventParticipant->reregistered_by = null;
        }

        $eventParticipant->save();

        return response()->json([
            'message' => 'Status daftar ulang peserta berhasil diperbarui.',
            'data'    => $eventParticipant->fresh([
                'participant',
                'event',
                'eventBranch',
                'eventGroup',
                'eventCategory',
                'reregistrator', // kalau relasi user ada
            ]),
        ]);
    }

}
