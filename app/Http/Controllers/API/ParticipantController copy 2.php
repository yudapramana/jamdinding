<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Auth;
use Carbon\Carbon;

class ParticipantController extends Controller
{


    protected function calculateAgeComponents(string $dateOfBirth, $tanggalMulai): array
    {
        $birth = Carbon::parse($dateOfBirth);

        // cutoff: tanggal_batas_umur kalau ada, kalau tidak: tanggal_mulai
        $cutoffDate = $this->event->tanggal_batas_umur ?? $tanggalMulai ?? null;
        if (!$cutoffDate) {
            return [null, null, null];
        }

        $cutoff = Carbon::parse($cutoffDate);

        if ($birth->gt($cutoff)) {
            // Lahir setelah tanggal batas → umur negatif, anggap 0
            return [0, 0, 0];
        }

        $diff = $birth->diff($cutoff);

        return [
            $diff->y, // year
            $diff->m, // month
            $diff->d, // day
        ];
    }


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
                $e = Event::find($eventId);

                $user = Auth::user();
                if($e->tingkat_event == 'provinsi') {
                    $query->where('province_id', $e->province_id);
                    $query->where('regency_id', $user->regency_id);
                }

                if($e->tingkat_event == 'kabupaten_kota') {
                    $query->where('province_id', $e->province_id);
                    $query->where('regency_id', $e->regency_id);
                    $query->where('district_id', $user->district_id);
                }

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

        $eventId = $request->get('event_id', $user->event_id ?? null);
        $event = Event::find($eventId);

        if (!$eventId) {
            return response()->json([
                'message' => 'Event ID is required.',
            ], 422);
        }

        // 1. validasi field utama
        $data = $this->validateData($request, $eventId);

        // 2. validasi lampiran PDF (hanya yang dikirim sebagai file)
        $this->validateAttachments($request);

        // 3. set event_id
        $data['event_id'] = $eventId;

        // 4. simpan lampiran ke storage (jika ada)
        $attachmentPaths = $this->handleAttachments($request, null);

        [$ageYear, $ageMonth, $ageDay] = $this->calculateAgeComponents($request['date_of_birth'], $event->tanggal_mulai);
        $ageData = [
            'age_year' => $ageYear,
            'age_month' => $ageMonth,
            'age_day' => $ageDay,
        ];

        // gabungkan ke $data
        $data = array_merge($data, $attachmentPaths, $ageData);

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
        $event = Event::find($eventId);

        // 1. validasi field utama
        $data = $this->validateData($request, $eventId, $participant->id, true);

        // 2. validasi lampiran (kalau ada file)
        $this->validateAttachments($request);

        // 3. simpan lampiran baru (kalau ada) / pertahankan path lama
        $attachmentPaths = $this->handleAttachments($request, $participant);

        [$ageYear, $ageMonth, $ageDay] = $this->calculateAgeComponents($request['date_of_birth'], $event->tanggal_mulai);
        $ageData = [
            'age_year' => $ageYear,
            'age_month' => $ageMonth,
            'age_day' => $ageDay,
        ];

        // 4. gabungkan data utama + lampiran
        $data = array_merge($data, $attachmentPaths, $ageData);


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
     *
     * - Jika ada file baru: simpan dan return path barunya.
     * - Jika tidak ada file, tapi ada string (path lama): gunakan string itu (untuk update).
     * - Jika tidak ada keduanya: kolom tersebut tidak disentuh.
     */
    protected function handleAttachments(Request $request, ?Participant $participant = null): array
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
                $file = $request->file($field);
                $extension = $file->getClientOriginalExtension();


                $fileName = $participant->nik .'_'.$field. '.' . $extension;



                // folder bisa disesuaikan, misal: participants/photo, participants/ktp, dst.
                // $dirName = str_replace('_url', '', $field); // contoh: photo_url -> photo
                // $storedPath = $file->store("participants/{$dirName}", 'public');

                $storedPath = $file->storeAs(
                    'documents/' . $participant->nik,
                    $fileName,
                    'privatedisk'
                );

