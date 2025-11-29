<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Participant;
use App\Models\EventParticipant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Arr;
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;



class ParticipantController extends Controller
{
    /**
     * Hitung umur (tahun, bulan, hari) berdasarkan tanggal lahir dan event.
     * Cutoff: tanggal_batas_umur kalau ada, kalau tidak tanggal_mulai.
     */
    protected function calculateAgeComponents(string $dateOfBirth, ?Event $event): array
    {
        if (!$event) {
            return [null, null, null];
        }

        $birth = Carbon::parse($dateOfBirth);
        $cutoffDate = $event->tanggal_batas_umur ?? $event->tanggal_mulai;

        if (!$cutoffDate) {
            return [null, null, null];
        }

        $cutoff = Carbon::parse($cutoffDate);

        if ($birth->gt($cutoff)) {
            // Lahir setelah tanggal batas → anggap 0
            return [0, 0, 0];
        }

        $diff = $birth->diff($cutoff);

        return [
            $diff->y,
            $diff->m,
            $diff->d,
        ];
    }

    /**
     * Ubah satu baris EventParticipant menjadi array "flatten" agar mirip
     * struktur lama (full_name, nik, competition_branch, regency, dst).
     */
    protected function transformEventParticipant(EventParticipant $ep): array
    {
        $ep->loadMissing([
            'participant.province',
            'participant.regency',
            'participant.district',
            'participant.village',
            'competitionBranch',
        ]);

        $p = $ep->participant;

        return [
            // ID utama sekarang ID event_participants
            'id'                       => $ep->id,
            'event_id'                 => $ep->event_id,
            'event_competition_branch_id' => $ep->event_competition_branch_id,
            'age_year'                 => $ep->age_year,
            'age_month'                => $ep->age_month,
            'age_day'                  => $ep->age_day,
            'status_pendaftaran'       => $ep->status_pendaftaran,
            'registration_notes'       => $ep->registration_notes,
            'moved_by'                 => $ep->moved_by,
            'verified_by'              => $ep->verified_by,
            'verified_at'              => $ep->verified_at,
            'created_at'               => $ep->created_at,
            'updated_at'               => $ep->updated_at,

            // data bank-data peserta
            'participant_id'           => $p->id,
            'lampiran_completion_percent' => $p->lampiran_completion_percent,
            'nik'                      => $p->nik,
            'full_name'                => $p->full_name,
            'phone_number'             => $p->phone_number,
            'place_of_birth'           => $p->place_of_birth,
            'date_of_birth'            => $p->date_of_birth,
            'gender'                   => $p->gender,
            'province_id'              => $p->province_id,
            'regency_id'               => $p->regency_id,
            'district_id'              => $p->district_id,
            'village_id'               => $p->village_id,
            'address'                  => $p->address,
            'education'                => $p->education,
            'bank_account_number'      => $p->bank_account_number,
            'bank_account_name'        => $p->bank_account_name,
            'bank_name'                => $p->bank_name,
            'photo_url'                => $p->photo_url,
            'id_card_url'              => $p->id_card_url,
            'family_card_url'          => $p->family_card_url,
            'bank_book_url'            => $p->bank_book_url,
            'certificate_url'          => $p->certificate_url,
            'other_url'                => $p->other_url,
            'tanggal_terbit_ktp'       => $p->tanggal_terbit_ktp,
            'tanggal_terbit_kk'        => $p->tanggal_terbit_kk,

            // relasi-relasi (nama sama dengan versi lama)
            'competition_branch'       => $ep->competitionBranch,
            'province'                 => $p->province,
            'regency'                  => $p->regency,
            'district'                 => $p->district,
            'village'                  => $p->village,
        ];
    }

