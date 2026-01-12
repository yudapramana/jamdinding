<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\EventCompetition;
use App\Models\EventFieldComponent;
use App\Models\EventScoresheet;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;




class EventCompetitionRankingV2Controller extends Controller
{

    protected function getIndividualFieldScores(EventCompetition $competition): array
    {
        return DB::table('event_score_items as esi')
            ->join('event_scoresheets as es', 'es.id', '=', 'esi.event_scoresheet_id')
            ->join('event_participants as ep', 'ep.id', '=', 'es.event_participant_id')
            ->where('es.event_competition_id', $competition->id)
            ->where('es.status', 'submitted')
            ->select([
                'ep.id as event_participant_id',
                'esi.event_field_component_id',
                DB::raw('AVG(esi.score) as avg_score'),
            ])
            ->groupBy(
                'ep.id',
                'esi.event_field_component_id'
            )
            ->get()
            ->groupBy('event_participant_id')
            ->map(fn ($rows) =>
                $rows->keyBy('event_field_component_id')
                    ->map(fn ($r) => (float) $r->avg_score)
                    ->toArray()
            )
            ->toArray();
    }

    protected function getTeamFieldScores(EventCompetition $competition): array
    {
        return DB::table('event_score_items as esi')
            ->join('event_scoresheets as es', 'es.id', '=', 'esi.event_scoresheet_id')
            ->join('event_participants as ep', 'ep.id', '=', 'es.event_participant_id')
            ->join('event_teams as et', 'et.id', '=', 'ep.event_team_id')
            ->where('es.event_competition_id', $competition->id)
            ->where('es.status', 'submitted')
            ->select([
                'et.id as event_team_id',
                'esi.event_field_component_id',
                DB::raw('AVG(esi.score) as avg_score'),
            ])
            ->groupBy(
                'et.id',
                'esi.event_field_component_id'
            )
            ->get()
            ->groupBy('event_team_id')
            ->map(fn ($rows) =>
                $rows->keyBy('event_field_component_id')
                    ->map(fn ($r) => (float) $r->avg_score)
                    ->toArray()
            )
            ->toArray();
    }

    protected function sortWithTieBreak(
        array $rows,
        array $fieldOrder,
        array $fieldScores,
        callable $nameGetter,
        callable $idGetter,
        callable $extraCompare = null
    ): array {
        usort($rows, function ($a, $b) use (
            $fieldOrder,
            $fieldScores,
            $nameGetter,
            $idGetter,
            $extraCompare
        ) {
            // 1. Final score
            if ($a['final_score'] !== $b['final_score']) {
                return $b['final_score'] <=> $a['final_score'];
            }

            $idA = $idGetter($a);
            $idB = $idGetter($b);

            // 2. Tie-break per bidang
            foreach ($fieldOrder as $fid) {
                $va = $fieldScores[$idA][$fid] ?? 0;
                $vb = $fieldScores[$idB][$fid] ?? 0;

                if ($va !== $vb) {
                    return $vb <=> $va;
                }
            }

            // 3. Extra compare (optional)
            if ($extraCompare) {
                $r = $extraCompare($a, $b);
                if ($r !== 0) return $r;
            }

            // 4. Nama (deterministik)
            return strcmp(
                mb_strtolower($nameGetter($a)),
                mb_strtolower($nameGetter($b))
            );
        });

        return $rows;
    }



    /**
     * ============================
     * RANKING V2
     * ============================
     */
    public function index(EventCompetition $eventCompetition)
    {
        $eventCompetition->load([
            'eventGroup:id,full_name,is_team,judge_assignment_mode',
            'round:id,name',
        ]);

        $group  = $eventCompetition->eventGroup;
        $isTeam = (bool) $group->is_team;

        $ranking = $isTeam
            ? $this->buildTeamRanking($eventCompetition)
            : $this->buildIndividualRanking($eventCompetition);

        return response()->json([
            'competition' => [
                'id'        => $eventCompetition->id,
                'full_name' => $eventCompetition->full_name,
                'round'     => $eventCompetition->round,
                'event_group' => [
                    'id'                     => $group->id,
                    'full_name'              => $group->full_name,
                    'is_team'                => $group->is_team,
                    'judge_assignment_mode'  => $group->judge_assignment_mode,
                ],
            ],
            'ranking' => $ranking,
        ]);
    }

