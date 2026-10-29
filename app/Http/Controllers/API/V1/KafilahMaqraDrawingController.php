<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EventGroup;
use App\Models\EventParticipant;
use App\Models\EventTeam;
use App\Models\EventMaqraDrawing;
use App\Models\EventMaqra;
use App\Models\EventQuestionPackage;
use Illuminate\Support\Facades\DB;

class KafilahMaqraDrawingController extends Controller
{
    /**
     * Helper: cek apakah branch_sequence sudah terisi (0 dianggap terisi).
     */
    private function hasBranchSequence($value): bool
    {
        return !is_null($value) && $value !== '';
    }

    public function getParticipants(Request $request)
    {
        $roundId = $request->get('round_id');
        $contingent = $request->get('contingent');
        $groupId = $request->get('event_group_id');

        if (!$contingent) {
            return response()->json(['message' => 'Kontingen tidak ditemukan.'], 400);
        }

        // 1. Ambil semua Golongan Lomba yang menggunakan sistem Maqra
        $groups = EventGroup::where('has_maqra_drawing', true)->get()->keyBy('id');
        $groupIds = $groups->keys();

        // Cek status gerbang jika filter event_group_id spesifik dipilih
        $isDrawingOpen = false;
        if ($groupId && isset($groups[$groupId])) {
            $isDrawingOpen = (bool) $groups[$groupId]->is_maqra_drawing_open;
        }

        // 2. Ambil riwayat pencabutan di babak ini
        $drawings = EventMaqraDrawing::with(['eventMaqra', 'eventQuestionPackage'])
            ->where('round_id', $roundId)
            ->get();

        $drawingsParticipantDict = $drawings->whereNotNull('event_participant_id')->keyBy('event_participant_id');
        $drawingsTeamDict = $drawings->whereNotNull('event_team_id')->keyBy('event_team_id');

        $result = collect();

        // 3. Peserta Individu (anggota regu tidak dihitung sebagai individu)
        $individuals = EventParticipant::with(['participant'])
            ->whereIn('event_group_id', $groupIds)
            ->where('contingent', $contingent)
            ->where('registration_status', 'verified')
            ->whereNull('event_team_id')
            ->get();

        foreach ($individuals as $p) {
            $drawing = $drawingsParticipantDict->get($p->id);
            $groupInfo = $groups[$p->event_group_id] ?? null;

            $result->push([
                'participant_id'     => $p->id,
                'event_group_id'     => $p->event_group_id,
                'group_name'         => $groupInfo->full_name ?? '-',
                'is_drawing_open'    => $groupInfo ? (bool) $groupInfo->is_maqra_drawing_open : false,
                'branch_sequence'    => $p->branch_sequence,
                'is_registered'      => $this->hasBranchSequence($p->branch_sequence), // ⭐️ Validasi Daftar Ulang
                'participant_number' => $p->participant_number,
                'name'               => $p->participant->full_name ?? 'Peserta',
                'contingent'         => $p->contingent,
                'is_team'            => false,
                'members'            => null,
                'drawing_status'     => $drawing ? $drawing->status : 'pending',
                'drawing_data'       => $drawing,
            ]);
        }

        // 4. Peserta Regu beserta anggotanya
        $teams = EventTeam::with(['participants.participant'])
            ->whereIn('event_group_id', $groupIds)
            ->where('contingent', $contingent)
            ->get();

        foreach ($teams as $t) {
            $drawing = $drawingsTeamDict->get($t->id);
            $groupInfo = $groups[$t->event_group_id] ?? null;
            $hasSeq = $this->hasBranchSequence($t->branch_sequence);

            $result->push([
                'participant_id'     => $t->id,
                'event_group_id'     => $t->event_group_id,
                'group_name'         => $groupInfo->full_name ?? '-',
                'is_drawing_open'    => $groupInfo ? (bool) $groupInfo->is_maqra_drawing_open : false,
                'branch_sequence'    => $t->branch_sequence,
                'is_registered'      => $hasSeq, // ⭐️ Validasi Daftar Ulang
                'participant_number' => $t->participant_number
                    ?? ($hasSeq ? ($t->branch_code . '-' . $t->branch_sequence) : null),
                'name'               => $t->team_name ?? 'Regu ' . $t->contingent,
                'contingent'         => $t->contingent,
                'is_team'            => true,
                'members'            => $t->participants->pluck('participant.full_name')->filter()->implode(', '),
                'drawing_status'     => $drawing ? $drawing->status : 'pending',
                'drawing_data'       => $drawing,
            ]);
        }

        // 5. Urutkan natural berdasarkan nomor peserta (yang belum punya nomor di akhir)
        $sortedResult = $result->sort(function ($a, $b) {
            $na = (string) ($a['participant_number'] ?? '');
            $nb = (string) ($b['participant_number'] ?? '');
            if ($na === '' && $nb !== '') return 1;
            if ($nb === '' && $na !== '') return -1;
            return strnatcmp($na, $nb);
        })->values();

        return response()->json([
            'is_drawing_open'  => $isDrawingOpen,
            'pending'          => $sortedResult->where('drawing_status', 'pending')->values(),
            'history'          => $sortedResult->where('drawing_status', '!=', 'pending')->values(),
            'available_groups' => $sortedResult->pluck('group_name', 'event_group_id')->unique()->map(function ($name, $id) {
                return ['id' => $id, 'name' => $name];
            })->values(),
        ]);
    }

