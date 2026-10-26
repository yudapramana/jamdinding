<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EventGroup;
use App\Models\EventParticipant;
use App\Models\EventMaqraDrawing;
use App\Models\EventMaqra;
use App\Models\EventQuestionPackage;
use Illuminate\Support\Facades\DB;

class EventMaqraDrawingController extends Controller
{
    // Mengambil daftar peserta yang belum dan sudah mencabut undian
    public function getParticipants(Request $request)
    {
        $groupId = $request->get('event_group_id');
        $roundId = $request->get('round_id');

        // Cek apakah golongan ini adalah Regu/Tim atau Individu
        $group = EventGroup::findOrFail($groupId);
        $isTeam = $group->is_team;

        // Array Urutan Kontingen Kecamatan
        $districts = [
            "IV Jurai", 
            "Bayang", 
            "IV Nagari Bayang Utara", 
            "Batang Kapas", 
            "Koto XI Tarusan", 
            "Sutera", 
            "Lengayang", 
            "Ranah Pesisir", 
            "Linggo Sari Baganti", 
            "Airpura", 
            "Pancung Soal", 
            "Basa Ampek Balai Tapan", 
            "Ranah Ampek Hulu Tapan", 
            "Lunang", 
            "Silaut"
        ];

        // Buat mapping [Nama_Kecamatan => Urutan_Index] untuk pencarian cepat
        $districtOrder = array_flip($districts);

        // Ambil riwayat pencabutan di babak ini
        $drawings = EventMaqraDrawing::with(['eventMaqra', 'eventQuestionPackage', 'drawnBy'])
            ->where('round_id', $roundId)
            ->get();

        if ($isTeam) {
            // ---------------------------------------------------------
            // LOGIKA UNTUK PESERTA REGU (Misal: Fahmil, Syarhil)
            // ---------------------------------------------------------
            $teams = \App\Models\EventTeam::where('event_group_id', $groupId)
                ->get();

            // Mapping berdasarkan event_team_id
            $drawingsDict = $drawings->keyBy('event_team_id');

            $result = $teams->map(function ($t) use ($drawingsDict) {
                $drawing = $drawingsDict->get($t->id);
                return [
                    'participant_id' => $t->id,
                    'participant_number' => $t->participant_number ?? ($t->branch_code . '-' . $t->branch_sequence),
                    'name' => $t->team_name ?? 'Regu ' . $t->contingent,
                    'contingent' => $t->contingent,
                    'is_team' => true,
                    'drawing_status' => $drawing ? $drawing->status : 'pending',
                    'drawing_data' => $drawing,
                    'branch_sequence' => $t->branch_sequence,
                ];
            });

        } else {
            // ---------------------------------------------------------
            // LOGIKA UNTUK PESERTA INDIVIDU (Misal: Tilawah, Tahfiz)
            // ---------------------------------------------------------
            $participants = \App\Models\EventParticipant::with(['participant'])
                ->where('event_group_id', $groupId)
                ->where('registration_status', 'verified')
                ->get();

            // Mapping berdasarkan event_participant_id
            $drawingsDict = $drawings->keyBy('event_participant_id');

            $result = $participants->map(function ($p) use ($drawingsDict) {
                $drawing = $drawingsDict->get($p->id);
                return [
                    'participant_id' => $p->id,
                    'participant_number' => $p->participant_number,
                    'name' => $p->participant->full_name ?? 'Peserta',
                    'contingent' => $p->contingent,
                    'is_team' => false,
                    'drawing_status' => $drawing ? $drawing->status : 'pending',
                    'drawing_data' => $drawing,
                    'branch_sequence' => $p->branch_sequence,
                ];
            });
        }

        // PERBAIKAN: Urutkan pertama berdasarkan Kontingen (Kecamatan),
        // kemudian urutkan kedua berdasarkan Nomor Peserta (participant_number) secara natural
        $sortedResult = $result->sortBy([
            fn ($a, $b) => ($districtOrder[trim($a['contingent'] ?? '')] ?? 999) <=> ($districtOrder[trim($b['contingent'] ?? '')] ?? 999),
            fn ($a, $b) => strnatcmp($a['participant_number'] ?? '', $b['participant_number'] ?? ''),
        ]);

        return response()->json([
            'is_drawing_open' => (bool) $group->is_maqra_drawing_open, // Kirim status gerbang
            'pending' => $sortedResult->where('drawing_status', 'pending')->values(),
            'history' => $sortedResult->where('drawing_status', '!=', 'pending')->values(),
        ]);
    }