    /**
     * ============================
     * INDIVIDUAL RANKING
     * ============================
     */
    protected function buildIndividualRanking(EventCompetition $competition): array
    {
        /**
         * STEP 1
         * Ambil rata-rata nilai per FIELD per PESERTA
         */
        $fieldAverages = DB::table('event_score_items as esi')
            ->join('event_scoresheets as es', 'es.id', '=', 'esi.event_scoresheet_id')
            ->join('event_participants as ep', 'ep.id', '=', 'es.event_participant_id')
            ->join('participants as p', 'p.id', '=', 'ep.participant_id')
            ->join('event_categories as ec', 'ec.id', '=', 'ep.event_category_id')
            ->select([
                'ep.id as event_participant_id',
                'p.full_name',
                'ep.contingent',
                'ep.event_category_id',
                'ec.full_name as category_name',
                'esi.event_field_component_id',

                DB::raw('AVG(esi.score) as avg_field_score'),
            ])
            ->where('es.event_competition_id', $competition->id)
            ->where('es.status', 'submitted')
            ->groupBy(
                'ep.id',
                'p.full_name',
                'ep.contingent',
                'ep.event_category_id',
                'ec.full_name',
                'esi.event_field_component_id'
            )
            ->get();

        /**
         * STEP 2
         * Group by peserta → sum avg_field_score
         */
        $byParticipant = $fieldAverages->groupBy('event_participant_id');

        $rows = $byParticipant->map(function ($items) {
            $first = $items->first();

            $finalScore = $items->sum('avg_field_score');

            return [
                'event_participant_id' => (int) $first->event_participant_id,
                'full_name'            => $first->full_name,
                'contingent'           => $first->contingent,

                'event_category_id'    => (int) $first->event_category_id,
                'event_category'       => [
                    'id' => (int) $first->event_category_id,
                    'full_name' => $first->category_name,
                ],

                'final_score'          => round($finalScore, 2),
            ];
        })->values();

        /**
         * STEP 3
         * Ranking per kategori (Putra / Putri)
         */
        $rows = $rows->groupBy('event_category_id');

        $fieldOrder  = $this->getFieldOrder($competition);
        $fieldScores = $this->getIndividualFieldScores($competition);

        $finalRows = collect();

        foreach ($rows as $categoryId => $catRows) {
            // 🔑 tie-break hanya dalam 1 kategori
            $sorted = $this->sortWithTieBreak(
                $catRows->values()->all(),
                $fieldOrder,
                $fieldScores,
                fn ($r) => $r['full_name'],
                fn ($r) => $r['event_participant_id']
            );

            // 🔢 ranking
            $rank = 1;
            foreach ($sorted as &$r) {
                $r['rank'] = $rank++;
                $finalRows->push($r);
            }
        }

        return $finalRows->values()->all();


    }



