<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\JudgePanel;
use App\Models\JudgePanelMember;
use App\Models\MasterJudge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JudgePanelController extends Controller
{
    public function index($eventId)
    {
        return JudgePanel::with([
                'members.masterJudge.user'
            ])
            ->where('event_id', $eventId)
            ->orderBy('name')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'event_id' => 'required|exists:events,id',
            'code'     => 'nullable|string|max:50',
            'name'     => 'required|string|max:255',
            'notes'    => 'nullable|string',
            'is_active'=> 'boolean',
        ]);

        return JudgePanel::create($data);
    }

    public function update(Request $request, JudgePanel $judgePanel)
    {
        $judgePanel->update(
            $request->only(['code','name','notes','is_active'])
        );

        return $judgePanel;
    }

    public function destroy(JudgePanel $judgePanel)
    {
        $judgePanel->delete();
        return response()->noContent();
    }

    /**
     * ===============================
     * MEMBERS
     * ===============================
     */
    public function addMember(Request $request, JudgePanel $judgePanel)
    {
        $data = $request->validate([
            'master_judge_id' => ['required', 'exists:master_judges,id'],
        ]);

        // pastikan tidak dobel
        $judgePanel->members()->create([
            'master_judge_id' => $data['master_judge_id'],
            'order_number'    => $judgePanel->members()->max('order_number') + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Hakim berhasil ditambahkan ke majelis',
        ]);
    }


    public function updateMember(Request $request, JudgePanelMember $member)
    {
        return DB::transaction(function () use ($request, $member) {

            if ($request->boolean('is_chief')) {
                JudgePanelMember::where('judge_panel_id', $member->judge_panel_id)
                    ->update(['is_chief' => false]);
            }

            $member->update(
                $request->only(['is_chief','order_number'])
            );

            return $member;
        });
    }

    public function removeMember(JudgePanelMember $member)
    {
        $member->delete();
        return response()->noContent();
    }
}
