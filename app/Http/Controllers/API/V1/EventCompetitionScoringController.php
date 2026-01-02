<?php

namespace App\Http\Controllers\API\V1;

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

class EventCompetitionScoringController extends Controller
{
    /**
     * Resolve apakah group ini TEAM atau INDIVIDUAL.
     */
    private function isTeamMode(EventCompetition $competition): bool
    {
        return (bool) EventGroup::query()
            ->whereKey($competition->event_group_id)
            ->value('is_team');
    }

    /**
     * Ambil list EP target:
     * - INDIVIDUAL: hanya EP itu
     * - TEAM: semua EP yang contingent + category + group + event sama
     */
    private function resolveTargetEventParticipantIds(EventCompetition $competition, EventParticipant $ep): array
    {
        $isTeam = $this->isTeamMode($competition);

        if (!$isTeam) return [$ep->id];

        $contingent = trim((string) $ep->contingent);

        // kalau contingent kosong, team tidak bisa dibuat → fallback ke individual
        if ($contingent === '') return [$ep->id];

        return EventParticipant::query()
            ->where('event_id', $competition->event_id)
            ->where('event_group_id', $competition->event_group_id)
            ->where('event_category_id', $ep->event_category_id)
            ->where('contingent', $contingent)
            ->pluck('id')
            ->map(fn($x) => (int) $x)
            ->all();
    }

