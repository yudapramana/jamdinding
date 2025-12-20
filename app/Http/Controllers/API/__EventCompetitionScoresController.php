<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EventCompetition;
use App\Models\EventScoresheet;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class __EventCompetitionScoresController extends Controller
{
    public function index(Request $request, EventCompetition $competition)
    {
        // optional filters
        $search = $request->get('search');
        $status = $request->get('status'); // draft/submitted/locked

        // Ambil semua scoresheet utk kompetisi ini
        $q = EventScoresheet::query()
            ->with([
                'judge:id,name,username',
                'eventParticipant:id,event_id,participant_id,event_branch_id,event_group_id,event_category_id,contingent',
                'eventParticipant.participant:id,full_name,gender',
                'eventParticipant.eventBranch:id,full_name',
                'eventParticipant.eventGroup:id,full_name,is_team',
                'eventParticipant.eventCategory:id,full_name',
            ])
            ->where('event_competition_id', $competition->id);

        if ($status) {
            $q->where('status', $status);
        }

        if ($search) {
            $q->whereHas('eventParticipant.participant', function ($qq) use ($search) {
                $qq->where('full_name', 'like', "%{$search}%");
            });
        }

        $rows = $q->get();

        // Bentuk matrix: peserta -> [judge_id => total_score]
        $participants = [];
        $judgesMap = [];

        foreach ($rows as $ss) {
            $ep = $ss->eventParticipant;
            if (!$ep || !$ep->participant) continue;

            $pid = $ep->id;
            $judgeId = $ss->judge_id;

            $judgesMap[$judgeId] = [
                'id' => $judgeId,
                'name' => $ss->judge?->name ?? 'Hakim',
                'username' => $ss->judge?->username,
            ];

            if (!isset($participants[$pid])) {
                $participants[$pid] = [
                    'event_participant_id' => $ep->id,
                    'participant_id' => $ep->participant_id,
                    'full_name' => $ep->participant->full_name,
                    'gender' => $ep->participant->gender,
                    'contingent' => $ep->contingent,
                    'branch' => $ep->eventBranch?->full_name,
                    'group' => $ep->eventGroup?->full_name,

                    // ✅ FIX untuk tabs
                    'event_category_id' => $ep->event_category_id,
                    'category_name'     => $ep->eventCategory?->full_name,
                    // opsional legacy
                    'category'          => $ep->eventCategory?->full_name,

                    'scores' => [],
                    'status_by_judge' => [],
                    'scoresheet_id_by_judge' => [],
                ];
            }


            $participants[$pid]['scores'][$judgeId] = (float) $ss->total_score;
            $participants[$pid]['status_by_judge'][$judgeId] = $ss->status;
            $participants[$pid]['scoresheet_id_by_judge'][$judgeId] = $ss->id;
        }

        // hitung total & average per peserta
        foreach ($participants as &$p) {
            $vals = array_values($p['scores']);
            $p['judge_count'] = count($vals);
            $p['sum_score'] = round(array_sum($vals), 2);
            $p['avg_score'] = $p['judge_count'] > 0 ? round($p['sum_score'] / $p['judge_count'], 2) : 0;
        }
        unset($p);

        $competition->load([
            'eventGroup:id,full_name,is_team',
        ]);

        return response()->json([
            'competition' => [
                'id' => $competition->id,
                'full_name' => $competition->full_name,
                'status' => $competition->status,
                'scheduled_at' => $competition->scheduled_at,
                'venue' => $competition->venue,

                // ✅ PENTING: untuk mode grup / team di frontend
                'is_team' => (bool) $competition->eventGroup->is_team,
                'event_group' => $competition->eventGroup ? [
                    'id' => $competition->eventGroup->id,
                    'full_name' => $competition->eventGroup->full_name,
                    'is_team' => (bool) $competition->eventGroup->is_team,
                ] : null,
            ],
            'judges' => array_values($judgesMap),
            'participants' => array_values($participants),
        ]);
    }

    public function indexDetail(Request $request, EventCompetition $competition)
    {
        $search = $request->get('search');
        $status = $request->get('status'); // draft/submitted/locked

        $fieldComponents = \App\Models\EventFieldComponent::query()
            ->where('event_group_id', $competition->event_group_id)
            ->orderByRaw('COALESCE(order_number, 999999) asc')
            ->get(['id','field_id','field_name','order_number','max_score','weight']);

        $fields = $fieldComponents->map(fn($fc) => [
            'event_field_component_id' => $fc->id,
            'field_id' => $fc->field_id,
            'name' => $fc->field_name,
            'order_number' => $fc->order_number,
            'max_score' => $fc->max_score,
            'weight' => $fc->weight,
        ])->values();

        $q = EventScoresheet::query()
            ->with([
                'judge:id,name,username',
                'eventParticipant:id,event_id,participant_id,event_branch_id,event_group_id,event_category_id,contingent',
                'eventParticipant.participant:id,full_name,gender',
                'eventParticipant.eventBranch:id,full_name',
                'eventParticipant.eventGroup:id,full_name',
                'eventParticipant.eventCategory:id,full_name',
                'items:id,event_scoresheet_id,event_field_component_id,score,max_score,weight,weighted_score',
            ])
            ->where('event_competition_id', $competition->id);

        if ($status) $q->where('status', $status);

        if ($search) {
            $q->whereHas('eventParticipant.participant', function ($qq) use ($search) {
                $qq->where('full_name', 'like', "%{$search}%");
            });
        }

        $rows = $q->get();

        $participants = [];
        $judgesMap = [];

        foreach ($rows as $ss) {
            $ep = $ss->eventParticipant;
            if (!$ep || !$ep->participant) continue;

            $pid = $ep->id;
            $judgeId = $ss->judge_id;

            $judgesMap[$judgeId] = [
                'id' => $judgeId,
                'name' => $ss->judge?->name ?? 'Hakim',
                'username' => $ss->judge?->username,
            ];

            if (!isset($participants[$pid])) {
                $participants[$pid] = [
                    'event_participant_id' => $ep->id,
                    'participant_id' => $ep->participant_id,
                    'full_name' => $ep->participant->full_name,
                    'gender' => $ep->participant->gender,
                    'contingent' => $ep->contingent,
                    'branch' => $ep->eventBranch?->full_name,
                    'group' => $ep->eventGroup?->full_name,

                    // ✅ untuk tabs
                    'event_category_id' => $ep->event_category_id,
                    'category_name'     => $ep->eventCategory?->full_name,
                    'category'          => $ep->eventCategory?->full_name,

                    'scores' => [],
                    'status_by_judge' => [],
                    'scoresheet_id_by_judge' => [],
                    'field_scores' => [],
                ];
            }

            $participants[$pid]['scores'][$judgeId] = (float) $ss->total_score;
            $participants[$pid]['status_by_judge'][$judgeId] = $ss->status;
            $participants[$pid]['scoresheet_id_by_judge'][$judgeId] = $ss->id;

            if (!isset($participants[$pid]['field_scores'][$judgeId])) {
                $participants[$pid]['field_scores'][$judgeId] = [];
                foreach ($fieldComponents as $fc) {
                    $participants[$pid]['field_scores'][$judgeId][$fc->id] = null;
                }
            }

            foreach ($ss->items as $item) {
                $fcId = $item->event_field_component_id;
                if (!$fcId) continue;

                $participants[$pid]['field_scores'][$judgeId][$fcId] = [
                    'score' => (float) $item->score,
                    'weighted_score' => (float) $item->weighted_score,
                    'max_score' => (float) $item->max_score,
                    'weight' => $item->weight,
                ];
            }
        }

        foreach ($participants as &$p) {
            $vals = array_values($p['scores']);
            $p['judge_count'] = count($vals);
            $p['sum_score'] = round(array_sum($vals), 2);
            $p['avg_score'] = $p['judge_count'] > 0 ? round($p['sum_score'] / $p['judge_count'], 2) : 0;
        }
        unset($p);

        $competition->load([
            'eventGroup:id,full_name,is_team',
        ]);


        return response()->json([
            'competition' => [
                'id' => $competition->id,
                'full_name' => $competition->full_name,
                'status' => $competition->status,
                'scheduled_at' => $competition->scheduled_at,
                'venue' => $competition->venue,

                // ✅ PENTING: untuk mode grup / team di frontend
                'is_team' => (bool) $competition->eventGroup->is_team,
                'event_group' => $competition->eventGroup ? [
                    'id' => $competition->eventGroup->id,
                    'full_name' => $competition->eventGroup->full_name,
                    'is_team' => (bool) $competition->eventGroup->is_team,
                ] : null,
            ],
            'fields' => $fields,
            'judges' => array_values($judgesMap),
            'participants' => array_values($participants),
        ]);
    }


    public function update(Request $request, EventCompetition $competition, EventScoresheet $scoresheet)
    {
        if ($scoresheet->event_competition_id !== $competition->id) {
            return response()->json(['message' => 'Scoresheet not in this competition'], 422);
        }

        $data = $request->validate([
            'status' => ['nullable', Rule::in(['draft','submitted','locked'])],
            'rank_in_round' => ['nullable','integer','min:1'],
        ]);

        // contoh aturan: kalau locked, jangan ubah rank? (opsional)
        $scoresheet->fill($data)->save();

        return response()->json([
            'message' => 'Updated',
            'data' => $scoresheet->fresh(),
        ]);
    }
}
