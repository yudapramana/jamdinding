<?php

namespace App\Http\Controllers\API\V1;

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
            'event_id' => ['nullable', 'exists:events,id'],
            'event_participant_id' => ['nullable', 'exists:event_participants,id'],

            'status' => ['required', 'in:verified,rejected'],

            // ✅ sesuai migration event_participants.registration_status
            'registration_status' => [
                'required',
                Rule::in(['bank_data','process','verified','need_revision','rejected','disqualified']),
            ],

            'checked_photo' => ['boolean'],
            'checked_id_card' => ['boolean'],
            'checked_family_card' => ['boolean'],
            'checked_bank_book' => ['boolean'],
            'checked_certificate' => ['boolean'],
            'checked_other' => ['boolean'],

            'checked_identity' => ['boolean'],
            'checked_contact' => ['boolean'],
            'checked_domicile' => ['boolean'],
            'checked_education' => ['boolean'],
            'checked_bank_account' => ['boolean'],
            'checked_document_dates' => ['boolean'],

            'field_matches' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
        ]);


        // set default false kalau tidak dikirim (supaya aman)
        $boolFields = [
            'checked_photo','checked_id_card','checked_family_card','checked_bank_book','checked_certificate','checked_other',
            'checked_identity','checked_contact','checked_domicile','checked_education','checked_bank_account','checked_document_dates',
            ];

            foreach ($boolFields as $field) {
            $data[$field] = (bool)($data[$field] ?? false);
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
            'verified_at' => now(),
            ]);


        if (!empty($data['event_participant_id'])) {
            EventParticipant::where('id', $data['event_participant_id'])
                ->update([
                    'registration_status' => $data['registration_status'],
                    'verified_by' => Auth::id(),
                    'verified_at' => now(),
                ]);
        } elseif (!empty($data['event_id'])) {
            EventParticipant::where('event_id', $data['event_id'])
                ->where('participant_id', $participant->id)
                ->update([
                    'registration_status' => $data['registration_status'],
                    'verified_by' => Auth::id(),
                    'verified_at' => now(),
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