    /**
     * FORM
     */
    public function form(Request $request, EventCompetition $competition)
    {
        $validated = $request->validate([
            'event_participant_id' => ['required','integer','exists:event_participants,id'],
        ]);

        $ep = EventParticipant::with(['participant:id,nik,full_name'])
            ->findOrFail($validated['event_participant_id']);

        if ((int) $ep->event_id !== (int) $competition->event_id) {
            return response()->json(['message' => 'Peserta bukan untuk event kompetisi ini.'], 422);
        }

        $isTeam = $this->isTeamMode($competition);
        $targetEpIds = $this->resolveTargetEventParticipantIds($competition, $ep);

        // components sesuai event_group di competition
        $components = EventFieldComponent::query()
            ->where('event_group_id', $competition->event_group_id)
            ->orderByRaw('COALESCE(order_number, 999999) asc')
            ->orderBy('id')
            ->get(['id','event_group_id','field_id','field_name','weight','max_score','order_number']);

        $judges = $this->getJudgesForCompetition($competition);

        /**
         * TEAM MODE:
         * scoresheets bisa ada banyak per anggota.
         * Namun UI kamu menganggap 1 sheet per judge untuk “tim”.
         *
         * Strategi aman:
         * - Ambil scoresheets milik representative EP (yang dipilih).
         * - Karena saveDraft/submit/lock nanti akan menyamakan ke semua anggota,
         *   maka data representative sudah cukup sebagai “tampilan form”.
         */
        $sheetEpIdForView = $ep->id;

        $scoresheets = EventScoresheet::query()
            ->where('event_competition_id', $competition->id)
            ->where('event_participant_id', $sheetEpIdForView)
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
                'is_team' => $isTeam,
            ],
            'participant' => [
                'event_participant_id' => $ep->id,
                'participant_id' => $ep->participant_id,
                'nik' => $ep->participant?->nik,
                'full_name' => $ep->participant?->full_name,
                'event_group_id' => $ep->event_group_id,
                'event_category_id' => $ep->event_category_id,
                'contingent' => $ep->contingent,
                'team_member_count' => $isTeam ? count($targetEpIds) : 1,
            ],
            'judges' => $judges,
            'components' => $components,
            'scoresheets' => $scoresheets,
        ]);
    }

    /**
     * SAVE DRAFT
     * - INDIVIDUAL: sama seperti sebelumnya
     * - TEAM: apply nilai yang sama ke seluruh anggota tim (targetEpIds)
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

        // komponen harus sesuai group kompetisi
        $componentMap = EventFieldComponent::query()
            ->where('event_group_id', $competition->event_group_id)
            ->get(['id','weight','max_score'])
            ->keyBy('id');

        $targetEpIds = $this->resolveTargetEventParticipantIds($competition, $ep);

        DB::transaction(function () use ($data, $competition, $ep, $componentMap, $targetEpIds) {
            foreach ($data['rows'] as $row) {
                $judgeId = (int) $row['judge_id'];

                // hitung total sekali (nilai tim/individu sama)
                $total = 0;
                $normalizedItems = [];

                foreach ($row['items'] as $it) {
                    $cid = (int) $it['event_field_component_id'];
                    if (!$componentMap->has($cid)) continue;

                    $comp = $componentMap->get($cid);
                    $maxScore = (float) ($comp->max_score ?? 0);
                    $weight = (int) ($comp->weight ?? 0);

                    $score = (float) ($it['score'] ?? 0);
                    if ($score < 0) $score = 0;
                    if ($maxScore > 0) $score = min($score, $maxScore);

                    $weighted = $weight ? round($score * ($weight / 100), 2) : round($score, 2);
                    $total += $weighted;

                    $normalizedItems[] = [
                        'cid' => $cid,
                        'score' => $score,
                        'maxScore' => $maxScore,
                        'weight' => $weight ?: null,
                        'weighted' => $weighted,
                        'notes' => $it['notes'] ?? null,
                    ];
                }

                $total = round($total, 2);

                // APPLY ke semua EP target (TEAM: banyak, INDIV: 1)
                foreach ($targetEpIds as $epId) {
                    // ambil event_category_id berdasarkan EP masing-masing (harus sama saat team)
                    $epRow = $epId === $ep->id ? $ep : null;

                    if (!$epRow) {
                        $epRow = EventParticipant::query()
                            ->whereKey($epId)
                            ->first(['id','event_category_id']);
                    }

                    $sheet = EventScoresheet::query()->firstOrCreate(
                        [
                            'event_competition_id' => $competition->id,
                            'event_participant_id' => $epId,
                            'judge_id' => $judgeId,
                        ],
                        [
                            'event_group_id' => $competition->event_group_id,
                            'event_category_id' => $epRow?->event_category_id,
                            'total_score' => 0,
                            'status' => 'draft',
                        ]
                    );

                    if ($sheet->status === 'locked') {
                        continue;
                    }

                    foreach ($normalizedItems as $ni) {
                        EventScoreItem::query()->updateOrCreate(
                            [
                                'event_scoresheet_id' => $sheet->id,
                                'event_field_component_id' => $ni['cid'],
                            ],
                            [
                                'score' => $ni['score'],
                                'max_score' => $ni['maxScore'],
                                'weight' => $ni['weight'],
                                'weighted_score' => $ni['weighted'],
                                'notes' => $ni['notes'],
                            ]
                        );
                    }

                    $sheet->update([
                        'event_group_id' => $competition->event_group_id,
                        'event_category_id' => $epRow?->event_category_id,
                        'total_score' => $total,
                        'status' => 'draft',
                    ]);
                }
            }
        });

        return response()->json([
            'message' => 'Draft tersimpan.',
            'applied_to' => count($targetEpIds),
        ]);
    }

    /**
     * SUBMIT
     * - TEAM: submit semua EP dalam tim
     */
    public function submit(Request $request, EventCompetition $competition)
    {
        $validated = $request->validate([
            'event_participant_id' => ['required','integer','exists:event_participants,id'],
        ]);

        $ep = EventParticipant::findOrFail($validated['event_participant_id']);

        if ((int) $ep->event_id !== (int) $competition->event_id) {
            return response()->json(['message' => 'Peserta bukan untuk event kompetisi ini.'], 422);
        }

        $targetEpIds = $this->resolveTargetEventParticipantIds($competition, $ep);

        EventScoresheet::query()
            ->where('event_competition_id', $competition->id)
            ->whereIn('event_participant_id', $targetEpIds)
            ->where('status', '!=', 'locked')
            ->update(['status' => 'submitted']);

        return response()->json([
            'message' => 'Nilai dikirim (submitted).',
            'applied_to' => count($targetEpIds),
        ]);
    }

    /**
     * LOCK
     * - TEAM: lock semua EP dalam tim
     */
    public function lock(Request $request, EventCompetition $competition)
    {
        $validated = $request->validate([
            'event_participant_id' => ['required','integer','exists:event_participants,id'],
        ]);

        $ep = EventParticipant::findOrFail($validated['event_participant_id']);

        if ((int) $ep->event_id !== (int) $competition->event_id) {
            return response()->json(['message' => 'Peserta bukan untuk event kompetisi ini.'], 422);
        }

        $targetEpIds = $this->resolveTargetEventParticipantIds($competition, $ep);

        EventScoresheet::query()
            ->where('event_competition_id', $competition->id)
            ->whereIn('event_participant_id', $targetEpIds)
            ->update(['status' => 'locked']);

        return response()->json([
            'message' => 'Nilai dikunci (locked).',
            'applied_to' => count($targetEpIds),
        ]);
    }

    /**
     * Judges resolver (tetap).
     */
    private function getJudgesForCompetition(EventCompetition $competition): array
    {
        $group = EventGroup::query()
            ->whereKey($competition->event_group_id)
            ->first(['id','event_id','branch_id','use_custom_judges']);

        if (!$group) return [];

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