    // Mengambil sisa bank maqra & soal yang tersedia
    public function getAvailableItems(Request $request)
    {
        $groupId = $request->get('event_group_id');
        $roundId = $request->get('round_id'); // Tangkap round_id

        // Cek status gerbang pencabutan pada golongan ini
        $group = EventGroup::findOrFail($groupId);
        $isOpen = (bool) $group->is_maqra_drawing_open;

        // Jika pencabutan ditutup, jangan kirim maqra & paket soal sama sekali
        if (!$isOpen) {
            return response()->json([
                'is_drawing_open' => false,
                'maqras' => [],
                'packages' => []
            ]);
        }
        
        $maqras = EventMaqra::where('event_group_id', $groupId)
            ->where('round_id', $roundId) // Filter tambahan
            ->where('is_available', true)
            ->get();
            
        $packages = EventQuestionPackage::where('event_group_id', $groupId)
            ->where('round_id', $roundId) // Filter tambahan
            ->where('is_available', true)
            ->get();

        return response()->json([
            'is_drawing_open' => true,
            'maqras' => $maqras,
            'packages' => $packages
        ]);
    }

    // Menyimpan hasil pencabutan
    public function storeDraw(Request $request)
    {
        $request->validate([
            'round_id' => 'required',
            'event_participant_id' => 'required', // ID yang dikirim dari Vue
            'is_team' => 'required|boolean',      // Tambahkan flag ini dari Vue
            'exam_type' => 'required',
            'event_maqra_id' => 'nullable|exists:event_maqras,id',
            'event_question_package_id' => 'nullable|exists:event_question_packages,id',
            'disable_item' => 'required|boolean'
        ]);

        $groupId = null;
        if ($request->is_team) {
            $groupId = \App\Models\EventTeam::find($request->event_participant_id)->event_group_id ?? null;
        } else {
            $groupId = \App\Models\EventParticipant::find($request->event_participant_id)->event_group_id ?? null;
        }

        $group = EventGroup::find($groupId);
        if (!$group || !$group->is_maqra_drawing_open) {
            return response()->json(['message' => 'Pencabutan Maqra untuk golongan ini sedang ditutup.'], 403);
        }

        DB::beginTransaction();
        try {
            // 1. PENGAMAN RACE CONDITION: Lock & Validasi ketersediaan Maqra
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

            // 2. PENGAMAN RACE CONDITION: Lock & Validasi ketersediaan Paket Soal
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

            // 3. Simpan Transaksi Draw
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

            // 4. Nonaktifkan item jika dipilih
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

    // 3. Tambahkan Method Baru Khusus Admin untuk Mengubah Status
    public function toggleDrawingStatus(Request $request)
    {
        $request->validate([
            'event_group_id' => 'required|exists:event_groups,id',
            'is_open' => 'required|boolean'
        ]);

        // Opsional: Pastikan hanya SUPERADMIN / ADMIN_EVENT yang bisa mengakses ini
        // if (!in_array(auth()->user()->role->name, ['SUPERADMIN', 'ADMIN_EVENT'])) abort(403);

        $group = EventGroup::find($request->event_group_id);
        $group->is_maqra_drawing_open = $request->is_open;
        $group->save();

        return response()->json([
            'success' => true, 
            'message' => $request->is_open ? 'Pencabutan Maqra Dibuka' : 'Pencabutan Maqra Ditutup'
        ]);
    }
}