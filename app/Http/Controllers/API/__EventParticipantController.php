<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Event;
use App\Models\EventBranch;
use App\Models\EventCategory;
use App\Models\EventGroup;
use App\Models\EventParticipant;
use App\Models\Participant;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Auth;
use Illuminate\Validation\ValidationException;

class __EventParticipantController extends Controller
{
    public function index(Request $request, Event $event)
    {
        $user     = $request->user();
        $roleSlug = optional($user->role)->slug ?? null;

        $search                = $request->get('search');
        $perPage               = (int) $request->get('per_page', 10);
        $registrationStatus    = $request->get('registration_status');
        $reregistrationStatus  = $request->get('reregistration_status');

        $eventId = $event->id;
        $query = EventParticipant::query()
            ->with(['participant', 'eventGroup', 'eventCategory', 'eventBranch'])
            ->when($eventId, function ($q) use ($eventId) {
                $q->where('event_id', $eventId);
            })
            ->join('participants as p', 'p.id', '=', 'event_participants.participant_id')
            ->select('event_participants.*')
            ->orderBy('p.full_name');

        if ($registrationStatus) {
            $query->where('registration_status', $registrationStatus);
        }

        if ($reregistrationStatus) {
            $query->where('reregistration_status', $reregistrationStatus);
        }

        if ($roleSlug !== 'superadmin') {
            $user  = Auth::user();

            if ($event->event_level === 'province') {
                $query->where('p.province_id', $event->province_id)
                    ->where('p.regency_id', $user->regency_id);
            } elseif ($event->event_level === 'regency') {
                $query->where('p.province_id', $event->province_id)
                    ->where('p.regency_id', $event->regency_id)
                    ->where('p.district_id', $user->district_id);
            }
        }

        if ($search) {
            $query->whereHas('participant', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%");
            })->orWhere('contingent', 'like', "%{$search}%");
        }


