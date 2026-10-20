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
    public function getParticipants(Request $request)
    {
        $roundId = $request->get('round_id');
        $contingent = $request->get('contingent'); 
        $groupId = $request->get('event_group_id'); // ➕ Tangkap parameter groupId

        if (!$contingent) {
            return response()->json(['message' => 'Kontingen tidak ditemukan.'], 400);
        }

        // 1. Ambil semua Golongan Lomba yang menggunakan sistem Maqra
        $groups = EventGroup::where('has_maqra_drawing', true)->get()->keyBy('id');
        $groupIds = $groups->keys();

        // ➕ Cek status gerbang jika filter event_group_id spesifik dipilih
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

        // 3. Ambil Peserta Individu dari kecamatan tersebut
        // Tambahkan whereNull('event_team_id') agar anggota regu tidak terhitung sebagai individu
        $individuals = EventParticipant::with(['participant'])
            ->whereIn('event_group_id', $groupIds)
            ->where('contingent', $contingent)
            ->where('registration_status', 'verified')
            ->whereNull('event_team_id') // <--- TAMBAHKAN BARIS INI
            ->get();

        foreach ($individuals as $p) {
            $drawing = $drawingsParticipantDict->get($p->id);
            $groupInfo = $groups[$p->event_group_id] ?? null; // Ambil info golongan
            $result->push([
                'participant_id'     => $p->id,
                'event_group_id'     => $p->event_group_id,
                'group_name'         => $groups[$p->event_group_id]->full_name ?? '-',
                'is_drawing_open'    => $groupInfo ? (bool) $groupInfo->is_maqra_drawing_open : false, // ➕ STATUS PER BARIS
                'participant_number' => $p->participant_number,
                'name'               => $p->participant->full_name ?? 'Peserta',
                'contingent'         => $p->contingent,
                'is_team'            => false,
                'members'            => null, // Individu tidak memiliki anggota tambahan
                'drawing_status'     => $drawing ? $drawing->status : 'pending',
                'drawing_data'       => $drawing
            ]);
        }

        // 4. Ambil Peserta Regu dari kecamatan tersebut beserta relasi anggotanya
        $teams = EventTeam::with(['participants.participant'])
            ->whereIn('event_group_id', $groupIds)
            ->where('contingent', $contingent)
            ->get();

        foreach ($teams as $t) {
            $drawing = $drawingsTeamDict->get($t->id);
            $groupInfo = $groups[$t->event_group_id] ?? null; // Ambil info golongan
            $result->push([
                'participant_id'     => $t->id,
                'event_group_id'     => $t->event_group_id,
                'group_name'         => $groups[$t->event_group_id]->full_name ?? '-',
                'is_drawing_open'    => $groupInfo ? (bool) $groupInfo->is_maqra_drawing_open : false, // ➕ STATUS PER BARIS
                'participant_number' => $t->participant_number ?? ($t->branch_code . '-' . $t->branch_sequence),
                'name'               => $t->team_name ?? 'Regu ' . $t->contingent,
                'contingent'         => $t->contingent,
                'is_team'            => true,
                // Mengambil nama peserta menggunakan relasi ->participants() ke model EventParticipant, lalu ke participant()
                'members'            => $t->participants->pluck('participant.full_name')->filter()->implode(', '),
                'drawing_status'     => $drawing ? $drawing->status : 'pending',
                'drawing_data'       => $drawing
            ]);
        }

        // 5. Urutkan berdasarkan nomor peserta secara natural
        $sortedResult = $result->sortBy(fn($item) => strnatcmp($item['participant_number'], $item['participant_number']))->values();

        return response()->json([
            'is_drawing_open' => $isDrawingOpen, // ➕ Kirim data gerbang
            'pending' => $sortedResult->where('drawing_status', 'pending')->values(),
            'history' => $sortedResult->where('drawing_status', '!=', 'pending')->values(),
            'available_groups' => $sortedResult->pluck('group_name', 'event_group_id')->unique()->map(function($name, $id) {
                return ['id' => $id, 'name' => $name];
            })->values()
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
            'maqras' => $maqras,
            'packages' => $packages
        ]);
    }

    public function storeDraw(Request $request)
    {
        $request->validate([
            'round_id' => 'required',
            'event_participant_id' => 'required',
            'is_team' => 'required|boolean',
            'exam_type' => 'required',
            'event_maqra_id' => 'nullable|exists:event_maqras,id',
            'event_question_package_id' => 'nullable|exists:event_question_packages,id',
            'disable_item' => 'required|boolean'
        ]);

        // 1. PENGAMAN GATEKEEPER: Cek apakah golongan ditutup
        $groupId = null;
        if ($request->is_team) {
            $groupId = \App\Models\EventTeam::find($request->event_participant_id)->event_group_id ?? null;
        } else {
            $groupId = \App\Models\EventParticipant::find($request->event_participant_id)->event_group_id ?? null;
        }

        $group = EventGroup::find($groupId);
        if (!$group || !$group->is_maqra_drawing_open) {
            return response()->json(['message' => 'Pencabutan Maqra untuk golongan ini sedang ditutup oleh Panitia Pusat.'], 403);
        }

        DB::beginTransaction();
        try {
            // 2. PENGAMAN RACE CONDITION: Lock & Validasi ketersediaan Maqra
            if ($request->event_maqra_id) {
                $maqra = EventMaqra::where('id', $request->event_maqra_id)
                    ->lockForUpdate()
                    ->first();

                if (!$maqra || !$maqra->is_available) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Maqra tersebut sudah dicabut oleh peserta lain sesaat lalu. Silakan pilih maqra yang lain.'
                    ], 422);
                }
            }

            // 3. PENGAMAN RACE CONDITION: Lock & Validasi ketersediaan Paket Soal
            if ($request->event_question_package_id) {
                $package = EventQuestionPackage::where('id', $request->event_question_package_id)
                    ->lockForUpdate()
                    ->first();

                if (!$package || !$package->is_available) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Paket soal tersebut sudah dipilih oleh peserta lain sesaat lalu. Silakan pilih paket lain.'
                    ], 422);
                }
            }

            // 4. Simpan Transaksi Draw
            $dataDraw = [
                'round_id' => $request->round_id,
                'exam_type' => $request->exam_type,
                'event_maqra_id' => $request->event_maqra_id,
                'event_question_package_id' => $request->event_question_package_id,
                'drawn_at' => now(),
                'drawn_by' => auth()->id(),
                'status' => 'drawn'
            ];

            if ($request->is_team) {
                $dataDraw['event_team_id'] = $request->event_participant_id;
            } else {
                $dataDraw['event_participant_id'] = $request->event_participant_id;
            }

            $draw = EventMaqraDrawing::create($dataDraw);

            // 5. Nonaktifkan item jika opsi disable_item aktif
            if ($request->disable_item) {
                if ($request->event_maqra_id) {
                    EventMaqra::where('id', $request->event_maqra_id)->update(['is_available' => false]);
                }
                if ($request->event_question_package_id) {
                    EventQuestionPackage::where('id', $request->event_question_package_id)->update(['is_available' => false]);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'data' => $draw]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menyimpan undian', 'error' => $e->getMessage()], 500);
        }
    }
}