    public function getAvailableItems(Request $request)
    {
        $groupId = $request->get('event_group_id');
        $roundId = $request->get('round_id');

        $maqras = EventMaqra::where('event_group_id', $groupId)
            ->where('round_id', $roundId)
            ->where('is_available', true)
            ->get();

        $packages = EventQuestionPackage::where('event_group_id', $groupId)
            ->where('round_id', $roundId)
            ->where('is_available', true)
            ->get();

        return response()->json([
            'maqras'   => $maqras,
            'packages' => $packages,
        ]);
    }

    public function storeDraw(Request $request)
    {
        $request->validate([
            'round_id'                  => 'required',
            'event_participant_id'      => 'required',
            'is_team'                   => 'required|boolean',
            'exam_type'                 => 'required',
            'event_maqra_id'            => 'nullable|exists:event_maqras,id',
            'event_question_package_id' => 'nullable|exists:event_question_packages,id',
            'disable_item'              => 'required|boolean',
        ]);

        // 1. Ambil entitas peserta / regu
        $entity = $request->is_team
            ? EventTeam::find($request->event_participant_id)
            : EventParticipant::find($request->event_participant_id);

        if (!$entity) {
            return response()->json(['message' => 'Peserta / regu tidak ditemukan.'], 404);
        }

        // 2. ⭐️ PENGAMAN DAFTAR ULANG: branch_sequence wajib terisi
        if (!$this->hasBranchSequence($entity->branch_sequence)) {
            return response()->json([
                'message' => 'Peserta / regu belum terdaftar (nomor urut cabang belum diisi). Tidak dapat mencabut maqra.'
            ], 422);
        }

        // 3. PENGAMAN GATEKEEPER: Cek apakah golongan ditutup
        $group = EventGroup::find($entity->event_group_id);
        if (!$group || !$group->is_maqra_drawing_open) {
            return response()->json(['message' => 'Pencabutan Maqra untuk golongan ini sedang ditutup oleh Panitia Pusat.'], 403);
        }

        DB::beginTransaction();
        try {
            // 4. Cegah cabut ganda di babak yang sama
            $alreadyDrawn = EventMaqraDrawing::where('round_id', $request->round_id)
                ->where($request->is_team ? 'event_team_id' : 'event_participant_id', $entity->id)
                ->lockForUpdate()
                ->exists();

            if ($alreadyDrawn) {
                DB::rollBack();
                return response()->json(['message' => 'Peserta / regu ini sudah melakukan pencabutan di babak ini.'], 422);
            }

            // 5. Lock & Validasi ketersediaan Maqra
            if ($request->event_maqra_id) {
                $maqra = EventMaqra::where('id', $request->event_maqra_id)->lockForUpdate()->first();

                if (!$maqra || !$maqra->is_available) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Amplop maqra tersebut sudah dicabut oleh peserta lain sesaat lalu. Silakan pilih amplop yang lain.'
                    ], 422);
                }
            }

            // 6. Lock & Validasi ketersediaan Paket Soal
            if ($request->event_question_package_id) {
                $package = EventQuestionPackage::where('id', $request->event_question_package_id)->lockForUpdate()->first();

                if (!$package || !$package->is_available) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Paket soal tersebut sudah dipilih oleh peserta lain sesaat lalu. Silakan pilih paket lain.'
                    ], 422);
                }
            }

            // 7. Simpan Transaksi Draw
            $dataDraw = [
                'round_id'                  => $request->round_id,
                'exam_type'                 => $request->exam_type,
                'event_maqra_id'            => $request->event_maqra_id,
                'event_question_package_id' => $request->event_question_package_id,
                'drawn_at'                  => now(),
                'drawn_by'                  => auth()->id(),
                'status'                    => 'drawn',
            ];

            if ($request->is_team) {
                $dataDraw['event_team_id'] = $entity->id;
            } else {
                $dataDraw['event_participant_id'] = $entity->id;
            }

            $draw = EventMaqraDrawing::create($dataDraw);

            // 8. Nonaktifkan item jika opsi disable_item aktif
            if ($request->disable_item) {
                if ($request->event_maqra_id) {
                    EventMaqra::where('id', $request->event_maqra_id)->update(['is_available' => false]);
                }
                if ($request->event_question_package_id) {
                    EventQuestionPackage::where('id', $request->event_question_package_id)->update(['is_available' => false]);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'data' => $draw->load(['eventMaqra', 'eventQuestionPackage'])]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menyimpan undian', 'error' => $e->getMessage()], 500);
        }
    }
}