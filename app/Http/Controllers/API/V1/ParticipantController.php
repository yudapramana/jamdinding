<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Event;
use App\Models\EventParticipant;
use Auth;

class ParticipantController extends Controller
{

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
        $regId  = ($event->event_level == 'province')       ? $user->regency_id  : $request->get('regency_id');
        $distId = ($event->event_level == 'regency') ? $user->district_id : $request->get('district_id');
        $villId = ($event->event_level == 'district')      ? $user->village_id  : $request->get('village_id');

        // level wilayah utama
        switch ($event->event_level) {
            case 'province':
                $regionColumn = 'regency_id';
                break;
            case 'regency':
                $regionColumn = 'district_id';
                break;
            case 'district':
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
     * Cek NIK dalam satu event & wilayah.
     * Sekarang cek ke event_participants + participants.
     */
    // public function checkNik(Request $request)
    // {
    //     $nik       = preg_replace('/\D/', '', $request->get('nik', ''));
    //     $eventId   = $request->get('event_id');
    //     $rowId     = $request->get('participant_id'); // sekarang: ID event_participants

    //     if (!$nik || !$eventId) {
    //         return response()->json([
    //             'conflict' => false,
    //             'message'  => 'NIK atau event tidak lengkap.',
    //         ], 200);
    //     }

    //     $event = Event::find($eventId);
    //     if (!$event) {
    //         return response()->json([
    //             'conflict' => false,
    //             'message'  => 'Event tidak ditemukan.',
    //         ], 200);
    //     }

    //     $user   = Auth::user();
    //     $provId = $request->get('province_id');
    //     $regId  = ($event->event_level == 'province')       ? $user->regency_id  : $request->get('regency_id');
    //     $distId = ($event->event_level == 'regency') ? $user->district_id : $request->get('district_id');
    //     $villId = ($event->event_level == 'district')      ? $user->village_id  : $request->get('village_id');

    //     // level wilayah utama
    //     switch ($event->event_level) {
    //         case 'province':
    //             $regionColumn = 'regency_id';
    //             break;
    //         case 'regency':
    //             $regionColumn = 'district_id';
    //             break;
    //         case 'district':
    //             $regionColumn = 'village_id';
    //             break;
    //         default:
    //             $regionColumn = 'regency_id';
    //     }

    //     // nilai wilayah baru
    //     $newRegionId = null;
    //     if ($regionColumn === 'regency_id') {
    //         $newRegionId = $regId;
    //     } elseif ($regionColumn === 'district_id') {
    //         $newRegionId = $distId;
    //     } elseif ($regionColumn === 'village_id') {
    //         $newRegionId = $villId;
    //     }

    //     // Ambil semua peserta lain di event yang sama dengan NIK yang sama
    //     $query = EventParticipant::where('event_id', $eventId)
    //         ->whereHas('participant', function ($q) use ($nik) {
    //             $q->where('nik', $nik);
    //         });

    //     $others = $query->with(['participant.regency', 'participant.district', 'participant.village'])->get();

    //     if ($others->isEmpty()) {
    //         return response()->json([
    //             'conflict' => false,
    //         ]);
    //     }

    //     foreach ($others as $other) {
    //         $p = $other->participant;

    //         $existingRegionId = $p->{$regionColumn};

    //         if (!$existingRegionId || !$newRegionId) {
    //             return response()->json([
    //                 'conflict'         => true,
    //                 'message'          => 'NIK ini sudah terdaftar di event ini pada wilayah lain.',
    //                 'participant_name' => $p->full_name,
    //             ]);
    //         }

    //         if ((string) $existingRegionId !== (string) $newRegionId) {
    //             $regionName = null;
    //             if ($regionColumn === 'regency_id') {
    //                 $regionName = optional($p->regency)->name;
    //             } elseif ($regionColumn === 'district_id') {
    //                 $regionName = optional($p->district)->name;
    //             } elseif ($regionColumn === 'village_id') {
    //                 $regionName = optional($p->village)->name;
    //             }

    //             return response()->json([
    //                 'conflict'         => true,
    //                 'message'          => sprintf(
    //                     'NIK ini sudah digunakan di event ini oleh "%s" pada wilayah "%s". NIK tidak boleh dipakai untuk wilayah yang berbeda dalam satu event.',
    //                     $p->full_name,
    //                     $regionName ?: '-'
    //                 ),
    //                 'participant_name' => $p->full_name,
    //                 'region_name'      => $regionName,
    //             ]);
    //         } else {
    //             return response()->json([
    //                 'conflict'         => true,
    //                 'message'          => 'NIK ini sudah terdaftar pada cabang yang lain.',
    //                 'participant_name' => $p->full_name,
    //             ]);
    //         }
    //     }

    //     return response()->json([
    //         'conflict' => false,
    //     ]);
    // }


    public function index(Request $request)
    {
        $search  = $request->get('search');
        $perPage = (int) $request->get('per_page', 10);

        $query = Participant::query()->orderBy('full_name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function searchByNik(Request $request)
    {
        $nik = $request->get('nik');

        if (!$nik) {
            return response()->json([], 200);
        }

        $participant = Participant::where('nik', $nik)->first();

        if (!$participant) {
            return response()->json([], 200);
        }

        return response()->json($participant);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nik'            => ['required', 'string', 'max:30', 'unique:participants,nik'],
            'full_name'      => ['required', 'string', 'max:150'],
            'phone_number'   => ['nullable', 'string', 'max:30'],
            'place_of_birth' => ['required', 'string', 'max:100'],
            'date_of_birth'  => ['required', 'date'],
            'gender'         => ['required', Rule::in(['MALE', 'FEMALE'])],
            'education'      => ['nullable', Rule::in([
                'SD','SMP','SMA','D1','D2','D3','D4','S1','S2','S3'
            ])],
            'address'        => ['nullable', 'string'],
        ]);

        $participant = Participant::create($validated);

        return response()->json($participant, 201);
    }

    public function show(Participant $participant)
    {
        return response()->json($participant);
    }

    public function update(Request $request, Participant $participant)
    {
        $validated = $request->validate([
            'nik'            => ['required', 'string', 'max:30', Rule::unique('participants', 'nik')->ignore($participant->id)],
            'full_name'      => ['required', 'string', 'max:150'],
            'phone_number'   => ['nullable', 'string', 'max:30'],
            'place_of_birth' => ['required', 'string', 'max:100'],
            'date_of_birth'  => ['required', 'date'],
            'gender'         => ['required', Rule::in(['MALE', 'FEMALE'])],
            'education'      => ['nullable', Rule::in([
                'SD','SMP','SMA','D1','D2','D3','D4','S1','S2','S3'
            ])],
            'address'        => ['nullable', 'string'],
        ]);

        $participant->update($validated);

        return response()->json($participant);
    }

    public function destroy(Participant $participant)
    {
        $participant->delete();

        return response()->json(['message' => 'Participant deleted.']);
    }
}
