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
        // Optional: pakai policy kalau ada
        // $this->authorize('reRegister', $eventParticipant);

        // Hanya boleh daftar ulang kalau sudah DITERIMA di pendaftaran awal
        if ($eventParticipant->status_pendaftaran !== 'diterima') {
            return response()->json([
                'message' => 'Peserta belum berstatus diterima. Tidak dapat diproses daftar ulang.',
            ], 422);
        }

        $data = $request->validate([
            'status_daftar_ulang' => [
                'required',
                Rule::in(['belum', 'terverifikasi', 'gagal']),
            ],
            'daftar_ulang_notes' => ['nullable', 'string'],
        ]);

        $oldStatus = $eventParticipant->status_daftar_ulang;

        $eventParticipant->status_daftar_ulang = $data['status_daftar_ulang'];
        $eventParticipant->daftar_ulang_notes = $data['daftar_ulang_notes'] ?? null;

        // Set metadata waktu & petugas ketika sudah ada interaksi
        if (in_array($data['status_daftar_ulang'], ['proses', 'terverifikasi', 'gagal'], true)) {
            // Kalau sebelumnya masih 'tidak_dibuka' / 'belum', set waktu pertama kali
            if (empty($eventParticipant->daftar_ulang_at)) {
                $eventParticipant->daftar_ulang_at = now();
            }
            $eventParticipant->daftar_ulang_by = Auth::id();
        }

        $eventParticipant->save();

        return response()->json([
            'message' => 'Status daftar ulang peserta berhasil diperbarui.',
            'data'    => $eventParticipant->fresh('participant', 'event', 'competitionBranch'),
        ]);
    }
}