    /**
     * GET /api/v1/participants
     * Sekarang yang di-query adalah event_participants + participants.
     */
    public function index(Request $request)
    {
        $user     = $request->user();
        $roleSlug = optional($user->role)->slug ?? null;

        $search   = $request->get('search');
        $perPage  = (int) $request->get('per_page', 10);
        $eventId  = $request->get('event_id', $user->event_id ?? null);
        $status   = $request->get('status_pendaftaran'); // ⬅ status filter

        $query = EventParticipant::query()
            ->with([
                'participant',
                'competitionBranch',
                'participant.regency',
                'participant.district',
            ])
            ->when($eventId, function ($q) use ($eventId) {
                $q->where('event_id', $eventId);
            })
            // join untuk bisa orderBy nama peserta
            ->join('participants as p', 'p.id', '=', 'event_participants.participant_id')
            ->select('event_participants.*')
            ->orderBy('p.full_name');

        // filter wilayah untuk non-superadmin
        if ($roleSlug !== 'superadmin') {
            if ($eventId) {
                $event = Event::find($eventId);
                $user  = Auth::user();

                if ($event) {
                    if ($event->tingkat_event === 'provinsi') {
                        $query->where('p.province_id', $event->province_id)
                            ->where('p.regency_id', $user->regency_id);
                    } elseif ($event->tingkat_event === 'kabupaten_kota') {
                        $query->where('p.province_id', $event->province_id)
                            ->where('p.regency_id', $event->regency_id)
                            ->where('p.district_id', $user->district_id);
                    }
                }
            } else {
                $query->whereRaw('1 = 0'); // tidak ada data
            }
        }

        // filter status_pendaftaran jika dikirim
        if ($status && in_array($status, ['proses', 'diterima', 'perbaiki', 'mundur', 'tolak', 'bankdata'])) {
            $query->where('event_participants.status_pendaftaran', $status);
        }

        // search by nama / nik / phone
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('p.full_name', 'like', "%{$search}%")
                ->orWhere('p.nik', 'like', "%{$search}%")
                ->orWhere('p.phone_number', 'like', "%{$search}%");
            });
        }

        $paginator = $query->paginate($perPage);

        // transform supaya bentuk datanya mirip struktur lama
        $paginator->getCollection()->transform(function (EventParticipant $ep) {
            return $this->transformEventParticipant($ep);
        });

        return response()->json($paginator);
    }


    /**
     * POST /api/v1/participants
     * - Simpan/Update bank-data di participants
     * - Tambah baris di event_participants untuk event tertentu
     */
    public function store(Request $request)
    {
        $user     = $request->user();
        $roleSlug = optional($user->role)->slug ?? null;

        $eventId = $request->get('event_id', $user->event_id ?? null);
        $event   = $eventId ? Event::find($eventId) : null;

        if (!$event) {
            return response()->json([
                'message' => 'Event ID is required or event not found.',
            ], 422);
        }

        // =========================================
        // 1. DETEKSI PESERTA EXISTING (BY ID / NIK)
        // =========================================
        $existingParticipantId = null;

        // a) kalau frontend kirim participant_id → pakai itu
        if ($request->filled('participant_id')) {
            $existing = Participant::find($request->input('participant_id'));
            if ($existing) {
                $existingParticipantId = $existing->id;

                // kalau NIK tidak dikirim dari form, isi dengan NIK existing
                if (!$request->filled('nik')) {
                    $request->merge(['nik' => $existing->nik]);
                }
            }
        }
        // b) kalau tidak ada participant_id, cek berdasarkan NIK
        elseif ($request->filled('nik')) {
            $existing = Participant::where('nik', $request->input('nik'))->first();
            if ($existing) {
                $existingParticipantId = $existing->id;
            }
        }

        // =========================================
        // 2. VALIDASI (KIRIM existingParticipantId)
        // =========================================
        $data = $this->validateData($request, $existingParticipantId, false);

        // pisah data peserta dan data event
        $participantData = Arr::except($data, ['event_competition_branch_id']);
        $branchId        = $data['event_competition_branch_id'];

        // =========================================
        // 3. VALIDASI LAMPIRAN
        // =========================================
        $this->validateAttachments($request);

        // =========================================
        // 4. SIMPAN / UPDATE PARTICIPANT (BANK DATA)
        //    - kalau existingParticipantId != null → update
        //    - kalau belum ada → create baru
        // =========================================
        $participant = Participant::updateOrCreate(
            ['nik' => $participantData['nik']],
            $participantData
        );

        // =========================================
        // 5. SIMPAN FILE LAMPIRAN (JIKA ADA)
        // =========================================
        $attachmentPaths = $this->handleAttachments($request, $participant);
        if (!empty($attachmentPaths)) {
            $participant->update($attachmentPaths);
        }

        // =========================================
        // 6. HITUNG UMUR BERDASARKAN EVENT
        // =========================================
        [$ageYear, $ageMonth, $ageDay] = $this->calculateAgeComponents(
            $participant->date_of_birth,
            $event
        );

        // =========================================
        // 7. BUAT BARIS event_participants
        //    (relasi peserta ke event tertentu)
        // =========================================
        $eventParticipant = EventParticipant::create([
            'event_id'                    => $event->id,
            'participant_id'              => $participant->id,
            'event_competition_branch_id' => $branchId,
            'age_year'                    => $ageYear,
            'age_month'                   => $ageMonth,
            'age_day'                     => $ageDay,
            // status_pendaftaran pakai default enum di migration
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Participant created successfully.',
            'data'    => $this->transformEventParticipant($eventParticipant),
        ], 201);
    }

    
    
    // public function store(Request $request)
    // {
    //     $user     = $request->user();
    //     $roleSlug = optional($user->role)->slug ?? null;

    //     $eventId = $request->get('event_id', $user->event_id ?? null);
    //     $event   = $eventId ? Event::find($eventId) : null;

    //     if (!$event) {
    //         return response()->json([
    //             'message' => 'Event ID is required or event not found.',
    //         ], 422);
    //     }

    //     // 1. Validasi field utama (bank data + cabang)
    //     $data = $this->validateData($request, null, false);

    //     // pisah data peserta dan data event
    //     $participantData = Arr::except($data, ['event_competition_branch_id']);
    //     $branchId        = $data['event_competition_branch_id'];

    //     // 2. validasi lampiran
    //     $this->validateAttachments($request);

    //     // 3. simpan / update participant berdasarkan NIK (bank data)
    //     $participant = Participant::updateOrCreate(
    //         ['nik' => $participantData['nik']],
    //         $participantData
    //     );

    //     // 4. simpan lampiran ke storage (jika ada) dan update participant
    //     $attachmentPaths = $this->handleAttachments($request, $participant);
    //     if (!empty($attachmentPaths)) {
    //         $participant->update($attachmentPaths);
    //     }

    //     // 5. hitung umur per event
    //     [$ageYear, $ageMonth, $ageDay] = $this->calculateAgeComponents(
    //         $participant->date_of_birth,
    //         $event
    //     );

    //     // 6. buat baris event_participants
    //     $eventParticipant = EventParticipant::create([
    //         'event_id'                  => $event->id,
    //         'participant_id'            => $participant->id,
    //         'event_competition_branch_id' => $branchId,
    //         'age_year'                  => $ageYear,
    //         'age_month'                 => $ageMonth,
    //         'age_day'                   => $ageDay,
    //         // status default pakai default enum di migration
    //     ]);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Participant created successfully.',
    //         'data'    => $this->transformEventParticipant($eventParticipant),
    //     ], 201);
    // }

    /**
     * GET /api/v1/participants/{eventParticipant}
     */
    public function show(Request $request, EventParticipant $eventParticipant)
    {
        $user     = $request->user();
        $roleSlug = optional($user->role)->slug ?? null;

        if ($roleSlug !== 'superadmin' && $user->event_id && $user->event_id !== $eventParticipant->event_id) {
            return response()->json([
                'message' => 'You are not allowed to view this participant.',
            ], 403);
        }

        return response()->json(
            $this->transformEventParticipant($eventParticipant)
        );
    }

    /**
     * PUT/PATCH /api/v1/participants/{eventParticipant}
     * - Update bank-data (participants)
     * - Update baris event_participants (cabang, umur, status, dst)
     */
    public function update(Request $request, EventParticipant $eventParticipant)
    {
        $user     = $request->user();
        $roleSlug = optional($user->role)->slug ?? null;

        // return $eventParticipant;

        // return [
        //     'user_event_id' => $user->event_id,
        //     'event_participant_event_id' => $eventParticipant->event_id,
        // ];

        if ($roleSlug !== 'superadmin' && $user->event_id && $user->event_id !== $eventParticipant->event_id) {
            return response()->json([
                'message' => 'You are not allowed to update this participant.',
            ], 403);
        }

        $participant = $eventParticipant->participant;
        $event       = $eventParticipant->event ?? Event::find($eventParticipant->event_id);

        // 1. Validasi data
        $data = $this->validateData($request, $participant->id, true);

        $participantData = Arr::except($data, ['event_competition_branch_id']);
        $branchId        = $data['event_competition_branch_id'];

        // 2. Validasi lampiran
        $this->validateAttachments($request);

        // 3. Update bank data peserta
        $participant->update($participantData);

        // 4. Simpan lampiran baru / pertahankan path lama
        $attachmentPaths = $this->handleAttachments($request, $participant);
        if (!empty($attachmentPaths)) {
            $participant->update($attachmentPaths);
        }

        // 5. Re-hitung umur
        $dob = $participant->date_of_birth;
        [$ageYear, $ageMonth, $ageDay] = $this->calculateAgeComponents($dob, $event);

        // 6. Update baris event_participants
        $eventParticipant->update([
            'event_competition_branch_id' => $branchId,
            'age_year'                    => $ageYear,
            'age_month'                   => $ageMonth,
            'age_day'                     => $ageDay,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Participant updated successfully.',
            'data'    => $this->transformEventParticipant($eventParticipant->fresh()),
        ]);
    }

    /**
     * DELETE /api/v1/participants/{eventParticipant}
     * Hanya menghapus relasi di event_participants, bank-data peserta tetap ada.
     */
    public function destroy(Request $request, EventParticipant $eventParticipant)
    {
        $user     = $request->user();
        $roleSlug = optional($user->role)->slug ?? null;

        if ($roleSlug !== 'superadmin' && $user->event_id && $user->event_id !== $eventParticipant->event_id) {
            return response()->json([
                'message' => 'You are not allowed to delete this participant.',
            ], 403);
        }

        $eventParticipant->delete();

        return response()->json([
            'success' => true,
            'message' => 'Participant deleted successfully from this event.',
        ]);
    }

    /**
     * Validasi data bank-data peserta + cabang lomba.
     */
    protected function validateData(Request $request, $participantId = null, $isUpdate = false): array
    {
        $rules = [
            'event_competition_branch_id' => ['required', 'exists:event_competition_branches,id'],

            'nik'                         => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:30',
                Rule::unique('participants', 'nik')->ignore($participantId),
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

            // bank_name sekarang enum di DB, pastikan value valid
            'bank_name' => [
                $isUpdate ? 'sometimes' : 'required',
                Rule::in([
                    'BRI', 'BNI', 'MANDIRI', 'BTN',
                    'BSI', 'BRI SYARIAH', 'BNI SYARIAH', 'MANDIRI SYARIAH',
                    'BCA', 'CIMB NIAGA', 'PERMATA', 'PANIN', 'OCBC NISP',
                    'DANAMON', 'MEGA', 'SINARMAS', 'BUKOPIN', 'MAYBANK', 'BTPN', 'J TRUST BANK',
                    'BANK DKI', 'BANK BJB', 'BANK BJB SYARIAH', 'BANK JATENG', 'BANK JATIM',
                    'BANK SUMUT', 'BANK NAGARI', 'BANK RIAU KEPRI', 'BANK SUMSEL BABEL',
                    'BANK LAMPUNG', 'BANK KALSEL', 'BANK KALBAR', 'BANK KALTIMTARA',
                    'BANK SULSEL BAR', 'BANK SULTRA', 'BANK SULUTGO', 'BANK NTB SYARIAH',
                    'BANK NTT', 'BANK PAPUA', 'BANK MALUKU MALUT',
                ]),
            ],

            // tanggal terbit dokumen
            'tanggal_terbit_ktp'          => ['nullable', 'date'],
            'tanggal_terbit_kk'           => ['nullable', 'date'],
        ];

        return $request->validate($rules);
    }

    /**
     * Validasi lampiran (hanya field yang benar-benar dikirim sebagai file).
     */
    protected function validateAttachments(Request $request): void
    {
        $fileFields = [
            'photo_url',
            'id_card_url',
            'family_card_url',
            'bank_book_url',
            'certificate_url',
            'other_url',
        ];

        $rules = [];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                if ($field === 'photo_url') {
                    // FOTO hanya JPEG/PNG max 1 MB
                    $rules[$field] = ['file', 'mimes:jpg,jpeg,png', 'max:1024'];
                } else {
                    // Lainnya wajib PDF
                    $rules[$field] = ['file', 'mimes:pdf', 'max:1024'];
                }
            }
        }

        if (!empty($rules)) {
            $request->validate($rules);
        }
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

    /**
     * Cek NIK dalam satu event & wilayah.
     * Sekarang cek ke event_participants + participants.
     */
    public function checkNik(Request $request)
    {
        $nik       = preg_replace('/\D/', '', $request->get('nik', ''));
        $eventId   = $request->get('event_id');
        $rowId     = $request->get('participant_id'); // sekarang: ID event_participants

        if (!$nik || !$eventId) {
            return response()->json([
                'conflict' => false,
                'message'  => 'NIK atau event tidak lengkap.',
            ], 200);
        }

        $event = Event::find($eventId);
        if (!$event) {
            return response()->json([
                'conflict' => false,
                'message'  => 'Event tidak ditemukan.',
            ], 200);
        }

        $user   = Auth::user();
        $provId = $request->get('province_id');
        $regId  = ($event->tingkat_event == 'provinsi')       ? $user->regency_id  : $request->get('regency_id');
        $distId = ($event->tingkat_event == 'kabupaten_kota') ? $user->district_id : $request->get('district_id');
        $villId = ($event->tingkat_event == 'kecamatan')      ? $user->village_id  : $request->get('village_id');

        // level wilayah utama
        switch ($event->tingkat_event) {
            case 'provinsi':
                $regionColumn = 'regency_id';
                break;
            case 'kabupaten_kota':
                $regionColumn = 'district_id';
                break;
            case 'kecamatan':
                $regionColumn = 'village_id';
                break;
            default:
                $regionColumn = 'regency_id';
        }

        // nilai wilayah baru
        $newRegionId = null;
        if ($regionColumn === 'regency_id') {
            $newRegionId = $regId;
        } elseif ($regionColumn === 'district_id') {
            $newRegionId = $distId;
        } elseif ($regionColumn === 'village_id') {
            $newRegionId = $villId;
        }

        // Ambil semua peserta lain di event yang sama dengan NIK yang sama
        $query = EventParticipant::where('event_id', $eventId)
            ->whereHas('participant', function ($q) use ($nik) {
                $q->where('nik', $nik);
            });

        if ($rowId) {
            $query->where('id', '!=', $rowId);
        }

        $others = $query->with(['participant.regency', 'participant.district', 'participant.village'])->get();

        if ($others->isEmpty()) {
            return response()->json([
                'conflict' => false,
            ]);
        }

        foreach ($others as $other) {
            $p = $other->participant;

            $existingRegionId = $p->{$regionColumn};

            if (!$existingRegionId || !$newRegionId) {
                return response()->json([
                    'conflict'         => true,
                    'message'          => 'NIK ini sudah terdaftar di event ini pada wilayah lain.',
                    'participant_name' => $p->full_name,
                ]);
            }

            if ((string) $existingRegionId !== (string) $newRegionId) {
                $regionName = null;
                if ($regionColumn === 'regency_id') {
                    $regionName = optional($p->regency)->name;
                } elseif ($regionColumn === 'district_id') {
                    $regionName = optional($p->district)->name;
                } elseif ($regionColumn === 'village_id') {
                    $regionName = optional($p->village)->name;
                }

                return response()->json([
                    'conflict'         => true,
                    'message'          => sprintf(
                        'NIK ini sudah digunakan di event ini oleh "%s" pada wilayah "%s". NIK tidak boleh dipakai untuk wilayah yang berbeda dalam satu event.',
                        $p->full_name,
                        $regionName ?: '-'
                    ),
                    'participant_name' => $p->full_name,
                    'region_name'      => $regionName,
                ]);
            } else {
                return response()->json([
                    'conflict'         => true,
                    'message'          => 'NIK ini sudah terdaftar pada cabang yang lain.',
                    'participant_name' => $p->full_name,
                ]);
            }
        }

        return response()->json([
            'conflict' => false,
        ]);
    }

    /**
     * POST /api/v1/participants/{eventParticipant}/mutasi-wilayah
     * Memindahkan wilayah peserta (bank-data) untuk peserta di event ini.
     */
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
            switch ($event->tingkat_event) {
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

        return response()->json([
            'success' => true,
            'message' => 'Wilayah peserta berhasil diperbarui.',
            'data'    => $this->transformEventParticipant($eventParticipant->fresh()),
        ]);
    }

    /**
     * Cari peserta berdasarkan NIK dari bank data (tabel participants)
     * tanpa batasan event.
     *
     * GET /api/v1/participants/search-by-nik?nik=123456...
     */
    public function searchByNik(Request $request)
    {
        $nik = trim($request->nik);

        if (!$nik || strlen($nik) < 6) {
            return response()->json([
                'message' => 'NIK tidak valid.',
            ], 400);
        }

        // ==========================
        // RATE LIMIT: 10x / 1 menit
        // ==========================
        $key = 'search-nik:' . $request->ip() . ':' . $nik;

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => 'Terlalu banyak percobaan pencarian NIK. Silakan coba lagi beberapa saat.',
                'retry_after' => $seconds,
            ], 429);
        }

        // hit counter, expired setelah 60 detik
        RateLimiter::hit($key, 60);

        // ==========================
        // LOGIKA PENCARIAN
        // ==========================
        $participant = Participant::with([
            'province:id,name',
            'regency:id,name',
            'district:id,name',
            'village:id,name',
        ])
        ->where('nik', $nik)
        ->latest()
        ->first();

        if (!$participant) {
            return response()->json([
                'message' => 'Peserta dengan NIK ini belum ada di bank data.',
            ], 200);
        }

        return response()->json([
            'message' => 'Data peserta ditemukan.',
            'data' => $participant,
        ], 200);
    }


    public function bulkRegister(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:event_participants,id'],
            'event_id' => ['required', 'exists:events,id'],
            'status_pendaftaran' => ['nullable', Rule::in(['proses', 'draft', 'selesai'])],
        ]);

        $status = $data['status_pendaftaran'] ?? 'proses';

        EventParticipant::whereIn('id', $data['ids'])
            ->where('event_id', $data['event_id'])
            ->update(['status_pendaftaran' => $status]);

        return response()->json([
            'success' => true,
            'message' => 'Status pendaftaran peserta berhasil diubah menjadi ' . $status . '.',
        ]);
    }


    /**
     * Rekap jumlah peserta per status_pendaftaran dalam 1 event.
     *
     * GET /api/v1/participants/status-counts?event_id=XX
     * Response:
     * {
     *   "proses": 10,
     *   "diterima": 5,
     *   "perbaiki": 2,
     *   "mundur": 1,
     *   "tolak": 3
     * }
     */
    public function statusCounts(Request $request)
    {
        $user     = $request->user();
        $roleSlug = optional($user->role)->slug ?? null;

        $eventId  = $request->get('event_id', $user->event_id ?? null);

        if (!$eventId) {
            return response()->json([
                'message' => 'Event ID is required.',
            ], 422);
        }

        $event = Event::find($eventId);
        if (!$event) {
            return response()->json([
                'message' => 'Event not found.',
            ], 404);
        }

        // base query
        $query = EventParticipant::query()
            ->join('participants as p', 'p.id', '=', 'event_participants.participant_id')
            ->where('event_participants.event_id', $eventId);

        // filter wilayah sama dengan index() untuk non-superadmin
        if ($roleSlug !== 'superadmin') {
            $user = Auth::user();

            if ($event->tingkat_event === 'provinsi') {
                $query->where('p.province_id', $event->province_id)
                    ->where('p.regency_id', $user->regency_id);
            } elseif ($event->tingkat_event === 'kabupaten_kota') {
                $query->where('p.province_id', $event->province_id)
                    ->where('p.regency_id', $event->regency_id)
                    ->where('p.district_id', $user->district_id);
            }
            // kalau tingkat lain (nasional, kecamatan) bisa disesuaikan jika ada aturan khusus
        }

        // group by status_pendaftaran
        $rawCounts = $query
            ->select(
                'event_participants.status_pendaftaran',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('event_participants.status_pendaftaran')
            ->pluck('total', 'status_pendaftaran');

        $allowedStatuses = ['proses', 'diterima', 'perbaiki', 'mundur', 'tolak'];

        // inisialisasi 0 semua
        $result = [];
        foreach ($allowedStatuses as $st) {
            $result[$st] = 0;
        }

        // isi dari hasil query
        foreach ($rawCounts as $status => $total) {
            if (in_array($status, $allowedStatuses, true)) {
                $result[$status] = (int) $total;
            }
        }

        return response()->json($result);
    }



}