                $paths[$field] = $storedPath;
            } elseif ($participant && $request->has($field)) {
                // Ambil path lama
                $oldPath = $request->input($field);

                // Hapus prefix "/secure/" jika ada
                $cleanPath = str_replace('/secure/', '', $oldPath);

                $paths[$field] = $cleanPath;
            }
            // kalau create dan tidak ada file, biasanya field kosong → tidak di-set sama sekali
        }

        return $paths;
    }



    /**
     * Cek NIK dalam satu event & wilayah.
     *
     * Aturan:
     * - NIK boleh dipakai di event lain (event_id berbeda).
     * - Dalam 1 event, NIK TIDAK boleh dipakai untuk wilayah yang berbeda
     *   (wilayah mengikuti tingkat_event).
     */
    public function checkNik(Request $request)
    {
        $nik       = preg_replace('/\D/', '', $request->get('nik', ''));
        $eventId   = $request->get('event_id');
        $rowId     = $request->get('participant_id'); // saat edit
        

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

        $user = Auth::user();
        $provId    = $request->get('province_id');
        $regId     = ($event->tingkat_event == 'provinsi') ? $user->regency_id  :  $request->get('regency_id');
        $distId    = ($event->tingkat_event == 'kabupaten_kota') ? $user->district_id  :  $request->get('district_id');
        $villId    = ($event->tingkat_event == 'kecamatan') ? $user->village_id  :  $request->get('village_id');

        // Tentukan "level wilayah utama" berdasarkan tingkat_event
        switch ($event->tingkat_event) {
            case 'provinsi':
                $regionColumn = 'regency_id';   // Asal = Kab/Kota
                break;
            case 'kabupaten_kota':
                $regionColumn = 'district_id';  // Asal = Kecamatan
                break;
            case 'kecamatan':
                $regionColumn = 'village_id';   // Asal = Desa/Kel
                break;
            default:
                $regionColumn = 'regency_id';   // nasional → fallback
        }

        // Ambil nilai wilayah baru dari request
        $newRegionId = null;
        if ($regionColumn === 'regency_id') {
            $newRegionId = $regId;
        } elseif ($regionColumn === 'district_id') {
            $newRegionId = $distId;
        } elseif ($regionColumn === 'village_id') {
            $newRegionId = $villId;
        }

        // Ambil semua peserta lain di event yang sama dengan NIK yang sama
        $query = Participant::where('event_id', $eventId)
            ->where('nik', $nik);

        if ($rowId) {
            $query->where('id', '!=', $rowId);
        }

        $others = $query->with(['regency', 'district', 'village'])->get();

        if ($others->isEmpty()) {
            // Tidak ada peserta lain dengan NIK ini di event ini → aman
            return response()->json([
                'conflict' => false,
            ]);
        }

        // Cek apakah ada yang wilayah utamanya berbeda
        foreach ($others as $other) {
            $existingRegionId = $other->{$regionColumn};

            // Kalau region di DB atau yang baru kosong, kita anggap beda (lebih ketat)
            if (!$existingRegionId || !$newRegionId) {
                return response()->json([
                    'conflict' => true,
                    'message'  => 'NIK ini sudah terdaftar di event ini pada wilayah lain.',
                    'participant_name' => $other->full_name,
                ]);
            }

            if ((string) $existingRegionId !== (string) $newRegionId) {
                // Bangun nama wilayah untuk pesan
                $regionName = null;
                if ($regionColumn === 'regency_id') {
                    $regionName = optional($other->regency)->name;
                } elseif ($regionColumn === 'district_id') {
                    $regionName = optional($other->district)->name;
                } elseif ($regionColumn === 'village_id') {
                    $regionName = optional($other->village)->name;
                }

                return response()->json([
                    'conflict' => true,
                    'message'  => sprintf(
                        'NIK ini sudah digunakan di event ini oleh "%s" pada wilayah "%s". NIK tidak boleh dipakai untuk wilayah yang berbeda dalam satu event.',
                        $other->full_name,
                        $regionName ?: '-'
                    ),
                    'participant_name' => $other->full_name,
                    'region_name'      => $regionName,
                ]);
            } else {
                return response()->json([
                    'conflict' => true,
                    'message'  => 'NIK ini sudah terdaftar pada cabang yang lain.',
                    'participant_name' => $other->full_name,
                ]);
            }
        }

        // Semua existing punya wilayah utama yang sama → diizinkan
        return response()->json([
            'conflict' => false,
        ]);
    }

    /**
     * POST /api/v1/participants/{participant}/mutasi-wilayah
     * Memindahkan wilayah peserta (provinsi/kab/kec).
     */
    public function mutasiWilayah(Request $request, Participant $participant)
    {
        $user     = $request->user();
        $roleSlug = optional($user->role)->slug ?? null;

        // Izin sama seperti update: hanya SUPERADMIN atau user yang punya akses ke event ini
        if ($roleSlug !== 'superadmin' && $user->event_id && $user->event_id !== $participant->event_id) {
            return response()->json([
                'message' => 'You are not allowed to move this participant.',
            ], 403);
        }

        $event = $participant->event ?: Event::find($participant->event_id);

        // Validasi dasar
        $data = $request->validate([
            'province_id' => ['required', 'exists:provinces,id'],
            'regency_id'  => ['required', 'exists:regencies,id'],
            'district_id' => ['required', 'exists:districts,id'],
        ]);

        // Sesuaikan dengan tingkat_event (agar tidak keluar dari wilayah event)
        if ($event) {
            switch ($event->tingkat_event) {
                case 'provinsi':
                    // provinsi harus sama dengan event
                    $data['province_id'] = $event->province_id;
                    break;

                case 'kabupaten_kota':
                    // provinsi & kab/kota ikut event
                    $data['province_id'] = $event->province_id;
                    $data['regency_id']  = $event->regency_id;
                    break;

                case 'kecamatan':
                    // provinsi, kab/kota, kecamatan sebenarnya sudah fix di event
                    $data['province_id'] = $event->province_id;
                    $data['regency_id']  = $event->regency_id;
                    $data['district_id'] = $event->district_id;
                    break;

                default:
                    // nasional → tidak dipaksa, pakai input user
                    break;
            }
        }

        // Optional: reset village_id karena tidak diinput di form mutasi
        $data['village_id'] = null;

        $participant->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Wilayah peserta berhasil diperbarui.',
            'data'    => $participant->fresh(['regency', 'district', 'province']),
        ]);
    }

}
