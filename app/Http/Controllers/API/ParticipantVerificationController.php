<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EventParticipant;
use App\Models\Participant;
use App\Models\ParticipantVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;


class ParticipantVerificationController extends Controller
{
    /**
     * (Opsional) daftar semua verifikasi untuk satu participant
     */
    public function index(Participant $participant)
    {
        $this->authorize('view', $participant); // kalau pakai policy

        $verifications = ParticipantVerification::with('verifiedBy')
            ->where('participant_id', $participant->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $verifications,
        ]);
    }

    /**
     * Simpan satu sesi verifikasi baru untuk participant tertentu.
     *
     * POST /api/v1/participants/{participant}/verifications
     */
    public function store(Request $request, Participant $participant)
    {
        // $this->authorize('verify', $participant); // opsional, kalau pakai policy khusus

        $data = $request->validate([
            // context event (opsional)
            'event_id' => ['nullable', 'exists:events,id'],
            'event_participant_id' => ['nullable', 'exists:event_participants,id'],

            // status verifikasi (participant_verifications.status)
            'status' => ['required', 'in:verified,rejected'],

            // 🔹 status pendaftaran (event_participants.status_pendaftaran)
            'status_pendaftaran' => [
                'required',
                Rule::in(['bankdata', 'proses', 'diterima', 'perbaiki', 'mundur', 'tolak']),
            ],

            // dokumen dicek?
            'checked_photo' => ['boolean'],
            'checked_id_card' => ['boolean'],
            'checked_family_card' => ['boolean'],
            'checked_bank_book' => ['boolean'],
            'checked_certificate' => ['boolean'],
            'checked_other' => ['boolean'],

            // kelompok data dicek?
            'checked_identity' => ['boolean'],
            'checked_contact' => ['boolean'],
            'checked_domicile' => ['boolean'],
            'checked_education' => ['boolean'],
            'checked_bank_account' => ['boolean'],
            'checked_document_dates' => ['boolean'],

            // detail hasil cek per field
            'field_matches' => ['nullable', 'array'],

            // catatan
            'notes' => ['nullable', 'string'],
        ]);


        // set default false kalau tidak dikirim (supaya aman)
        $boolFields = [
            'checked_photo',
            'checked_id_card',
            'checked_family_card',
            'checked_bank_book',
            'checked_certificate',
            'checked_other',

            'checked_identity',
            'checked_contact',
            'checked_domicile',
            'checked_education',
            'checked_bank_account',
            'checked_document_dates',
        ];

        foreach ($boolFields as $field) {
            $data[$field] = (bool) ($data[$field] ?? false);
        }

        $verification = ParticipantVerification::create([
            'participant_id' => $participant->id,
            'event_id' => $data['event_id'] ?? null,
            'event_participant_id' => $data['event_participant_id'] ?? null,
            'verified_by' => Auth::id(),

            'status' => $data['status'],

            'checked_photo' => $data['checked_photo'],
            'checked_id_card' => $data['checked_id_card'],
            'checked_family_card' => $data['checked_family_card'],
            'checked_bank_book' => $data['checked_bank_book'],
            'checked_certificate' => $data['checked_certificate'],
            'checked_other' => $data['checked_other'],

            'checked_identity' => $data['checked_identity'],
            'checked_contact' => $data['checked_contact'],
            'checked_domicile' => $data['checked_domicile'],
            'checked_education' => $data['checked_education'],
            'checked_bank_account' => $data['checked_bank_account'],
            'checked_document_dates' => $data['checked_document_dates'],

            'field_matches' => $data['field_matches'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        // return [
        //     'event_participant_id' => $data['event_participant_id'],
        //     'status_pendaftaran' => $data['status_pendaftaran']
        // ];

        // 🔹 Update status_pendaftaran di event_participants (kalau ada event_participant_id)
        if (!empty($data['event_participant_id']) && !empty($data['status_pendaftaran'])) {
            EventParticipant::where('id', $data['event_participant_id'])
                ->update([
                    'status_pendaftaran' => $data['status_pendaftaran'],
                ]);
        } elseif (!empty($data['event_id'])) {
            EventParticipant::where('event_id', $data['event_id'])
                ->where('participant_id', $participant->id)
                ->update([
                    'status_pendaftaran' => $data['status_pendaftaran'],
                ]);
        }



        return response()->json([
            'message' => 'Verifikasi peserta berhasil disimpan.',
            'data' => $verification->fresh('verifiedBy'),
        ], 201);
    }

    /**
     * (Opsional) tampilkan satu verifikasi tertentu
     */
    public function show(Participant $participant, ParticipantVerification $verification)
    {
        $this->authorize('view', $participant);

        if ($verification->participant_id !== $participant->id) {
            abort(404);
        }

        return response()->json([
            'data' => $verification->load('verifiedBy'),
        ]);
    }
}
