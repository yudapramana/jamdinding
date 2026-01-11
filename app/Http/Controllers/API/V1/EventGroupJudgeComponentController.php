<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\EventGroup;
use App\Models\Event;
use App\Models\Branch;
use App\Models\EventFieldComponentJudge;
use App\Models\EventJudgePanel;
use App\Models\Group;
use App\Models\MasterGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventGroupJudgeComponentController extends Controller
{
    public function index(EventGroup $eventGroup)
    {
        $eventGroup->load([
            'fieldComponents',
            'judgePanel.members.eventJudge.masterJudge'
        ]);

        $availableJudges = $eventGroup
            ->judgePanel
            ->members
            ->map(fn ($m) => [
                'id' => $m->event_judge_id,
                'full_name' => $m->eventJudge->masterJudge->full_name,
            ])
            ->values();
            
        // return $eventGroup->fieldComponents;

        $data = $eventGroup->fieldComponents->map(function ($comp) use ($availableJudges) {
            return [
                'event_field_component_id' => $comp->id,
                'field_name' => $comp->field_name,
                'judge_ids' => $comp->componentJudges->pluck('event_judge_id'),
                'available_judges' => $availableJudges,
            ];
        });

        return response()->json([
            'data' => $data,
        ]);
    }

    public function store(Request $request, EventGroup $eventGroup)
    {
        $data = $request->validate([
            'components' => ['required', 'array'],
            'components.*.event_field_component_id' => ['required', 'exists:event_field_components,id'],
            'components.*.judge_ids' => ['array'],
            'components.*.judge_ids.*' => ['exists:event_judges,id'],
        ]);

        foreach ($data['components'] as $row) {
            EventFieldComponentJudge::where(
                'event_field_component_id',
                $row['event_field_component_id']
            )->delete();

            foreach ($row['judge_ids'] ?? [] as $judgeId) {
                EventFieldComponentJudge::create([
                    'event_field_component_id' => $row['event_field_component_id'],
                    'event_judge_id' => $judgeId,
                    'event_judge_panel_id' => $eventGroup->event_judge_panel_id,
                ]);
            }
        }

        return response()->json([
            'message' => 'Pengaturan juri per komponen berhasil disimpan.',
        ]);
    }


    
}