    /**
     * ============================
     * TEAM / REGU RANKING
     * ============================
     */
    protected function buildTeamRanking(EventCompetition $competition): array
    {
        /* =====================================================
        * 1. Ambil rata-rata nilai per BIDANG per TIM
        * ===================================================== */
        $fieldAverages = DB::table('event_score_items as esi')
            ->join('event_scoresheets as es', 'es.id', '=', 'esi.event_scoresheet_id')
            ->join('event_participants as ep', 'ep.id', '=', 'es.event_participant_id')
            ->join('event_teams as et', 'et.id', '=', 'ep.event_team_id')
            ->join('event_categories as ec', 'ec.id', '=', 'ep.event_category_id')
            ->where('es.event_competition_id', $competition->id)
            ->where('es.status', 'submitted')
            ->select([
                'et.id as event_team_id',
                'et.contingent',

                'ep.event_category_id',
                'ec.full_name as category_name',

                'esi.event_field_component_id',
                DB::raw('AVG(esi.score) as avg_field_score'),
            ])
            ->groupBy(
                'et.id',
                'et.contingent',
                'ep.event_category_id',
                'ec.full_name',
                'esi.event_field_component_id'
            )
            ->get();

        /* =====================================================
        * 2. Kelompokkan per TIM
        * ===================================================== */
        $byTeam = $fieldAverages->groupBy('event_team_id');

        /* =====================================================
        * 3. Ambil anggota tim
        * ===================================================== */
        $teamMembers = DB::table('event_participants as ep')
            ->join('participants as p', 'p.id', '=', 'ep.participant_id')
            ->where('ep.event_id', $competition->event_id)
            ->whereNotNull('ep.event_team_id')
            ->select([
                'ep.event_team_id',
                'ep.id as event_participant_id',
                'p.full_name',
            ])
            ->get()
            ->groupBy('event_team_id');

        /* =====================================================
        * 4. Build rows
        * ===================================================== */
        $rows = $byTeam->map(function ($items) use ($teamMembers) {
            $first = $items->first();
            $members = $teamMembers[$first->event_team_id] ?? collect();

            return [
                'group_key' => 'team-' . $first->event_team_id,

                'event_team_id' => (int) $first->event_team_id,
                'contingent'    => $first->contingent,

                'event_category_id' => (int) $first->event_category_id,
                'event_category' => [
                    'id'        => (int) $first->event_category_id,
                    'full_name'=> $first->category_name,
                ],

                // 🔑 ANGGOTA TIM
                'members' => $members->map(fn ($m) => [
                    'event_participant_id' => (int) $m->event_participant_id,
                    'full_name'            => $m->full_name,
                ])->values(),

                'member_count' => $members->count(),

                // 🔑 FINAL SCORE = SUM AVG BIDANG
                'final_score' => round($items->sum('avg_field_score'), 2),
            ];
        })->values();

        /* =====================================================
        * 5. Ranking per kategori (TIM / REGU) + TIE BREAK
        * ===================================================== */

        $fieldOrder  = $this->getFieldOrder($competition);
        $fieldScores = $this->getTeamFieldScores($competition);

        $finalRows = collect();

        $rows
            ->groupBy('event_category_id')
            ->each(function ($categoryRows) use (
                &$finalRows,
                $fieldOrder,
                $fieldScores
            ) {
                // 🔑 Tie-break hanya dalam kategori yang sama
                $sorted = $this->sortWithTieBreak(
                    $categoryRows->values()->all(),
                    $fieldOrder,
                    $fieldScores,
                    fn ($r) => $r['contingent'],      // fallback nama
                    fn ($r) => $r['event_team_id']    // key unik tim
                );

                // 🔢 Penentuan rank
                $rank = 1;
                foreach ($sorted as &$row) {
                    $row['rank'] = $rank++;
                    $finalRows->push($row);
                }
            });

        return $finalRows->values()->all();

    }






    /**
     * ============================
     * DETAIL ENTRY POINT
     * ============================
     */
    public function details(Request $request, EventCompetition $competition)
    {
        $categoryId = $request->integer('event_category_id');
        abort_if(!$categoryId, 422, 'event_category_id wajib');


        $competition->load('eventGroup');




        if ($competition->eventGroup->is_team) {
            return response()->json(
                $this->buildTeamDetails($competition, $categoryId)
            );
        }

        return response()->json(
            $this->buildIndividualDetails($competition, $categoryId)
        );
    }

    protected function getFieldOrder(EventCompetition $competition): array
    {
        return EventFieldComponent::query()
            ->where('event_group_id', $competition->event_group_id)
            ->orderByRaw('COALESCE(order_number, 999999) asc')
            ->pluck('id')
            ->values()
            ->all();
    }

    protected function getPanelJudges(EventCompetition $competition)
    {
        $panelId = $competition->eventGroup->event_judge_panel_id;

        if (!$panelId) {
            return collect();
        }

        return DB::table('event_judge_panel_members as m')
            ->join('event_judges as ej', 'ej.id', '=', 'm.event_judge_id')
            ->join('master_judges as mj', 'mj.id', '=', 'ej.master_judge_id')
            ->where('m.event_judge_panel_id', $panelId)
            ->orderByRaw('COALESCE(m.order_number, 999999)')
            ->get([
                'ej.id',
                'mj.full_name as name',
            ])
            ->map(fn ($row) => [
                'id'   => (int) $row->id,
                'name' => $row->name,
            ]);

    }

