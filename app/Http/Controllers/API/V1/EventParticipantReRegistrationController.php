<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\EventParticipant;
use App\Models\EventTeam;
use App\Models\Event;
use App\Models\EventGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EventParticipantReRegistrationController extends Controller
{

    public function index(Request $request)
    {
        $eventId      = $request->integer('event_id');
        $eventGroupId = $request->integer('event_group_id');
        $perPage      = $request->integer('per_page', 10);
        $search       = trim($request->string('search')->toString());
        $status       = $request->string('reregistration_status')->toString();

        if (!$eventId) {
            return response()->json([
                'message' => 'event_id wajib diisi'
            ], 422);
        }

        /**
         * 🔒 event_group_id wajib
         */
        if (!$eventGroupId) {
            return response()->json(
                paginateCollection(collect([]), $perPage)
            );
        }

        /**
         * 🔍 Ambil EventGroup
         */
        $eventGroup = EventGroup::query()
            ->where('id', $eventGroupId)
            ->where('event_id', $eventId)
            ->first();

        if (!$eventGroup) {
            return response()->json(
                paginateCollection(collect([]), $perPage)
            );
        }

        /**
         * =========================
         * 🔀 BRANCH LOGIC
         * =========================
         */
        if ($eventGroup->is_team) {

            /* =========================
            * 2️⃣ TEAM SAJA
            * ========================= */
            $items = EventTeam::query()
                ->where('event_id', $eventId)
                ->where('event_group_id', $eventGroupId)
                /**
                 * 🔒 WAJIB ADA PESERTA VERIFIED
                 * → jika tidak ada, TIM TIDAK DITAMPILKAN
                 */
                ->whereHas('participants', fn ($q) =>
                    $q->where('registration_status', 'verified')
                )
                // 🔎 FILTER DAFTAR ULANG (BERDASARKAN PESERTA YG SUDAH REG VERIFIED)
                ->when($status, fn ($q) =>
                    $q->whereHas('participants', fn ($p) =>
                        $p->where('reregistration_status', $status)
                        ->where('registration_status', 'verified') // ✅ PENTING
                    )
                )

                // 🔎 SEARCH
                ->when($search, fn ($q) =>
                    $q->where(function ($qq) use ($search) {
                        $qq->where('team_name', 'like', "%{$search}%")
                        ->orWhere('contingent', 'like', "%{$search}%")
                        ->orWhereHas('participants.participant', fn ($p) =>
                                $p->where('registration_status', 'verified') // ✅
                                ->where(function ($pp) use ($search) {
                                    $pp->where('full_name', 'like', "%{$search}%")
                                        ->orWhere('nik', 'like', "%{$search}%");
                                })
                        );
                    })
                )

                // 🔑 LOAD RELASI (HANYA PESERTA VERIFIED)
                ->with([
                    'eventGroup',
                    'eventCategory',
                    'participants' => fn ($q) =>
                        $q->where('registration_status', 'verified'),
                    'participants.participant',
                ])

                ->withCount([
                    'participants as participants_count' => fn ($q) =>
                        $q->where('registration_status', 'verified'),
                ])

                ->get()

                ->map(function ($team) {

                    return array_merge(
                        $team->toArray(),
                        [
                            'unit_type' => 'team',

                            'display_name' =>
                                $team->team_name
                                ?? 'Tim ' . ($team->branch_sequence ?? $team->id),

                            'members_count' => $team->participants_count,

                            // ✅ sudah difilter VERIFIED
                            'participants' => $team->participants,

                            // 🔥 pending daftar ulang (hanya dari verified)
                            'has_pending_reregistration' => $team->participants
                                ->contains(fn ($ep) =>
                                    in_array($ep->reregistration_status, ['not_yet', null, ''])
                                ),

                            'raw_id' => $team->id,
                        ]
                    );
            });

        } else {

            /* =========================
            * 1️⃣ INDIVIDUAL SAJA
            * ========================= */
            $items = EventParticipant::query()
                ->where('event_id', $eventId)
                ->where('event_group_id', $eventGroupId)
                ->where('registration_status', 'verified')

                ->when($status, fn ($q) =>
                    $q->where('reregistration_status', $status)
                )

                ->when($search, fn ($q) =>
                    $q->whereHas('participant', fn ($p) =>
                        $p->where('full_name', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%")
                    )
                )

                ->with(['participant', 'eventGroup', 'eventCategory'])
                ->get()
                ->map(fn ($ep) => array_merge(
                    $ep->toArray(),
                    [
                        'unit_type' => 'individual',
                        'display_name' => $ep->participant?->full_name,
                        'leader' => $ep->participant,
                        'members_count' => 1,
                        'lampiran_completion_percent' =>
                            $ep->participant?->lampiran_completion_percent ?? 0,
                        'raw_id' => $ep->id,
                    ]
            ));

        }

        /**
         * =========================
         * 📦 PAGINATE
         * =========================
         */
        return response()->json(
            paginateCollection(
                $items->sortBy('display_name', SORT_NATURAL | SORT_FLAG_CASE)->values(),
                $perPage
            )
        );
    }






    public function assignNumber(Request $request, EventParticipant $eventParticipant)
    {
        if ($eventParticipant->eventGroup->is_team) {
            return response()->json([
                'message' => 'Cabang grup menggunakan penomoran tim.'
            ], 422);
        }


        $request->validate([
            'branch_sequence' => 'required|integer|min:1|max:99',
        ]);

        if ($eventParticipant->participant_number) {
            return response()->json(['message' => 'Nomor sudah dikunci.'], 422);
        }

        $exists = EventParticipant::where('event_id', $eventParticipant->event_id)
            ->where('event_branch_id', $eventParticipant->event_branch_id)
            ->where('event_category_id', $eventParticipant->event_category_id)
            ->where('branch_sequence', $request->branch_sequence)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Nomor sudah digunakan.'], 422);
        }

        $branchCode = $eventParticipant->eventBranch->branch->code;

        $eventParticipant->update([
            'branch_code' => $branchCode,
            'branch_sequence' => $request->branch_sequence,
            'participant_number' => sprintf('%s.%02d', $branchCode, $request->branch_sequence),
        ]);

        return response()->json([
            'participant_number' => $eventParticipant->participant_number,
        ]);
    }

    
    public function drawNumber(EventParticipant $eventParticipant)
    {
        if ($eventParticipant->eventGroup->is_team) {
            return response()->json([
                'message' => 'Cabang grup menggunakan penomoran tim.'
            ], 422);
        }
        
        $eventParticipant->load([
            'participant',
            'eventBranch.branch',
        ]);

        // ❌ Sudah punya nomor
        if ($eventParticipant->participant_number) {
            return response()->json([
                'message' => 'Nomor peserta sudah ditetapkan.'
            ], 422);
        }

        $gender = strtoupper($eventParticipant->participant->gender); // MALE / FEMALE

        // 🔢 HITUNG TOTAL PESERTA PER CATEGORY (PUTRA / PUTRI)
        $totalCategoryParticipants = EventParticipant::where('event_id', $eventParticipant->event_id)
            ->where('event_branch_id', $eventParticipant->event_branch_id)
            ->where('event_group_id', $eventParticipant->event_group_id)
            ->where('event_category_id', $eventParticipant->event_category_id)
            ->count();

        if ($totalCategoryParticipants === 0) {
            return response()->json([
                'message' => 'Tidak ada peserta pada kategori ini.'
            ], 422);
        }

        // 🔁 NOMOR YANG SUDAH DIPAKAI (KHUSUS CATEGORY INI)
        $usedNumbers = EventParticipant::where('event_id', $eventParticipant->event_id)
            ->where('event_branch_id', $eventParticipant->event_branch_id)
            ->where('event_group_id', $eventParticipant->event_group_id)
            ->where('event_category_id', $eventParticipant->event_category_id)
            ->whereNotNull('branch_sequence')
            ->pluck('branch_sequence')
            ->map(fn ($n) => (int) $n)
            ->toArray();

        /**
         * 🔢 GENERATE POOL:
         * - PUTRA  → GENAP : 2,4,6,...(2 * total)
         * - PUTRI  → GANJIL: 1,3,5,...(2 * total - 1)
         */
        $numbers = [];

        

        if ($gender === 'MALE') {
            // PUTRA → GENAP
            for ($i = 1; $i <= $totalCategoryParticipants; $i++) {
                $numbers[] = $i * 2;
            }
        } else {
            // PUTRI → GANJIL
            for ($i = 0; $i < $totalCategoryParticipants; $i++) {
                $numbers[] = ($i * 2) + 1;
            }
        }

        // return [
        //     'totalCategoryParticipants' => $totalCategoryParticipants,
        //     'numbers' => $numbers,
        //     'usedNumbers' => $usedNumbers,
        // ];

        // ❌ HAPUS NOMOR YANG SUDAH DIPAKAI
        $numbers = array_values(array_diff($numbers, $usedNumbers));

        if (empty($numbers)) {
            return response()->json([
                'message' => 'Nomor peserta sudah habis untuk kategori ini.'
            ], 422);
        }

        // 🔀 ACAK UNTUK UNDIAN
        shuffle($numbers);

        return response()->json([
            'numbers'     => $numbers,
            'branch_code' => $eventParticipant->eventBranch->branch->code, // FH.01
            'gender'      => $gender,
            'total_pool'  => count($numbers),
        ]);
    }




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
