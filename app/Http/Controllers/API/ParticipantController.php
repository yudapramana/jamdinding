<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ParticipantController extends Controller
{
    /**
     * GET /api/v1/participants
     * - SUPERADMIN: bisa lihat semua peserta (dengan filter event_id opsional)
     * - Role lain: dibatasi event_id (dari request atau user->event_id)
     */
    public function index(Request $request)
    {
        $user     = $request->user();
        $roleSlug = optional($user->role)->slug ?? null;

        $search   = $request->get('search');
        $perPage  = (int)$request->get('per_page', 10);
        $eventId  = $request->get('event_id', $user->event_id ?? null);

        $query = Participant::query()
            ->with(['competitionBranch', 'regency', 'district'])
            ->orderBy('full_name');

        // filter event untuk non-superadmin
        if ($roleSlug !== 'superadmin') {
            if ($eventId) {
                $query->where('event_id', $eventId);
            } else {
                $query->whereRaw('1 = 0'); // tidak ada data
            }
        } else {
            // SUPERADMIN boleh filter manual event_id kalau dikirim
            if ($eventId) {
                $query->where('event_id', $eventId);
            }
        }

        // search by nama / nik / phone
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        $participants = $query->paginate($perPage);

        return response()->json($participants);
    }

    /**
     * POST /api/v1/participants
     */
    public function store(Request $request)
    {
        $user     = $request->user();
        $roleSlug = optional($user->role)->slug ?? null;

        // event_id: boleh dikirim dari front-end (localStorage event_data), 
        // untuk non-superadmin bisa dipaksa pakai user->event_id kalau mau.
        $eventId = $request->get('event_id', $user->event_id ?? null);

        if (!$eventId) {
            return response()->json([
                'message' => 'Event ID is required.',
            ], 422);
        }

        $data = $this->validateData($request, $eventId);

        $data['event_id'] = $eventId;

        $participant = Participant::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Participant created successfully.',
            'data'    => $participant->load(['competitionBranch', 'regency']),
        ], 201);
    }

    /**
     * GET /api/v1/participants/{participant}
     */
    public function show(Request $request, Participant $participant)
    {
        $user     = $request->user();
        $roleSlug = optional($user->role)->slug ?? null;

        if ($roleSlug !== 'superadmin' && $user->event_id && $user->event_id !== $participant->event_id) {
            return response()->json([
                'message' => 'You are not allowed to view this participant.',
            ], 403);
        }

        return response()->json(
            $participant->load(['competitionBranch', 'regency'])
        );
    }

    /**
     * PUT/PATCH /api/v1/participants/{participant}
     */
    public function update(Request $request, Participant $participant)
    {
        $user     = $request->user();
        $roleSlug = optional($user->role)->slug ?? null;

        if ($roleSlug !== 'superadmin' && $user->event_id && $user->event_id !== $participant->event_id) {
            return response()->json([
                'message' => 'You are not allowed to update this participant.',
            ], 403);
        }

        $eventId = $participant->event_id;

        $data = $this->validateData($request, $eventId, $participant->id, true);

        $participant->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Participant updated successfully.',
            'data'    => $participant->load(['competitionBranch', 'regency']),
        ]);
    }

    /**
     * DELETE /api/v1/participants/{participant}
     */
    public function destroy(Request $request, Participant $participant)
    {
        $user     = $request->user();
        $roleSlug = optional($user->role)->slug ?? null;

        if ($roleSlug !== 'superadmin' && $user->event_id && $user->event_id !== $participant->event_id) {
            return response()->json([
                'message' => 'You are not allowed to delete this participant.',
            ], 403);
        }

        $participant->delete();

        return response()->json([
            'success' => true,
            'message' => 'Participant deleted successfully.',
        ]);
    }

    /**
     * Validasi reusable (create & update)
     */
    protected function validateData(Request $request, $eventId, $participantId = null, $isUpdate = false): array
    {
        $rules = [
            'event_competition_branch_id' => ['required', 'exists:event_competition_branches,id'],
            'nik'                         => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:30',
                // unique per event_id
                Rule::unique('participants', 'nik')
                    ->where('event_id', $eventId)
                    ->ignore($participantId),
            ],
            'full_name'                   => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:150'],
            'phone_number'                => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:30'],
            'place_of_birth'              => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:100'],
            'date_of_birth'               => [$isUpdate ? 'sometimes' : 'required', 'date'],
            'gender'                      => [$isUpdate ? 'sometimes' : 'required', Rule::in(['MALE', 'FEMALE'])],

            'province_id'                 => [$isUpdate ? 'sometimes' : 'required', 'exists:provinces,id'],
            'regency_id'                  => [$isUpdate ? 'sometimes' : 'required', 'exists:regencies,id'],
            'district_id'                 => [$isUpdate ? 'sometimes' : 'required', 'exists:districts,id'],
            'village_id'                  => ['nullable', 'exists:villages,id'],

            'address'                     => [$isUpdate ? 'sometimes' : 'required', 'string'],
            'education'                   => [
                $isUpdate ? 'sometimes' : 'required',
                Rule::in(['SD', 'SMP', 'SMA', 'D1', 'D2', 'D3', 'D4', 'S1', 'S2', 'S3']),
            ],

            'bank_account_number'         => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:50'],
            'bank_account_name'           => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:150'],
            'bank_name'                   => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:50'],

            'photo_url'                   => ['nullable', 'string', 'max:255'],
            'id_card_url'                 => ['nullable', 'string', 'max:255'],
            'family_card_url'             => ['nullable', 'string', 'max:255'],
            'bank_book_url'               => ['nullable', 'string', 'max:255'],
            'certificate_url'             => ['nullable', 'string', 'max:255'],
            'other_url'                   => ['nullable', 'string', 'max:255'],

            'tanggal_terbit_ktp'          => ['nullable', 'date'],
            'tanggal_terbit_kk'           => ['nullable', 'date'],
        ];

        return $request->validate($rules);
    }
}
