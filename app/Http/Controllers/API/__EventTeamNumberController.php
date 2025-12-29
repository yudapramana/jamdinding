<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EventTeam;
use Illuminate\Http\Request;

class __EventTeamNumberController extends Controller
{
    public function drawNumber(EventTeam $eventTeam)
    {
        // ============================
        // VALIDASI DASAR
        // ============================

        if ($eventTeam->participant_number) {
            return response()->json([
                'message' => 'Nomor tim sudah ditetapkan.'
            ], 422);
        }

        $eventTeam->load([
            'eventGroup',
            'eventCategory',
            'eventBranch.branch',
        ]);

        if (! $eventTeam->eventGroup || ! $eventTeam->eventGroup->is_team) {
            return response()->json([
                'message' => 'Cabang ini bukan cabang grup.'
            ], 422);
        }

        if (! $eventTeam->eventCategory) {
            return response()->json([
                'message' => 'Kategori tim tidak ditemukan.'
            ], 422);
        }

        // ============================
        // DETEKSI PUTRA / PUTRI
        // ============================

        $categoryName = strtoupper($eventTeam->eventCategory->category_name ?? '');

        if (! in_array($categoryName, ['PUTRA', 'PUTRI'])) {
            return response()->json([
                'message' => 'Kategori tim harus PUTRA atau PUTRI.'
            ], 422);
        }

        // ============================
        // HITUNG TOTAL TIM PER KATEGORI
        // ============================

        $totalTeams = EventTeam::where('event_id', $eventTeam->event_id)
            ->where('event_branch_id', $eventTeam->event_branch_id)
            ->where('event_group_id', $eventTeam->event_group_id)
            ->where('event_category_id', $eventTeam->event_category_id)
            ->count();

        if ($totalTeams === 0) {
            return response()->json([
                'message' => 'Tidak ada tim pada kategori ini.'
            ], 422);
        }

        // ============================
        // NOMOR YANG SUDAH DIPAKAI
        // ============================

        $usedNumbers = EventTeam::where('event_id', $eventTeam->event_id)
            ->where('event_branch_id', $eventTeam->event_branch_id)
            ->where('event_group_id', $eventTeam->event_group_id)
            ->where('event_category_id', $eventTeam->event_category_id)
            ->whereNotNull('branch_sequence')
            ->pluck('branch_sequence')
            ->map(fn ($n) => (int) $n)
            ->toArray();

        // ============================
        // GENERATE POOL NOMOR
        // ============================

        $numbers = [];

        if ($categoryName === 'PUTRA') {
            // GENAP
            for ($i = 1; $i <= $totalTeams; $i++) {
                $numbers[] = $i * 2;
            }
        } else {
            // PUTRI → GANJIL
            for ($i = 0; $i < $totalTeams; $i++) {
                $numbers[] = ($i * 2) + 1;
            }
        }

        // ============================
        // HAPUS YANG SUDAH DIPAKAI
        // ============================

        $numbers = array_values(array_diff($numbers, $usedNumbers));

        if (empty($numbers)) {
            return response()->json([
                'message' => 'Nomor tim sudah habis untuk kategori ini.'
            ], 422);
        }

        shuffle($numbers);

        // ============================
        // RESPONSE
        // ============================

        return response()->json([
            'numbers'     => $numbers,
            'branch_code' => $eventTeam->eventBranch->branch->code,
            'category'    => $categoryName,
            'total_pool'  => count($numbers),
        ]);
    }


    public function assignNumber(Request $request, EventTeam $eventTeam)
    {
        $request->validate([
            'branch_sequence' => 'required|integer|min:1|max:99',
        ]);

        if ($eventTeam->participant_number) {
            return response()->json(['message' => 'Nomor tim sudah dikunci.'], 422);
        }

        $exists = EventTeam::where('event_id', $eventTeam->event_id)
            ->where('event_group_id', $eventTeam->event_group_id)
            ->where('branch_sequence', $request->branch_sequence)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Nomor tim sudah digunakan.'], 422);
        }

        $branchCode = $eventTeam->eventGroup->branch->code . '-G';

        $eventTeam->update([
            'branch_code' => $branchCode,
            'branch_sequence' => $request->branch_sequence,
            'participant_number' => sprintf('%s.%02d', $branchCode, $request->branch_sequence),
        ]);

        return response()->json([
            'participant_number' => $eventTeam->participant_number,
        ]);
    }
}