    protected function getJudgesByField(EventCompetition $competition): array
    {
        $group = $competition->eventGroup;

        // =========================
        // OPSI A: BY_PANEL
        // =========================
        if ($group->judge_assignment_mode === 'BY_PANEL') {
            $panelJudges = DB::table('event_judge_panel_members')
                ->where('event_judge_panel_id', $group->event_judge_panel_id)
                ->pluck('event_judge_id')
                ->all();

            $fields = EventFieldComponent::where('event_group_id', $group->id)
                ->pluck('id')
                ->all();

            return collect($fields)
                ->mapWithKeys(fn ($fid) => [$fid => $panelJudges])
                ->toArray();
        }

        // =========================
        // OPSI B: BY_COMPONENT
        // =========================
        return DB::table('event_field_component_judges')
            ->whereIn(
                'event_field_component_id',
                EventFieldComponent::where('event_group_id', $group->id)->pluck('id')
            )
            ->get()
            ->groupBy('event_field_component_id')
            ->map(fn ($r) => $r->pluck('event_judge_id')->unique()->values()->all())
            ->toArray();
    }

    protected function buildIndividualDetails(EventCompetition $competition, int $categoryId): array
    {
        $group = $competition->eventGroup;

        /* ===============================
        * FIELD ORDER
        * =============================== */
        $fieldIds = EventFieldComponent::query()
            ->where('event_group_id', $group->id)
            ->orderByRaw('COALESCE(order_number, 999999) asc')
            ->pluck('id')
            ->values()
            ->all();



        $fields = EventFieldComponent::whereIn('id', $fieldIds)
            ->orderByRaw('FIELD(id,' . implode(',', $fieldIds) . ')')
            ->get(['id', 'field_name as name']);



        /* ===============================
        * JUDGES HEADER (MAJELIS)
        * =============================== */
        $judges = $this->getPanelJudges($competition);


        /* ===============================
        * JUDGES SAH PER FIELD
        * =============================== */
        $fieldJudges = $this->getJudgesByField($competition);

        /* ===============================
        * ANCHOR QUERY: SCORESHEETS
        * =============================== */
        $rowsRaw = DB::table('event_scoresheets as es')
            ->join('event_participants as ep', 'ep.id', '=', 'es.event_participant_id')
            ->join('participants as p', 'p.id', '=', 'ep.participant_id')
            ->leftJoin('event_score_items as esi', 'esi.event_scoresheet_id', '=', 'es.id')
            ->where('es.event_competition_id', $competition->id)
            ->where('es.event_category_id', $categoryId)
            ->where('es.status', 'submitted')
            ->select([
                'ep.id as event_participant_id',
                'p.full_name',
                'ep.contingent',
                'es.event_judge_id',
                'esi.event_field_component_id',
                'esi.score',
            ])
            ->get();


        /* ===============================
        * GROUP RAW
        * =============================== */
        $rows = [];

        foreach ($rowsRaw as $r) {
            $pid = $r->event_participant_id;

            $rows[$pid]['event_participant_id'] = (int) $pid;
            $rows[$pid]['full_name'] = $r->full_name;
            $rows[$pid]['contingent'] = $r->contingent;

            if ($r->event_field_component_id) {
                $rows[$pid]['scores']
                    [$r->event_field_component_id]
                    [$r->event_judge_id][] = (float) $r->score;
            }
        }

        /* ===============================
        * HITUNG AVG PER FIELD
        * =============================== */
        foreach ($rows as &$row) {
            $final = 0;

            foreach ($fields as $f) {
                $vals = [];

                foreach ($fieldJudges[$f->id] ?? [] as $jid) {
                    if (!empty($row['scores'][$f->id][$jid])) {
                        $vals = array_merge($vals, $row['scores'][$f->id][$jid]);
                    }
                }

                $avg = count($vals) ? array_sum($vals) / count($vals) : 0;
                $row['field_avg'][$f->id] = round($avg, 2);
                $final += $avg;
            }

            $row['final_score'] = round($final, 2);
        }

        $rows = $this->sortRowsWithTieBreak(
            array_values($rows),
            $fields
        );

        return [
            'fields' => $fields,
            'judges' => $judges,
            'rows'   => $rows,
        ];
    }


