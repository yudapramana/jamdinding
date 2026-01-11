<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\EventJudgePanelMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventJudgePanelMemberController extends Controller
{
    /**
     * ===============================
     * SYNC MEMBERS (BULK)
     * ===============================
     */
    public function syncMembers(Request $request, $panelId)
    {
        $data = $request->validate([
            'members' => 'required|array|min:1',
            'members.*.event_judge_id' => 'required|exists:event_judges,id',
            'members.*.is_chief'       => 'boolean',
            'members.*.order_number'   => 'nullable|integer|min:1',
        ]);

        return DB::transaction(function () use ($panelId, $data) {

            // ===============================
            // 1. Ambil data existing
            // ===============================
            $existingMembers = EventJudgePanelMember::where('event_judge_panel_id', $panelId)
                ->get()
                ->keyBy('event_judge_id');

            $incomingJudgeIds = collect($data['members'])
                ->pluck('event_judge_id')
                ->unique()
                ->values();

            // ===============================
            // 2. Hapus anggota yang tidak ada lagi
            // ===============================
            EventJudgePanelMember::where('event_judge_panel_id', $panelId)
                ->whereNotIn('event_judge_id', $incomingJudgeIds)
                ->delete();

            // ===============================
            // 3. Validasi: hanya 1 ketua
            // ===============================
            $chiefCount = collect($data['members'])
                ->where('is_chief', true)
                ->count();

            if ($chiefCount > 1) {
                abort(422, 'Hanya boleh ada satu ketua majelis.');
            }

            // reset ketua
            EventJudgePanelMember::where('event_judge_panel_id', $panelId)
                ->update(['is_chief' => false]);

            // ===============================
            // 4. Insert / Update anggota
            // ===============================
            foreach ($data['members'] as $index => $member) {

                $payload = [
                    'event_judge_panel_id' => $panelId,
                    'event_judge_id'       => $member['event_judge_id'],
                    'is_chief'             => $member['is_chief'] ?? false,
                    'order_number'         => $member['order_number'] ?? ($index + 1),
                ];

                if ($existingMembers->has($member['event_judge_id'])) {
                    $existingMembers[$member['event_judge_id']]->update($payload);
                } else {
                    EventJudgePanelMember::create($payload);
                }
            }

            return response()->json([
                'message' => 'Anggota majelis berhasil disinkronkan',
            ]);
        });
    }

    /**
     * ===============================
     * LIST MEMBERS
     * ===============================
     */
    public function index($panelId)
    {
        $members = EventJudgePanelMember::with('eventJudge.user')
            ->where('event_judge_panel_id', $panelId)
            ->orderByDesc('is_chief')
            ->orderBy('order_number')
            ->get();

        return response()->json([
            'data' => $members,
        ]);
    }

    /**
     * ===============================
     * ADD SINGLE MEMBER
     * ===============================
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'event_judge_panel_id' => 'required|exists:event_judge_panels,id',
            'event_judge_id'       => 'required|exists:event_judges,id',
            'is_chief'             => 'boolean',
            'order_number'         => 'nullable|integer|min:1',
        ]);

        return DB::transaction(function () use ($data) {

            if (!empty($data['is_chief'])) {
                EventJudgePanelMember::where('event_judge_panel_id', $data['event_judge_panel_id'])
                    ->update(['is_chief' => false]);
            }

            $member = EventJudgePanelMember::create($data);

            return response()->json([
                'message' => 'Anggota majelis ditambahkan',
                'data'    => $member,
            ], 201);
        });
    }

    /**
     * ===============================
     * UPDATE MEMBER
     * ===============================
     */
    public function update(Request $request, $id)
    {
        $member = EventJudgePanelMember::findOrFail($id);

        $data = $request->validate([
            'is_chief'     => 'nullable|boolean',
            'order_number' => 'nullable|integer|min:1',
        ]);

        return DB::transaction(function () use ($member, $data) {

            if (!empty($data['is_chief'])) {
                EventJudgePanelMember::where('event_judge_panel_id', $member->event_judge_panel_id)
                    ->update(['is_chief' => false]);
            }

            $member->update($data);

            return response()->json([
                'message' => 'Anggota majelis diperbarui',
                'data'    => $member,
            ]);
        });
    }

    /**
     * ===============================
     * DELETE MEMBER
     * ===============================
     */
    public function destroy($id)
    {
        EventJudgePanelMember::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Anggota majelis dihapus',
        ]);
    }
}
