<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EventCompetition;
use App\Models\EventFieldComponent;
use App\Models\EventParticipant;
use App\Models\EventScoresheet;
use App\Models\EventScoreItem;
use App\Models\EventGroup;
use App\Models\EventGroupJudge;
use App\Models\EventBranch;
use App\Models\EventBranchJudge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class __EventCompetitionScoringController extends Controller
{
    /**
     * Ambil form penilaian:
     * - data participant (event_participant)
     * - komponen penilaian (event_field_components by event_group_id competition)
     * - daftar judges (event_group_judges; fallback event_branch_judges)
     * - nilai yang sudah ada (scoresheets + items)
     */
    public function form(Request $request, EventCompetition $competition)
    {
        $validated = $request->validate([
            'event_participant_id' => ['required','integer','exists:event_participants,id'],
        ]);

        $ep = EventParticipant::with(['participant:id,nik,full_name'])
            ->findOrFail($validated['event_participant_id']);

        // pastikan peserta masih dalam event yang sama
        if ((int) $ep->event_id !== (int) $competition->event_id) {
            return response()->json(['message' => 'Peserta bukan untuk event kompetisi ini.'], 422);
        }

        // components sesuai event_group di competition
        $components = EventFieldComponent::query()
            ->where('event_field_components.event_group_id', $competition->event_group_id)
            ->orderByRaw('COALESCE(event_field_components.order_number, 999999) asc')
            ->orderBy('event_field_components.id')
            ->get([
                'id','event_group_id','field_id','field_name','weight','max_score','order_number'
            ]);

        // judges: event_group_judges (fallback event_branch_judges)
        $judges = $this->getJudgesForCompetition($competition); // pastikan return: [{id,name},...]

        // existing scoresheets + items
        $scoresheets = EventScoresheet::query()
            ->where('event_scoresheets.event_competition_id', $competition->id)
            ->where('event_scoresheets.event_participant_id', $ep->id)
            ->with([
                'items:id,event_scoresheet_id,event_field_component_id,score,max_score,weight,weighted_score,notes',
            ])
            ->get([
                'id','event_competition_id','event_group_id','event_category_id',
                'event_participant_id','judge_id','total_score','rank_in_round','status','created_at','updated_at'
            ]);

        return response()->json([
            'competition' => [
                'id' => $competition->id,
                'event_id' => $competition->event_id,
                'event_group_id' => $competition->event_group_id,
                'round_id' => $competition->round_id,
                'full_name' => $competition->full_name,
                'status' => $competition->status,
            ],
            'participant' => [
                'event_participant_id' => $ep->id,
                'participant_id' => $ep->participant_id,
                'nik' => $ep->participant?->nik,
                'full_name' => $ep->participant?->full_name,
                'event_group_id' => $ep->event_group_id,
                'event_category_id' => $ep->event_category_id,
                'contingent' => $ep->contingent,
            ],
            'judges' => $judges,
            'components' => $components,
            'scoresheets' => $scoresheets,
        ]);
    }


    /**
     * Simpan draft nilai untuk 1 peserta pada 1 kompetisi, untuk banyak hakim sekaligus.
     */
    public function saveDraft(Request $request, EventCompetition $competition)
    {
        $data = $request->validate([
            'event_participant_id' => ['required','integer','exists:event_participants,id'],
            'rows' => ['required','array','min:1'],
            'rows.*.judge_id' => ['required','integer','exists:users,id'],
            'rows.*.items' => ['required','array'],
            'rows.*.items.*.event_field_component_id' => ['required','integer','exists:event_field_components,id'],
            'rows.*.items.*.score' => ['nullable','numeric','min:0'],
            'rows.*.items.*.notes' => ['nullable','string'],
        ]);

        $ep = EventParticipant::findOrFail($data['event_participant_id']);

        if ((int) $ep->event_id !== (int) $competition->event_id) {
            return response()->json(['message' => 'Peserta bukan untuk event kompetisi ini.'], 422);
        }

        // map komponen (harus sesuai group kompetisi)
        $componentMap = EventFieldComponent::query()
            ->where('event_group_id', $competition->event_group_id)
            ->get(['id','weight','max_score'])
            ->keyBy('id');

        DB::transaction(function () use ($data, $competition, $ep, $componentMap) {
            foreach ($data['rows'] as $row) {
                $judgeId = (int) $row['judge_id'];

                // upsert scoresheet by unique key (competition+participant+judge)
                $sheet = EventScoresheet::query()->firstOrCreate(
                    [
                        'event_competition_id' => $competition->id,
                        'event_participant_id' => $ep->id,
                        'judge_id' => $judgeId,
                    ],
                    [
                        'event_group_id' => $competition->event_group_id,
                        'event_category_id' => $ep->event_category_id, // boleh null (schema nullable)
                        'total_score' => 0,
                        'status' => 'draft',
                    ]
                );

                if ($sheet->status === 'locked') {
                    continue;
                }

                $total = 0;

                foreach ($row['items'] as $it) {
                    $cid = (int) $it['event_field_component_id'];

                    // hanya terima component yang memang milik group kompetisi
                    if (!$componentMap->has($cid)) continue;

                    $comp = $componentMap->get($cid);
                    $maxScore = (float) ($comp->max_score ?? 0);
                    $weight = (int) ($comp->weight ?? 0);

                    $score = (float) ($it['score'] ?? 0);
                    if ($maxScore > 0) $score = min($score, $maxScore);

                    // weighted = score * weight/100 (kalau weight null/0 -> score)
                    $weighted = $weight ? round($score * ($weight / 100), 2) : round($score, 2);
                    $total += $weighted;

                    EventScoreItem::query()->updateOrCreate(
                        [
                            'event_scoresheet_id' => $sheet->id,
                            'event_field_component_id' => $cid,
                        ],
                        [
                            'score' => $score,
                            'max_score' => $maxScore,
                            'weight' => $weight ?: null,
                            'weighted_score' => $weighted,
                            'notes' => $it['notes'] ?? null,
                        ]
                    );
                }

                $sheet->update([
                    'event_group_id' => $competition->event_group_id,
                    'event_category_id' => $ep->event_category_id,
                    'total_score' => round($total, 2),
                    'status' => 'draft',
                ]);
            }
        });

        return response()->json(['message' => 'Draft tersimpan.']);
    }

    public function submit(Request $request, EventCompetition $competition)
    {
        $validated = $request->validate([
            'event_participant_id' => ['required','integer','exists:event_participants,id'],
        ]);

        $ep = EventParticipant::findOrFail($validated['event_participant_id']);
        if ((int) $ep->event_id !== (int) $competition->event_id) {
            return response()->json(['message' => 'Peserta bukan untuk event kompetisi ini.'], 422);
        }

        EventScoresheet::query()
            ->where('event_competition_id', $competition->id)
            ->where('event_participant_id', $ep->id)
            ->where('status', '!=', 'locked')
            ->update(['status' => 'submitted']);

        return response()->json(['message' => 'Nilai dikirim (submitted).']);
    }

    public function lock(Request $request, EventCompetition $competition)
    {
        $validated = $request->validate([
            'event_participant_id' => ['required','integer','exists:event_participants,id'],
        ]);

        $ep = EventParticipant::findOrFail($validated['event_participant_id']);
        if ((int) $ep->event_id !== (int) $competition->event_id) {
            return response()->json(['message' => 'Peserta bukan untuk event kompetisi ini.'], 422);
        }

        EventScoresheet::query()
            ->where('event_competition_id', $competition->id)
            ->where('event_participant_id', $ep->id)
            ->update(['status' => 'locked']);

        return response()->json(['message' => 'Nilai dikunci (locked).']);
    }

    /**
     * Judges resolver:
     * - jika event_groups.use_custom_judges = true -> event_group_judges
     * - else -> event_branch_judges (mapping via event_branches by event_id + branch_id dari event_group)
     */
    private function getJudgesForCompetition(EventCompetition $competition): array
    {
        $group = EventGroup::query()
            ->whereKey($competition->event_group_id)
            ->first(['id','event_id','branch_id','use_custom_judges']);

        if (!$group) return [];

        // 1) custom judges by group
        if ((bool) $group->use_custom_judges) {
            return EventGroupJudge::query()
                ->where('event_group_id', $group->id)
                ->with(['user:id,name'])
                ->get(['id','event_group_id','user_id','is_chief'])
                ->map(fn($gj) => [
                    'id' => $gj->user?->id,
                    'name' => $gj->user?->name,
                    'is_chief' => (bool) $gj->is_chief,
                    'source' => 'group',
                ])
                ->filter(fn($x) => !empty($x['id']))
                ->values()
                ->all();
        }

        // 2) judges by branch (event_branch_judges)
        $eventBranchId = EventBranch::query()
            ->where('event_id', $competition->event_id)
            ->where('branch_id', $group->branch_id)
            ->value('id');

        if (!$eventBranchId) return [];

        return EventBranchJudge::query()
            ->where('event_branch_id', $eventBranchId)
            ->with(['user:id,name'])
            ->get(['id','event_branch_id','user_id','is_chief'])
            ->map(fn($bj) => [
                'id' => $bj->user?->id,
                'name' => $bj->user?->name,
                'is_chief' => (bool) $bj->is_chief,
                'source' => 'branch',
            ])
            ->filter(fn($x) => !empty($x['id']))
            ->values()
            ->all();
    }
}