    protected function buildTeamDetails(EventCompetition $competition, int $categoryId): array
    {
        $group = $competition->eventGroup;

        $fieldIds = EventFieldComponent::query()
            ->where('event_group_id', $group->id)
            ->orderByRaw('COALESCE(order_number, 999999) asc')
            ->pluck('id')
            ->values()
            ->all();

        $fields = EventFieldComponent::whereIn('id', $fieldIds)
            ->orderByRaw('FIELD(id,' . implode(',', $fieldIds) . ')')
            ->get(['id', 'field_name as name']);

        $judges = $this->getPanelJudges($competition);
        $fieldJudges = $this->getJudgesByField($competition);

        $rowsRaw = DB::table('event_scoresheets as es')
            ->join('event_participants as ep', 'ep.id', '=', 'es.event_participant_id')
            ->join('event_teams as et', 'et.id', '=', 'ep.event_team_id')
            ->leftJoin('event_score_items as esi', 'esi.event_scoresheet_id', '=', 'es.id')
            ->where('es.event_competition_id', $competition->id)
            ->where('es.event_category_id', $categoryId)
            ->where('es.status', 'submitted')
            ->select([
                'et.id as event_team_id',
                'et.contingent',
                'es.event_judge_id',
                'esi.event_field_component_id',
                'esi.score',
            ])
            ->get();

        $rows = [];

        foreach ($rowsRaw as $r) {
            $tid = $r->event_team_id;

            $rows[$tid]['event_team_id'] = $tid;
            $rows[$tid]['contingent'] = $r->contingent;

            if ($r->event_field_component_id) {
                $rows[$tid]['scores']
                    [$r->event_field_component_id]
                    [$r->event_judge_id][] = (float) $r->score;
            }
        }

        foreach ($rows as &$row) {
            $final = 0;

            foreach ($fields as $f) {
                $vals = [];

                foreach ($fieldJudges[$f->id] ?? [] as $jid) {
                    if (!empty($row['scores'][$f->id][$jid])) {
                        $vals = array_merge($vals, $row['scores'][$f->id][$jid]);
                    }
                }

                $avg = count($vals) ? array_sum($vals) / count($vals) : 0;
                $row['field_avg'][$f->id] = round($avg, 2);
                $final += $avg;
            }

            $row['final_score'] = round($final, 2);
        }

        $rows = $this->sortRowsWithTieBreak(
            array_values($rows),
            $fields
        );

        return [
            'fields' => $fields,
            'judges' => $judges,
            'rows'   => $rows,
        ];


    }



    protected function sortRowsWithTieBreak(array $rows, \Illuminate\Support\Collection $fields): array
    {
        $fieldOrder = $fields->pluck('id')->values()->all();

        usort($rows, function ($a, $b) use ($fieldOrder) {
            // 1️⃣ Final score
            if ($a['final_score'] !== $b['final_score']) {
                return $b['final_score'] <=> $a['final_score'];
            }

            // 2️⃣ Tie-break per field (urut sesuai order field)
            foreach ($fieldOrder as $fid) {
                $aAvg = $a['field_avg'][$fid] ?? 0;
                $bAvg = $b['field_avg'][$fid] ?? 0;

                if ($aAvg !== $bAvg) {
                    return $bAvg <=> $aAvg;
                }
            }

            // 3️⃣ Stabilizer (nama / kontingen)
            $aName = $a['full_name'] ?? $a['contingent'] ?? '';
            $bName = $b['full_name'] ?? $b['contingent'] ?? '';

            return strcasecmp($aName, $bName);
        });

        return $rows;
    }













}