        return $query->paginate($perPage);
    }

    /**
     * Endpoint simple master untuk Vue:
     * - event
     * - event_groups
     * - event_categories
     */
    public function simple(Event $event)
    {
        $branches = $event->eventBranches()
            ->select('id', 'branch_name')
            ->orderBy('branch_name')
            ->get();

        $groups = $event->eventGroups()
            ->select('id', 'group_name', 'max_age')
            ->orderBy('group_name')
            ->get();

        $categories = $event->eventCategories()
            ->select('id', 'category_name', 'full_name', 'group_id', 'branch_id')
            ->orderBy('category_name')
            ->get();

        return response()->json([
            'event'      => $event,
            'branches'     => $branches,
            'groups'     => $groups,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request, Event $event)
    {
        $validated = $request->validate([
            'participant_id'        => ['required', 'exists:participants,id'],
            'event_group_id'        => ['required', 'exists:event_groups,id'],
            'event_category_id'     => ['required', 'exists:event_categories,id'],
            'contingent'            => ['nullable', 'string'],
            'registration_status'   => ['required', Rule::in([
                'bank_data','process','verified','need_revision','rejected','disqualified'
            ])],
            'registration_notes'    => ['nullable', 'string'],
            'reregistration_status' => ['nullable', Rule::in([
                'not_yet','verified','rejected'
            ])],
            'reregistration_notes'  => ['nullable', 'string'],
        ]);

        // Pastikan satu peserta hanya sekali per event
        $exists = EventParticipant::where('event_id', $event->id)
            ->where('participant_id', $validated['participant_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Peserta ini sudah terdaftar di event ini.'
            ], 422);
        }

        // Ambil peserta untuk hitung usia
        $participant = Participant::findOrFail($validated['participant_id']);
        $dob = Carbon::parse($participant->date_of_birth);

        // Ambil tanggal referensi umur (pakai tanggal_batas_umur kalau ada, kalau tidak pakai event_date atau now)
        $refDate = $event->tanggal_batas_umur
            ?? $event->start_date
            ?? now();

        $ref = Carbon::parse($refDate);

        $ageYears  = $dob->diffInYears($ref);
        $tmp       = $dob->copy()->addYears($ageYears);
        $ageMonths = $tmp->diffInMonths($ref);
        $tmp2      = $tmp->copy()->addMonths($ageMonths);
        $ageDays   = $tmp2->diffInDays($ref);

        $ep = new EventParticipant();
        $ep->event_id             = $event->id;
        $ep->participant_id       = $participant->id;
        $ep->event_group_id       = $validated['event_group_id'];
        $ep->event_category_id    = $validated['event_category_id'];
        $ep->contingent           = $validated['contingent'] ?? null;
        $ep->registration_status  = $validated['registration_status'];
        $ep->registration_notes   = $validated['registration_notes'] ?? null;
        $ep->reregistration_status = $validated['reregistration_status'] ?? 'not_yet';
        $ep->reregistration_notes = $validated['reregistration_notes'] ?? null;

        $ep->age_year  = $ageYears;
        $ep->age_month = $ageMonths;
        $ep->age_day   = $ageDays;

        $ep->save();

        return response()->json($ep, 201);
    }

    public function show(EventParticipant $eventParticipant)
    {
        $eventParticipant->load(['participant', 'eventGroup', 'eventCategory']);

        return response()->json($eventParticipant);
    }

    public function update(Request $request, EventParticipant $eventParticipant)
    {
        $validated = $request->validate([
            'event_id'             => ['required', 'exists:events,id'],
            'participant_id'       => ['required', 'exists:participants,id'],
            'event_group_id'       => ['required', 'exists:event_groups,id'],
            'event_category_id'    => ['required', 'exists:event_categories,id'],
            'contingent'           => ['nullable', 'string'],
            'registration_status'  => ['required', Rule::in([
                'bank_data','process','verified','need_revision','rejected','disqualified'
            ])],
            'registration_notes'   => ['nullable', 'string'],
            'reregistration_status'=> ['nullable', Rule::in([
                'not_yet','verified','rejected'
            ])],
            'reregistration_notes' => ['nullable', 'string'],
        ]);

        $eventParticipant->fill($validated);

        // Re-hitungan usia kalau perlu (misalnya date_of_birth peserta berubah)
        $participant = Participant::findOrFail($validated['participant_id']);
        $event = Event::findOrFail($validated['event_id']);

        $dob = Carbon::parse($participant->date_of_birth);
        $refDate = $event->tanggal_batas_umur
            ?? $event->start_date
            ?? now();
        $ref = Carbon::parse($refDate);

        $ageYears  = $dob->diffInYears($ref);
        $tmp       = $dob->copy()->addYears($ageYears);
        $ageMonths = $tmp->diffInMonths($ref);
        $tmp2      = $tmp->copy()->addMonths($ageMonths);
        $ageDays   = $tmp2->diffInDays($ref);

        $eventParticipant->age_year  = $ageYears;
        $eventParticipant->age_month = $ageMonths;
        $eventParticipant->age_day   = $ageDays;

        $eventParticipant->save();

        return response()->json($eventParticipant);
    }

    public function destroy(EventParticipant $eventParticipant)
    {
        $eventParticipant->delete();

        return response()->json(['message' => 'Event participant deleted.']);
    }

        /**
     * Simpan / update Participant + EventParticipant sekaligus
     * Endpoint: POST /api/v1/event-participants/eventParticipant
     *
     * Body contoh:
     * {
     *   "participant": {
     *     "id": 1|null,
     *     "nik": "...",
     *     "full_name": "...",
     *     ...
     *   },
     *   "event_participant": {
     *     "id": 10|null,
     *     "event_id": 1,
     *     "event_group_id": 2,
     *     "event_category_id": 3,
     *     "event_branch_id": 4,
     *     "registration_status": "process",
     *     ...
     *   }
     * }
     */
    public function eventParticipant(Request $request)
    {
        // Decode JSON dari FormData
        $participantPayload     = json_decode($request->input('participant', '{}'), true) ?: [];
        $eventParticipantPayload = json_decode($request->input('event_participant', '{}'), true) ?: [];

        // Merge ke request supaya validasi bisa pakai dot notation
        $request->merge([
            'participant'       => $participantPayload,
            'event_participant' => $eventParticipantPayload,
        ]);

        $participantId      = $participantPayload['id'] ?? null;
        $eventParticipantId = $eventParticipantPayload['id'] ?? null;

        // ============================
        // CUSTOM MESSAGES
        // ============================
        $messages = [
            // PARTICIPANT
            'participant.id.exists' => 'ID peserta tidak valid.',

            'participant.nik.required' => 'NIK wajib diisi.',
            'participant.nik.string'   => 'NIK harus berupa teks.',
            'participant.nik.max'      => 'NIK maksimal 50 karakter.',
            'participant.nik.unique'   => 'NIK sudah terdaftar dalam sistem.',

            'participant.full_name.required' => 'Nama lengkap wajib diisi.',
            'participant.full_name.string'   => 'Nama lengkap harus berupa teks.',
            'participant.full_name.max'      => 'Nama lengkap maksimal 255 karakter.',

            'participant.phone_number.required' => 'Nomor HP wajib diisi.',
            'participant.phone_number.string'   => 'Nomor HP harus berupa teks.',
            'participant.phone_number.max'      => 'Nomor HP maksimal 50 karakter.',

            'participant.place_of_birth.required' => 'Tempat lahir wajib diisi.',
            'participant.place_of_birth.string'   => 'Tempat lahir harus berupa teks.',
            'participant.place_of_birth.max'      => 'Tempat lahir maksimal 100 karakter.',

            'participant.date_of_birth.required' => 'Tanggal lahir wajib diisi.',
            'participant.date_of_birth.date'     => 'Tanggal lahir tidak valid.',

            'participant.gender.required' => 'Jenis kelamin wajib dipilih.',
            'participant.gender.in'       => 'Jenis kelamin harus MALE atau FEMALE.',

            'participant.education.required' => 'Pendidikan wajib diisi.',
            'participant.education.string'   => 'Pendidikan harus berupa teks.',
            'participant.education.max'      => 'Pendidikan maksimal 100 karakter.',

            'participant.address.required' => 'Alamat wajib diisi.',
            'participant.address.string'   => 'Alamat harus berupa teks.',

            'participant.province_id.required' => 'Provinsi wajib dipilih.',
            'participant.province_id.exists'   => 'Provinsi tidak valid.',

            'participant.regency_id.required' => 'Kab/Kota wajib dipilih.',
            'participant.regency_id.exists'   => 'Kab/Kota tidak valid.',

            'participant.district_id.required' => 'Kecamatan wajib dipilih.',
            'participant.district_id.exists'   => 'Kecamatan tidak valid.',

            'participant.village_id.required' => 'Nagari/Desa wajib dipilih.',
            'participant.village_id.exists'   => 'Nagari/Desa tidak valid.',

            'participant.bank_account_number.required' => 'Nomor rekening wajib diisi.',
            'participant.bank_account_number.string'   => 'Nomor rekening harus berupa teks.',
            'participant.bank_account_number.max'      => 'Nomor rekening maksimal 100 karakter.',

            'participant.bank_account_name.required' => 'Nama pemilik rekening wajib diisi.',
            'participant.bank_account_name.string'   => 'Nama pemilik rekening harus berupa teks.',
            'participant.bank_account_name.max'      => 'Nama pemilik rekening maksimal 255 karakter.',

            'participant.bank_name.required' => 'Nama bank wajib diisi.',
            'participant.bank_name.string'   => 'Nama bank harus berupa teks.',
            'participant.bank_name.max'      => 'Nama bank maksimal 100 karakter.',

            'participant.tanggal_terbit_ktp.required' => 'Tanggal terbit KTP wajib diisi.',
            'participant.tanggal_terbit_ktp.date'     => 'Tanggal terbit KTP tidak valid.',

            'participant.tanggal_terbit_kk.required' => 'Tanggal terbit KK wajib diisi.',
            'participant.tanggal_terbit_kk.date'     => 'Tanggal terbit KK tidak valid.',

            // EVENT PARTICIPANT
            'event_participant.id.exists' => 'ID peserta event tidak valid.',

            'event_participant.event_id.required' => 'Event wajib dipilih.',
            'event_participant.event_id.exists'   => 'Event tidak valid.',

            // 'event_participant.event_group_id.required' => 'Golongan wajib dipilih.',
            // 'event_participant.event_group_id.exists'   => 'Golongan tidak valid.',

            'event_participant.event_category_id.required' => 'Kategori wajib dipilih.',
            'event_participant.event_category_id.exists'   => 'Kategori tidak valid.',

            // 'event_participant.event_branch_id.exists' => 'Cabang lomba tidak valid.',

            'event_participant.contingent.string' => 'Kontingen harus berupa teks.',

            'event_participant.registration_status.required' => 'Status registrasi wajib diisi.',
            'event_participant.registration_status.in'       => 'Status registrasi tidak valid.',

            'event_participant.registration_notes.string' => 'Catatan registrasi harus berupa teks.',

            'event_participant.reregistration_status.in'    => 'Status daftar ulang tidak valid.',
            'event_participant.reregistration_notes.string' => 'Catatan daftar ulang harus berupa teks.',
        ];

        // ============================
        // RULES
        // ============================
        $rules = [
            // PARTICIPANT
            'participant.id'                => ['nullable', 'exists:participants,id'],
            'participant.nik'               => [
                'required',
                'string',
                'max:50',
                Rule::unique('participants', 'nik')->ignore($participantId),
            ],
            'participant.full_name'         => ['required', 'string', 'max:255'],
            'participant.phone_number'      => ['required', 'string', 'max:50'],
            'participant.place_of_birth'    => ['required', 'string', 'max:100'],
            'participant.date_of_birth'     => ['required', 'date'],
            'participant.gender'            => ['required', Rule::in(['MALE','FEMALE'])],
            'participant.education'         => ['required', 'string', 'max:100'],
            'participant.address'           => ['required', 'string'],

            'participant.province_id'       => ['required', 'exists:provinces,id'],
            'participant.regency_id'        => ['required', 'exists:regencies,id'],
            'participant.district_id'       => ['required', 'exists:districts,id'],
            'participant.village_id'        => ['required', 'exists:villages,id'],

            'participant.bank_account_number' => ['required', 'string', 'max:100'],
            'participant.bank_account_name'   => ['required', 'string', 'max:255'],
            'participant.bank_name'           => ['required', 'string', 'max:100'],

            'participant.tanggal_terbit_ktp'  => ['required', 'date'],
            'participant.tanggal_terbit_kk'   => ['required', 'date'],

            // EVENT PARTICIPANT
            'event_participant.id'                => ['nullable', 'exists:event_participants,id'],
            'event_participant.event_id'          => ['required', 'exists:events,id'],
            // 'event_participant.event_group_id'    => ['required', 'exists:event_groups,id'],
            'event_participant.event_category_id' => ['required', 'exists:event_categories,id'],
            // 'event_participant.event_branch_id'   => ['nullable', 'exists:event_branches,id'],
            'event_participant.contingent'        => ['nullable', 'string'],

            'event_participant.registration_status' => ['required', Rule::in([
                'bank_data','process','verified','need_revision','rejected','disqualified'
            ])],
            'event_participant.registration_notes'  => ['nullable', 'string'],

            'event_participant.reregistration_status' => ['nullable', Rule::in([
                'not_yet','verified','rejected'
            ])],
            'event_participant.reregistration_notes'  => ['nullable', 'string'],
        ];

        $validated = $request->validate($rules, $messages);

        // ======================================================
        // SIMPAN DALAM TRANSAKSI
        // ======================================================
        return DB::transaction(function () use ($validated, $participantId, $eventParticipantId, $request) {
            $pData  = $validated['participant'];
            $epData = $validated['event_participant'];

            // ===========================
            // 1. SIMPAN / UPDATE PARTICIPANT
            // ===========================
            if ($participantId) {
                $participant = Participant::findOrFail($participantId);
            } else {
                $participant = new Participant();
            }

            $participant->nik                 = $pData['nik'];
            $participant->full_name           = $pData['full_name'];
            $participant->phone_number        = $pData['phone_number'] ?? null;
            $participant->place_of_birth      = $pData['place_of_birth'] ?? null;
            $participant->date_of_birth       = $pData['date_of_birth'];
            $participant->gender              = $pData['gender'];
            $participant->education           = $pData['education'] ?? null;
            $participant->address             = $pData['address'] ?? null;

            $participant->province_id         = $pData['province_id'] ?? null;
            $participant->regency_id          = $pData['regency_id'] ?? null;
            $participant->district_id         = $pData['district_id'] ?? null;
            $participant->village_id          = $pData['village_id'] ?? null;

            $participant->bank_account_number = $pData['bank_account_number'] ?? null;
            $participant->bank_account_name   = $pData['bank_account_name'] ?? null;
            $participant->bank_name           = $pData['bank_name'] ?? null;

            $participant->tanggal_terbit_ktp  = $pData['tanggal_terbit_ktp'] ?? null;
            $participant->tanggal_terbit_kk   = $pData['tanggal_terbit_kk'] ?? null;

            // ===========================
            // 1a. HANDLE ATTACHMENTS
            // ===========================
            $attachmentPaths = $this->handleAttachments($request, $participant);
            foreach ($attachmentPaths as $field => $path) {
                $participant->{$field} = $path;
            }

            $participant->save();

            // ===========================
            // 2. SIMPAN / UPDATE EVENT_PARTICIPANT
            // ===========================
            $event = Event::findOrFail($epData['event_id']);

            // Jika create baru -> pastikan 1 peserta hanya 1 kali per event
            if (!$eventParticipantId) {
                $exists = EventParticipant::where('event_id', $event->id)
                    ->where('participant_id', $participant->id)
                    ->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'event_participant' => ['Peserta ini sudah terdaftar di event ini.'],
                    ]);
                }

                $eventParticipant = new EventParticipant();
            } else {
                $eventParticipant = EventParticipant::findOrFail($eventParticipantId);
            }

            $eventParticipant->event_id          = $event->id;
            $eventParticipant->participant_id    = $participant->id;
            $eventParticipant->event_category_id = $epData['event_category_id'];

            $fCategory = EventCategory::find($epData['event_category_id']);
            $fGroup = null;
            $fBranch = null;
            if($fCategory) {
                $fGroup = EventGroup::where([
                    'group_id' => $fCategory->group_id,
                    'event_id' => $event->id
                    ])->first();

                if($fGroup) {
                    $fBranch = EventBranch::where([
                    'branch_id' => $fGroup->branch_id,
                    'event_id' => $event->id
                    ])->first();
                }
            }

            $eventParticipant->event_group_id    = $fGroup->id;
            $eventParticipant->event_branch_id   = $fBranch->id;
            $eventLevel = $event->event_level;
            $contingent = '';
            if($eventLevel == 'national') {
                $province = Province::findOrFail($pData['province_id']);
                $contingent = $province->name;
            } elseif($eventLevel == 'province') {
                $regency = Regency::findOrFail($pData['regency_id']);
                $contingent = $regency->name;
            } elseif($eventLevel == 'regency') {
                $district = District::findOrFail($pData['district_id']);
                $contingent = $district->name;
            } elseif($eventLevel == 'district') {
                $village = Village::findOrFail($pData['village_id']);
                $contingent = $village->name;
            }
            $eventParticipant->contingent            = $contingent ?? null;
            $eventParticipant->registration_status   = $epData['registration_status'];
            $eventParticipant->registration_notes    = $epData['registration_notes'] ?? null;
            $eventParticipant->reregistration_status = $epData['reregistration_status'] ?? 'not_yet';
            $eventParticipant->reregistration_notes  = $epData['reregistration_notes'] ?? null;

            // Hitung umur berdasarkan aturan event (tanggal_batas_umur / start_date / now)
            $dob = Carbon::parse($participant->date_of_birth);
            $refDate = $event->tanggal_batas_umur
                ?? $event->start_date
                ?? now();
            $ref = Carbon::parse($refDate);

            $ageYears  = $dob->diffInYears($ref);
            $tmp       = $dob->copy()->addYears($ageYears);
            $ageMonths = $tmp->diffInMonths($ref);
            $tmp2      = $tmp->copy()->addMonths($ageMonths);
            $ageDays   = $tmp2->diffInDays($ref);

            $eventParticipant->age_year  = $ageYears;
            $eventParticipant->age_month = $ageMonths;
            $eventParticipant->age_day   = $ageDays;

            $eventParticipant->save();

            return response()->json([
                'participant'       => $participant,
                'event_participant' => $eventParticipant,
            ], $eventParticipantId ? 200 : 201);
        });
    }

    /**
     * Simpan lampiran ke storage dan kembalikan array [kolom => path].
     */
    protected function handleAttachments(Request $request, Participant $participant): array
    {
        $fileFields = [
            'photo_url',
            'id_card_url',
            'family_card_url',
            'bank_book_url',
            'certificate_url',
            'other_url',
        ];

        $paths = [];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file      = $request->file($field);
                $extension = $file->getClientOriginalExtension();

                $fileName = $participant->nik . '_' . $field . '.' . $extension;

                $storedPath = $file->storeAs(
                    'documents/' . $participant->nik,
                    $fileName,
                    'privatedisk'
                );

                $paths[$field] = $storedPath;
            } elseif ($request->has($field)) {
                // path lama dari frontend (sudah tanpa /secure/)
                $oldPath   = $request->input($field);
                $cleanPath = str_replace('/secure/', '', $oldPath);
                $paths[$field] = $cleanPath;
            }
        }

        return $paths;
    }

    public function mutasiWilayah(Request $request, EventParticipant $eventParticipant)
    {
        $user     = $request->user();
        $roleSlug = optional($user->role)->slug ?? null;

        if ($roleSlug !== 'superadmin' && $user->event_id && $user->event_id !== $eventParticipant->event_id) {
            return response()->json([
                'message' => 'You are not allowed to move this participant.',
            ], 403);
        }

        $event      = $eventParticipant->event ?? Event::find($eventParticipant->event_id);
        $participant = $eventParticipant->participant;

        $data = $request->validate([
            'province_id' => ['required', 'exists:provinces,id'],
            'regency_id'  => ['required', 'exists:regencies,id'],
            'district_id' => ['required', 'exists:districts,id'],
        ]);

        if ($event) {
            switch ($event->event_level) {
                case 'provinsi':
                    $data['province_id'] = $event->province_id;
                    break;
                case 'kabupaten_kota':
                    $data['province_id'] = $event->province_id;
                    $data['regency_id']  = $event->regency_id;
                    break;
                case 'kecamatan':
                    $data['province_id'] = $event->province_id;
                    $data['regency_id']  = $event->regency_id;
                    $data['district_id'] = $event->district_id;
                    break;
                default:
                    // nasional → use input as is
                    break;
            }
        }

        // village_id tidak diinput di form mutasi
        $data['village_id'] = null;
        $participant->update($data);

        $eventParticipant->fresh();
        $eventLevel = $event->event_level;
        $contingent = '';
            if($eventLevel == 'national') {
                $province = Province::findOrFail($data['province_id']);
                $contingent = $province->name;
            } elseif($eventLevel == 'province') {
                $regency = Regency::findOrFail($data['regency_id']);
                $contingent = $regency->name;
            } elseif($eventLevel == 'regency') {
                $district = District::findOrFail($data['district_id']);
                $contingent = $district->name;
            } elseif($eventLevel == 'district') {
                $village = Village::findOrFail($data['village_id']);
                $contingent = $village->name;
            }
        $eventParticipant->contingent = $contingent;
        $eventParticipant->moved_by = $user->id;
        $eventParticipant->save();

        return response()->json([
            'success' => true,
            'message' => 'Wilayah peserta berhasil diperbarui.',
        ]);
    }


}
