<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EventCompetition;
use App\Models\EventScoresheet;
use App\Models\EventParticipant;
use App\Models\MedalStanding;
use App\Models\EventContingent;
use App\Models\EventFieldComponent;
use App\Models\EventContingentMedal;
use App\Models\MedalRule;
use App\Models\EventMedalRule;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\EventSnapshot;
use Carbon\Carbon;

class __EventCompetitionRankingController extends Controller
{
    /**
     * Detect mode team/group:
     * priority: event_competitions.is_team, fallback: event_groups.is_team
     */
    private function isTeamMode(EventCompetition $competition): bool
    {
        // pastikan eventGroup ter-load jika perlu
        if (!$competition->relationLoaded('eventGroup')) {
            $competition->load(['eventGroup:id,event_id,branch_id,is_team,full_name']);
        }

        if (!is_null($competition->eventGroup->is_team)) return (bool) $competition->is_team;
        return (bool) optional($competition->eventGroup)->is_team;
    }

    /**
     * Cari hakim ketua (is_chief) sesuai schema:
     * - event_group_judges (prioritas)
     * - fallback event_branch_judges
     * - fallback terakhir: judge terkecil yang memang ada scoresheet di competition
     */
    private function getChiefJudgeId(EventCompetition $competition, ?string &$source = null): ?int
    {
        $source = null;

        // 1) Chief di GROUP
        $id = DB::table('event_group_judges')
            ->where('event_group_id', $competition->event_group_id)
            ->where('is_chief', 1)
            ->orderBy('id')
            ->value('user_id');

        if ($id) {
            $source = 'event_group_judges';
            return (int) $id;
        }

        // 2) Chief di BRANCH
        $branchId = DB::table('event_groups')
            ->where('id', $competition->event_group_id)
            ->value('branch_id');

        if ($branchId) {
            $eventBranchId = DB::table('event_branches')
                ->where('event_id', $competition->event_id)
                ->where('branch_id', $branchId)
                ->value('id');

            if ($eventBranchId) {
                $id2 = DB::table('event_branch_judges')
                    ->where('event_branch_id', $eventBranchId)
                    ->where('is_chief', 1)
                    ->orderBy('id')
                    ->value('user_id');

                if ($id2) {
                    $source = 'event_branch_judges';
                    return (int) $id2;
                }
            }
        }

        // 3) Fallback: pilih judge yang benar-benar punya scoresheet di competition
        $id3 = DB::table('event_scoresheets')
            ->where('event_competition_id', $competition->id)
            ->orderBy('judge_id')
            ->value('judge_id');

        if ($id3) {
            $source = 'fallback_scoresheet_judge';
            return (int) $id3;
        }

        $source = 'none';
        return null;
    }

    private function roundCmp($v, int $decimals)
    {
        if ($v === null) return null;
        $n = (float) $v;
        return round($n, $decimals);
    }

    /**
     * Comparator ranking:
     * 1) final_score desc
     * 2) avg bidang (field_avg) desc, urutan fieldOrder
     * 3) nilai hakim ketua per bidang (chief_field) desc, urutan fieldOrder (SKIP jika salah satu null)
     * 4) fallback nama ASC (stabil)
     */
    private function compareParticipants(array $a, array $b, array $fieldOrder, int $cmpDecimals = 2): array
    {
        $aFinal = $this->roundCmp($a['final_score'] ?? $a['avg_score'] ?? 0, $cmpDecimals);
        $bFinal = $this->roundCmp($b['final_score'] ?? $b['avg_score'] ?? 0, $cmpDecimals);

        if ($aFinal !== $bFinal) {
            return [($bFinal <=> $aFinal), [
                'rule' => 1,
                'a' => $aFinal,
                'b' => $bFinal,
            ]];
        }

        // #2 avg bidang
        foreach ($fieldOrder as $fcId) {
            $av = $this->roundCmp($a['field_avg'][(string)$fcId] ?? $a['field_avg'][$fcId] ?? 0, $cmpDecimals);
            $bv = $this->roundCmp($b['field_avg'][(string)$fcId] ?? $b['field_avg'][$fcId] ?? 0, $cmpDecimals);
            if ($av === $bv) continue;

            return [($bv <=> $av), [
                'rule' => 2,
                'field_component_id' => (int)$fcId,
                'a' => $av,
                'b' => $bv,
            ]];
        }

        // #3 nilai ketua per bidang (SKIP NULL)
        foreach ($fieldOrder as $fcId) {
            $acRaw = $a['chief_field'][(string)$fcId] ?? $a['chief_field'][$fcId] ?? null;
            $bcRaw = $b['chief_field'][(string)$fcId] ?? $b['chief_field'][$fcId] ?? null;

            if ($acRaw === null || $bcRaw === null) continue;

            $ac = $this->roundCmp($acRaw, $cmpDecimals);
            $bc = $this->roundCmp($bcRaw, $cmpDecimals);

            if ($ac === $bc) continue;

            return [($bc <=> $ac), [
                'rule' => 3,
                'field_component_id' => (int)$fcId,
                'a' => $ac,
                'b' => $bc,
            ]];
        }

        // #4 fallback nama asc
        $cmp = strcasecmp($a['full_name'] ?? '', $b['full_name'] ?? '');
        return [$cmp, ['rule' => 4]];
    }

    // =========================================================
    // ENDPOINTS
    // =========================================================

    /**
     * RANKING SUMMARY
     */
    public function index(Request $request, EventCompetition $competition)
    {
        $competition->load([
            'round:id,name',
            'eventGroup:id,event_id,branch_id,is_team,full_name',
        ]);

        $isTeam = $this->isTeamMode($competition);

        $ranking = $this->buildRankingFromScoresheets($competition, $request->boolean('debug'));

        return response()->json([
            'competition' => [
                'id' => $competition->id,
                'full_name' => $competition->full_name,
                'event_id' => $competition->event_id,
                'event_group_id' => $competition->event_group_id,
                'round_id' => $competition->round_id,
                'is_team' => $isTeam ? 1 : 0,
                'round' => [
                    'id' => $competition->round_id,
                    'name' => $competition->round?->name,
                ],
            ],
            'ranking' => $ranking['ranking'],
            'debug' => $ranking['debug'] ?? null,
        ]);
    }

    /**
     * RANKING DETAILS (matrix nilai per field & per hakim) + ranking (dengan tiebreak sama)
     * Params:
     * - event_category_id (opsional)
     * - status (draft/submitted/locked) opsional
     * - search (opsional)
     * - debug=1 (opsional) -> kirim debug comparator
     */
    public function details(Request $request, EventCompetition $competition)
    {
        $competition->load(['eventGroup:id,event_id,branch_id,is_team,full_name']);

        $search      = $request->get('search');
        $status      = $request->get('status');
        $categoryId  = $request->get('event_category_id');
        $withDebug   = $request->boolean('debug', false);
        // kalau kamu mau paksa debug: $withDebug = true;
        $cmpDecimals = (int)($request->get('cmp_decimals', 2));

        $isTeam = $this->isTeamMode($competition);

        // 1) Fields (prioritas tie-break)
        $fieldComponents = EventFieldComponent::query()
            ->where('event_group_id', $competition->event_group_id)
            ->orderByRaw('COALESCE(order_number, 999999) asc')
            ->get(['id','field_id','field_name','order_number','max_score','weight']);

        $fieldOrder = $fieldComponents->pluck('id')->values()->all();

        $fields = $fieldComponents->map(fn($fc) => [
            'event_field_component_id' => $fc->id,
            'field_id' => $fc->field_id,
            'name' => $fc->field_name,
            'order_number' => $fc->order_number,
            'max_score' => $fc->max_score,
            'weight' => $fc->weight,
        ])->values();

        // 2) Chief Judge
        $chiefSource = null;
        $chiefJudgeId = $this->getChiefJudgeId($competition, $chiefSource);

        // 3) Query scoresheets (with items)
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

        if ($categoryId !== null && $categoryId !== '' && $categoryId !== 'all') {
            $q->where('event_category_id', $categoryId);
        }

        if ($search) {
            // NOTE: untuk team/group, search ini tetap cari nama anggota (participant.full_name)
            $q->whereHas('eventParticipant.participant', function ($qq) use ($search) {
                $qq->where('full_name', 'like', "%{$search}%");
            });
        }

        $rows = $q->get();

        // 4) Build matrix
        if (!$isTeam) {
            // ===== INDIVIDU (seperti sebelumnya) =====
            $participants = [];
            $judgesMap = [];

            foreach ($rows as $ss) {
                $ep = $ss->eventParticipant;
                if (!$ep || !$ep->participant) continue;

                $pid     = (int) $ep->id;
                $judgeId = (int) $ss->judge_id;

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

                        'event_category_id' => $ep->event_category_id,
                        'category_name'     => $ep->eventCategory?->full_name,
                        'category'          => $ep->eventCategory?->full_name,

                        'scores' => [],
                        'status_by_judge' => [],
                        'scoresheet_id_by_judge' => [],
                        'field_scores' => [],

                        'field_avg' => [],
                        'chief_field' => [],
                    ];

                    foreach ($fieldOrder as $fcId) {
                        $participants[$pid]['field_avg'][(string)$fcId] = 0.0;
                        $participants[$pid]['chief_field'][(string)$fcId] = null;
                    }
                }

                $participants[$pid]['scores'][(string)$judgeId] = (float) $ss->total_score;
                $participants[$pid]['status_by_judge'][(string)$judgeId] = $ss->status;
                $participants[$pid]['scoresheet_id_by_judge'][(string)$judgeId] = $ss->id;

                if (!isset($participants[$pid]['field_scores'][(string)$judgeId])) {
                    $participants[$pid]['field_scores'][(string)$judgeId] = [];
                    foreach ($fieldOrder as $fcId) {
                        $participants[$pid]['field_scores'][(string)$judgeId][(string)$fcId] = null;
                    }
                }

                foreach (($ss->items ?? []) as $item) {
                    $fcId = (int) $item->event_field_component_id;
                    if (!$fcId) continue;

                    $participants[$pid]['field_scores'][(string)$judgeId][(string)$fcId] = [
                        'score' => (float) $item->score,
                        'weighted_score' => (float) $item->weighted_score,
                        'max_score' => (float) $item->max_score,
                        'weight' => $item->weight,
                    ];
                }
            }

            $list = array_values($participants);

            // derived for sorting
            foreach ($list as &$p) {
                $vals = array_values($p['scores'] ?? []);
                $p['judge_count'] = count($vals);
                $p['sum_score']   = round(array_sum($vals), 2);
                $p['avg_score']   = $p['judge_count'] > 0 ? round($p['sum_score'] / $p['judge_count'], 2) : 0;
                $p['final_score'] = $p['avg_score'];

                foreach ($fieldOrder as $fcId) {
                    $sum = 0.0;
                    $cnt = 0;

                    foreach (($p['field_scores'] ?? []) as $jId => $byField) {
                        $cell = $byField[(string)$fcId] ?? null;
                        if (!is_array($cell)) continue;

                        $val = $cell['weighted_score'] ?? $cell['score'] ?? null;
                        if ($val === null) continue;

                        $sum += (float)$val;
                        $cnt++;
                    }

                    $p['field_avg'][(string)$fcId] = $cnt > 0 ? ($sum / $cnt) : 0.0;
                }

                foreach ($fieldOrder as $fcId) {
                    $p['chief_field'][(string)$fcId] = null;
                }

                if ($chiefJudgeId) {
                    $chiefRow = $p['field_scores'][(string)$chiefJudgeId] ?? null;
                    if (is_array($chiefRow)) {
                        foreach ($fieldOrder as $fcId) {
                            $cell = $chiefRow[(string)$fcId] ?? null;
                            if (!is_array($cell)) continue;

                            $val = $cell['weighted_score'] ?? $cell['score'] ?? null;
                            if ($val === null) continue;

                            $p['chief_field'][(string)$fcId] = (float)$val;
                        }
                    }
                }
            }
            unset($p);

            $pairs = [];
            usort($list, function ($a, $b) use ($fieldOrder, $cmpDecimals, &$pairs) {
                [$cmp, $decidedBy] = $this->compareParticipants($a, $b, $fieldOrder, $cmpDecimals);
                $pairs[] = [
                    'a' => ['id' => $a['event_participant_id'], 'name' => $a['full_name']],
                    'b' => ['id' => $b['event_participant_id'], 'name' => $b['full_name']],
                    'decided_by' => $decidedBy,
                ];
                return $cmp;
            });

            foreach ($list as $i => &$p) {
                $p['rank'] = $i + 1;
            }
            unset($p);

            return response()->json([
                'competition' => [
                    'id' => $competition->id,
                    'full_name' => $competition->full_name,
                    'status' => $competition->status,
                    'scheduled_at' => $competition->scheduled_at,
                    'venue' => $competition->venue,
                    'is_team' => 0,
                ],
                'fields' => $fields,
                'judges' => array_values($judgesMap),
                'participants' => $list,
                'debug' => $withDebug ? [
                    'cmp_decimals' => $cmpDecimals,
                    'chief_judge_id' => $chiefJudgeId,
                    'chief_source' => $chiefSource,
                    'field_order' => $fieldOrder,
                    'pairs' => $pairs,
                ] : null,
            ]);
        }

        // ===== TEAM / GROUP MODE (WITH scores_by_field) =====

        $groups = [];   // key => aggregated row

        $makeKey = function ($contingent, $cid) {
            $c = trim((string)($contingent ?? ''));
            if ($c === '') $c = '-';
            $cat = ($cid === null || $cid === '' || $cid === 'null') ? 'null' : (string)$cid;
            return $c . '||' . $cat;
        };

        foreach ($rows as $ss) {
            $ep = $ss->eventParticipant;
            if (!$ep || !$ep->participant) continue;

            $judgeId = (int) $ss->judge_id;
            $jid = (string)$judgeId;

            $judgesMap[$judgeId] = [
                'id' => $judgeId,
                'name' => $ss->judge?->name ?? 'Hakim',
                'username' => $ss->judge?->username,
            ];

            $key = $makeKey($ep->contingent, $ep->event_category_id);

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    // identity
                    'group_key' => $key,
                    'contingent' => trim((string)$ep->contingent) ?: '-',
                    'event_category_id' => $ep->event_category_id,
                    'category_name' => $ep->eventCategory?->full_name,
                    'category' => $ep->eventCategory?->full_name,

                    // display
                    'full_name' => strtoupper(trim((string)$ep->contingent) ?: '-'),
                    'member_names' => [],
                    'member_count' => 0,

                    // per judge totals
                    'judge_totals' => [],
                    'judge_counts' => [],
                    'scores' => [],

                    // NEW: per judge per field
                    'scores_by_field' => [],
                    'scores_by_field_cnt' => [],

                    // per field global
                    'field_totals' => [],
                    'field_counts' => [],

                    // chief
                    'chief_totals' => [],
                    'chief_counts' => [],
                    'chief_field' => [],

                    // derived
                    'field_avg' => [],
                    'final_score' => 0.0,
                    'avg_score' => 0.0,
                    'sum_score' => 0.0,
                ];

                foreach ($fieldOrder as $fcId) {
                    $fid = (string)$fcId;
                    $groups[$key]['field_totals'][$fid] = 0.0;
                    $groups[$key]['field_counts'][$fid] = 0;
                    $groups[$key]['chief_totals'][$fid] = 0.0;
                    $groups[$key]['chief_counts'][$fid] = 0;
                    $groups[$key]['chief_field'][$fid] = null;
                    $groups[$key]['field_avg'][$fid] = 0.0;
                }
            }

            // members
            $groups[$key]['member_names'][] = $ep->participant->full_name;
            $groups[$key]['member_count']++;

            // judge totals
            if (!isset($groups[$key]['judge_totals'][$jid])) {
                $groups[$key]['judge_totals'][$jid] = 0.0;
                $groups[$key]['judge_counts'][$jid] = 0;
            }
            $groups[$key]['judge_totals'][$jid] += (float)$ss->total_score;
            $groups[$key]['judge_counts'][$jid] += 1;

            // ensure scores_by_field slot
            if (!isset($groups[$key]['scores_by_field'][$jid])) {
                $groups[$key]['scores_by_field'][$jid] = [];
                $groups[$key]['scores_by_field_cnt'][$jid] = [];
            }

            // items → field aggregation
            foreach (($ss->items ?? []) as $it) {
                $fcId = (int)$it->event_field_component_id;
                if (!$fcId) continue;

                $fid = (string)$fcId;
                $val = $it->weighted_score ?? $it->score;
                if ($val === null) continue;

                // global field
                $groups[$key]['field_totals'][$fid] += (float)$val;
                $groups[$key]['field_counts'][$fid] += 1;

                // per judge per field
                if (!isset($groups[$key]['scores_by_field'][$jid][$fid])) {
                    $groups[$key]['scores_by_field'][$jid][$fid] = 0.0;
                    $groups[$key]['scores_by_field_cnt'][$jid][$fid] = 0;
                }

                $groups[$key]['scores_by_field'][$jid][$fid] += (float)$val;
                $groups[$key]['scores_by_field_cnt'][$jid][$fid] += 1;

                // chief
                if ($chiefJudgeId && $judgeId === (int)$chiefJudgeId) {
                    $groups[$key]['chief_totals'][$fid] += (float)$val;
                    $groups[$key]['chief_counts'][$fid] += 1;
                }
            }
        }

        $list = array_values($groups);

        // ===== FINALIZE =====
        foreach ($list as &$g) {
            // normalize members
            $names = array_values(array_unique(array_filter($g['member_names'])));
            sort($names, SORT_NATURAL | SORT_FLAG_CASE);
            $g['member_names'] = $names;
            $g['member_count'] = count($names);

            // scores per judge (avg total_score)
            $sumAcrossJudges = 0.0;
            $judgeCnt = 0;

            foreach (array_keys($judgesMap) as $jid) {
                $tot = (float)($g['judge_totals'][$jid] ?? 0);
                $cnt = (int)($g['judge_counts'][$jid] ?? 0);
                $avg = $cnt > 0 ? ($tot / $cnt) : 0.0;

                $g['scores'][$jid] = round($avg, 4);
                $sumAcrossJudges += $avg;
                $judgeCnt++;
            }

            $g['avg_score'] = $judgeCnt > 0 ? round($sumAcrossJudges / $judgeCnt, 2) : 0.0;
            $g['final_score'] = $g['avg_score'];
            $g['sum_score'] = round($sumAcrossJudges, 2);

            // field_avg
            foreach ($g['field_totals'] as $fid => $sum) {
                $cnt = (int)($g['field_counts'][$fid] ?? 0);
                $g['field_avg'][$fid] = $cnt > 0 ? round($sum / $cnt, 4) : 0.0;
            }

            // chief_field
            foreach ($g['chief_totals'] as $fid => $sum) {
                $cnt = (int)($g['chief_counts'][$fid] ?? 0);
                $g['chief_field'][$fid] = $cnt > 0 ? round($sum / $cnt, 4) : null;
            }

            // FINAL: avg per judge per field
            foreach ($g['scores_by_field'] as $jid => $byField) {
                foreach ($byField as $fid => $sum) {
                    $cnt = (int)($g['scores_by_field_cnt'][$jid][$fid] ?? 0);
                    $g['scores_by_field'][$jid][$fid] = $cnt > 0
                        ? round($sum / $cnt, 4)
                        : null;
                }
            }

            unset($g['scores_by_field_cnt']);
        }
        unset($g);

        // ===== SORT + RANK =====
        $pairs = [];
        usort($list, function ($a, $b) use ($fieldOrder, $cmpDecimals, &$pairs) {
            [$cmp, $decidedBy] = $this->compareParticipants($a, $b, $fieldOrder, $cmpDecimals);
            $pairs[] = [
                'a' => ['id' => $a['group_key'], 'name' => $a['contingent']],
                'b' => ['id' => $b['group_key'], 'name' => $b['contingent']],
                'decided_by' => $decidedBy,
            ];
            return $cmp;
        });

        foreach ($list as $i => &$g) {
            $g['rank'] = $i + 1;
        }
        unset($g);

        return response()->json([
            'competition' => [
                'id' => $competition->id,
                'full_name' => $competition->full_name,
                'status' => $competition->status,
                'scheduled_at' => $competition->scheduled_at,
                'venue' => $competition->venue,
                'is_team' => 1,
            ],
            'fields' => $fields,
            'judges' => array_values($judgesMap),
            'participants' => $list,
            'debug' => $withDebug ? [
                'cmp_decimals' => $cmpDecimals,
                'chief_judge_id' => $chiefJudgeId,
                'chief_source' => $chiefSource,
                'field_order' => $fieldOrder,
                'pairs' => $pairs,
            ] : null,
        ]);

    }

    public function recalculate(Request $request, EventCompetition $competition)
    {
        $competition->load(['eventGroup:id,event_id,branch_id,is_team,full_name']);

        $res = $this->buildRankingFromScoresheets($competition, $request->boolean('debug'));

        return response()->json([
            'message' => 'Recalculated',
            'ranking' => $res['ranking'],
            'debug' => $res['debug'] ?? null,
        ]);
    }

    public function export(Request $request, EventCompetition $competition)
    {
        $competition->load(['eventGroup:id,event_id,branch_id,is_team,full_name']);

        $categoryId = $request->get('event_category_id');

        $res = $this->buildRankingFromScoresheets($competition, false);

        $ranking = collect($res['ranking'])
            ->when($categoryId && $categoryId !== 'all', fn($q) => $q->where('event_category_id', $categoryId))
            ->values();

        return response()->json([
            'competition' => $competition->full_name,
            'category_id' => $categoryId,
            'is_team' => $this->isTeamMode($competition) ? 1 : 0,
            'ranking' => $ranking,
        ]);
    }

    public function publish(Request $request, EventCompetition $competition)
    {
        $competition->load([
            'round:id,name',
            'eventGroup:id,event_id,branch_id,is_team,full_name',
            'event',
        ]);

        // 1️⃣ Validasi hanya babak FINAL
        $roundName = strtolower(trim($competition->round?->name ?? ''));
        if (!str_contains($roundName, 'final')) {
            return response()->json([
                'message' => 'Publish hanya diperbolehkan untuk babak final.',
            ], 422);
        }

        $data = $request->validate([
            'top_n' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $topN = (int) ($data['top_n'] ?? 6);

        // 2️⃣ Mapping event_level → region_type
        $regionTypeMap = [
            'national' => EventContingent::REGION_PROVINCE,
            'province' => EventContingent::REGION_REGENCY,
            'regency'  => EventContingent::REGION_DISTRICT,
            'district' => EventContingent::REGION_VILLAGE,
        ];

        $regionType = $regionTypeMap[$competition->event->event_level] ?? null;
        if (!$regionType) {
            return response()->json([
                'message' => 'Event level tidak valid untuk publish.',
            ], 422);
        }

        // 3️⃣ Ambil ranking dari scoresheet
        $res = $this->buildRankingFromScoresheets($competition, false);
        $rankingByCategory = collect($res['ranking'])->groupBy('event_category_id');

        EventSnapshot::updateOrCreate(
            [
                'event_id' => $competition->event_id,
                'type'     => 'ranking',
            ],
            [
                'payload' => [
                    'competition_id' => $competition->id,
                    'event_group_id' => $competition->event_group_id,
                    'is_team'        => $this->isTeamMode($competition),
                    'ranking'        => $res['ranking'],
                ],
                'published_at' => now(),
            ]
        );


        // 4️⃣ Ambil aturan medali
        $eventMedalRules = EventMedalRule::where('event_id', $competition->event_id)
            ->where('is_active', true)
            ->orderBy('order_number')
            ->get()
            ->keyBy('order_number');

        $defaultMedalRules = MedalRule::where('is_active', true)
            ->orderBy('order_number')
            ->get()
            ->keyBy('order_number');

        DB::transaction(function () use (
            $competition,
            $rankingByCategory,
            $topN,
            $eventMedalRules,
            $defaultMedalRules,
            $regionType
        ) {

            foreach ($rankingByCategory as $categoryId => $rows) {

                // 5️⃣ Hapus standing lama (per group + category)
                MedalStanding::where('event_id', $competition->event_id)
                    ->where('event_group_id', $competition->event_group_id)
                    ->when(
                        empty($categoryId) || $categoryId === 'null',
                        fn ($q) => $q->whereNull('event_category_id'),
                        fn ($q) => $q->where('event_category_id', $categoryId)
                    )
                    ->delete();

                // 6️⃣ Ambil Top N
                $top = collect($rows)->take($topN)->values();


                foreach ($top as $idx => $row) {
                    $order = $idx + 1;

                    // 🎖 Ambil rule medali
                    $rule = $eventMedalRules[$order]
                        ?? $defaultMedalRules[$order]
                        ?? null;

                    if (!$rule) {
                        continue;
                    }


                    if ($this->isTeamMode($competition)) {

                        $contingentName = trim($row['contingent'] ?? '');

                        if ($contingentName === '') {
                            continue;
                        }

                        /**
                         * 🔑 Tentukan region_id berdasarkan event_level
                         */
                        $regionId = match ($regionType) {
                            EventContingent::REGION_PROVINCE =>
                                DB::table('provinces')->where('name', $contingentName)->value('id'),

                            EventContingent::REGION_REGENCY =>
                                DB::table('regencies')->where('name', $contingentName)->value('id'),

                            EventContingent::REGION_DISTRICT =>
                                DB::table('districts')->where('name', $contingentName)->value('id'),

                            EventContingent::REGION_VILLAGE =>
                                DB::table('villages')->where('name', $contingentName)->value('id'),

                            default => null,
                        };



                        if (!$regionId) {
                            // safety skip (nama tidak ditemukan)
                            continue;
                        }

                        /**
                         * 🔗 Ambil EventContingent
                         */
                        $contingent = EventContingent::where([
                            'event_id'    => $competition->event_id,
                            'region_type' => $regionType,
                            'region_id'   => $regionId,
                        ])->first();


                        if (!$contingent) {
                            continue;
                        }

                        /**
                         * 📸 Snapshot nama wilayah
                         */
                        $regionName = $contingent->region_name;


                    } else {

                        /**
                         * ==========================
                         * 🟩 INDIVIDU MODE
                         * ==========================
                         */
                        if (!isset($row['event_participant_id'])) {
                            continue;
                        }

                        $participant = EventParticipant::with([
                            'participant:id,province_id,regency_id,district_id,village_id',
                        ])->find($row['event_participant_id']);

                        if (!$participant || !$participant->participant) {
                            continue;
                        }

                        $regionId = match ($regionType) {
                            EventContingent::REGION_PROVINCE => $participant->participant->province_id,
                            EventContingent::REGION_REGENCY  => $participant->participant->regency_id,
                            EventContingent::REGION_DISTRICT => $participant->participant->district_id,
                            EventContingent::REGION_VILLAGE  => $participant->participant->village_id,
                            default => null,
                        };

                        if (!$regionId) {
                            continue;
                        }

                        $regionName = match ($regionType) {
                            EventContingent::REGION_PROVINCE => DB::table('provinces')->where('id', $regionId)->value('name'),
                            EventContingent::REGION_REGENCY  => DB::table('regencies')->where('id', $regionId)->value('name'),
                            EventContingent::REGION_DISTRICT => DB::table('districts')->where('id', $regionId)->value('name'),
                            EventContingent::REGION_VILLAGE  => DB::table('villages')->where('id', $regionId)->value('name'),
                        };
                    }


                    // 7️⃣ SIMPAN MEDAL STANDING
                    MedalStanding::create([
                        'event_id'            => $competition->event_id,
                        'event_group_id'      => $competition->event_group_id,
                        'event_category_id'   => empty($categoryId) || $categoryId === 'null'
                            ? null
                            : $categoryId,

                        'order_number'        => $order,
                        'event_medal_rule_id' => $rule instanceof EventMedalRule ? $rule->id : null,
                        'medal_rule_id'       => $rule instanceof MedalRule ? $rule->id : null,

                        'region_type'         => $regionType,
                        'region_id'           => $regionId,
                        'region_name'         => $regionName,
                    ]);
                }
            }

            $medals = MedalStanding::where('event_id', $competition->event_id)
                ->where('event_group_id', $competition->event_group_id)
                ->get();

            EventSnapshot::updateOrCreate(
                [
                    'event_id' => $competition->event_id,
                    'type'     => 'medal',
                ],
                [
                    'payload' => [
                        'event_group_id' => $competition->event_group_id,
                        'medal_standings' => $medals->toArray(),
                    ],
                    'published_at' => now(),
                ]
            );


            $this->loadEventContingentsFromRegion($competition->event);
            // 🔄 Rebuild klasemen kontingen
            $this->rebuildEventContingentsFromMedals($competition->event_id);

           $leaderboard = $this->buildLeaderboard($competition->event_id);

            EventSnapshot::updateOrCreate(
                [
                    'event_id' => $competition->event_id,
                    'type' => 'leaderboard',
                ],
                [
                    'payload' => $leaderboard,
                    'published_at' => now(),
                ]
            );

        });

        return response()->json([
            'message'       => 'Published',
            'published_top' => $topN,
            'is_team'       => $this->isTeamMode($competition) ? 1 : 0,
        ]);
    }


    private function buildLeaderboard(int $eventId)
    {
        return DB::table('event_contingents as ec')
            ->leftJoin('event_contingent_medals as ecm', 'ecm.event_contingent_id', '=', 'ec.id')

            ->leftJoin('provinces as p', fn($j) =>
                $j->on('p.id','=','ec.region_id')->where('ec.region_type','province'))
            ->leftJoin('regencies as r', fn($j) =>
                $j->on('r.id','=','ec.region_id')->where('ec.region_type','regency'))
            ->leftJoin('districts as d', fn($j) =>
                $j->on('d.id','=','ec.region_id')->where('ec.region_type','district'))
            ->leftJoin('villages as v', fn($j) =>
                $j->on('v.id','=','ec.region_id')->where('ec.region_type','village'))

            ->where('ec.event_id', $eventId)

            ->selectRaw('
                COALESCE(p.name, r.name, d.name, v.name) as region_name,
                ec.total_point,

                SUM(CASE WHEN ecm.order_number = 1 THEN ecm.medal_count ELSE 0 END) as juara_1,
                SUM(CASE WHEN ecm.order_number = 2 THEN ecm.medal_count ELSE 0 END) as juara_2,
                SUM(CASE WHEN ecm.order_number = 3 THEN ecm.medal_count ELSE 0 END) as juara_3,
                SUM(CASE WHEN ecm.order_number = 4 THEN ecm.medal_count ELSE 0 END) as harapan_1,
                SUM(CASE WHEN ecm.order_number = 5 THEN ecm.medal_count ELSE 0 END) as harapan_2,
                SUM(CASE WHEN ecm.order_number = 6 THEN ecm.medal_count ELSE 0 END) as harapan_3
            ')
            ->groupBy('region_name','ec.total_point')
            ->orderByDesc('ec.total_point')
            ->orderByDesc('juara_1')
            ->orderByDesc('juara_2')
            ->orderByDesc('juara_3')
            ->orderByDesc('harapan_1')
            ->orderByDesc('harapan_2')
            ->orderByDesc('harapan_3')
            ->orderBy('region_name')
            ->get();
    }




    // =========================================================
    // CORE BUILDER: INDIVIDU vs GROUP
    // =========================================================
    private function buildRankingFromScoresheets(EventCompetition $competition, bool $withDebug = false): array
    {
        $competition->load(['eventGroup:id,event_id,branch_id,is_team,full_name']);

        $isTeam = $this->isTeamMode($competition);
        return $isTeam
            ? $this->buildRankingGroupedByContingentCategory($competition, $withDebug)
            : $this->buildRankingIndividu($competition, $withDebug);
    }

    /**
     * INDIVIDU (existing behaviour)
     */
    private function buildRankingIndividu(EventCompetition $competition, bool $withDebug = false): array
    {
        $cmpDecimals = 2;

        $fieldOrder = EventFieldComponent::query()
            ->where('event_group_id', $competition->event_group_id)
            ->orderByRaw('COALESCE(order_number, 999999) asc')
            ->pluck('id')
            ->values()
            ->all();

        $chiefSource = null;
        $chiefJudgeId = $this->getChiefJudgeId($competition, $chiefSource);

        $rows = EventScoresheet::query()
            ->with([
                'eventParticipant:id,event_id,participant_id,event_branch_id,event_group_id,event_category_id,contingent',
                'eventParticipant.participant:id,full_name,gender',
                'eventParticipant.eventBranch:id,full_name',
                'eventParticipant.eventGroup:id,full_name',
                'eventParticipant.eventCategory:id,full_name',
                'items:id,event_scoresheet_id,event_field_component_id,score,weighted_score',
            ])
            ->where('event_competition_id', $competition->id)
            ->get();

        $participants = [];

        foreach ($rows as $ss) {
            $ep = $ss->eventParticipant;
            if (!$ep || !$ep->participant) continue;

            $pid = (int)$ep->id;
            $judgeId = (int)$ss->judge_id;

            if (!isset($participants[$pid])) {
                $participants[$pid] = [
                    'event_participant_id' => $ep->id,
                    'participant_id' => $ep->participant_id,
                    'full_name' => $ep->participant->full_name,
                    'gender' => $ep->participant->gender,
                    'contingent' => $ep->contingent,
                    'branch' => $ep->eventBranch?->full_name,
                    'group' => $ep->eventGroup?->full_name,

                    'event_category_id' => $ep->event_category_id,
                    'category_name'     => $ep->eventCategory?->full_name,
                    'category'          => $ep->eventCategory?->full_name,

                    'scores' => [],

                    'field_totals' => [],
                    'field_counts' => [],
                    'field_avg' => [],

                    'chief_field' => [],
                ];

                foreach ($fieldOrder as $fcId) {
                    $participants[$pid]['field_totals'][(string)$fcId] = 0.0;
                    $participants[$pid]['field_counts'][(string)$fcId] = 0;
                    $participants[$pid]['chief_field'][(string)$fcId]  = null;
                }
            }

            $participants[$pid]['scores'][(string)$judgeId] = (float)$ss->total_score;

            foreach (($ss->items ?? []) as $it) {
                $fcId = (int)$it->event_field_component_id;
                if (!$fcId) continue;

                $val = $it->weighted_score;
                if ($val === null) $val = $it->score;
                if ($val === null) continue;

                if (!array_key_exists((string)$fcId, $participants[$pid]['field_totals'])) {
                    $participants[$pid]['field_totals'][(string)$fcId] = 0.0;
                    $participants[$pid]['field_counts'][(string)$fcId] = 0;
                    $participants[$pid]['chief_field'][(string)$fcId]  = null;
                }

                $participants[$pid]['field_totals'][(string)$fcId] += (float)$val;
                $participants[$pid]['field_counts'][(string)$fcId] += 1;

                if ($chiefJudgeId && $judgeId === (int)$chiefJudgeId) {
                    $participants[$pid]['chief_field'][(string)$fcId] = (float)$val;
                }
            }
        }

        $list = array_values($participants);

        foreach ($list as &$p) {
            $vals = array_values($p['scores'] ?? []);
            $p['judge_count'] = count($vals);
            $p['final_score'] = $p['judge_count'] > 0 ? round(array_sum($vals) / $p['judge_count'], 2) : 0;

            $p['field_avg'] = [];
            foreach (($p['field_totals'] ?? []) as $fcId => $sum) {
                $cnt = (int)($p['field_counts'][(string)$fcId] ?? 0);
                $p['field_avg'][(string)$fcId] = $cnt > 0 ? ((float)$sum / $cnt) : 0.0;
            }
        }
        unset($p);

        // rank per category + comparator tiebreak
        $grouped = [];
        foreach ($list as $p) {
            $grouped[(string)($p['event_category_id'] ?? 'null')][] = $p;
        }

        $debugPairs = [];
        $final = [];

        foreach ($grouped as $cid => $items) {
            usort($items, function ($a, $b) use ($fieldOrder, $cmpDecimals, &$debugPairs) {
                [$cmp, $decidedBy] = $this->compareParticipants($a, $b, $fieldOrder, $cmpDecimals);

                $debugPairs[] = [
                    'a' => ['id' => $a['event_participant_id'], 'name' => $a['full_name']],
                    'b' => ['id' => $b['event_participant_id'], 'name' => $b['full_name']],
                    'decided_by' => $decidedBy,
                ];

                return $cmp;
            });

            foreach ($items as $i => $row) {
                $row['rank'] = $i + 1;
                $final[] = $row;
            }
        }

        // Debug tie groups (final_score sama)
        $tieGroups = [];
        if ($withDebug) {
            $byFinal = [];
            foreach ($final as $p) {
                $k = $this->roundCmp($p['final_score'] ?? 0, $cmpDecimals);
                $byFinal[(string)$k][] = $p;
            }
            foreach ($byFinal as $k => $group) {
                if (count($group) <= 1) continue;

                $tieGroups[] = [
                    'final_score_cmp' => (float)$k,
                    'rows' => array_map(function ($p) use ($cmpDecimals) {
                        $fa = [];
                        $cf = [];
                        foreach (($p['field_avg'] ?? []) as $fid => $v) $fa[(string)$fid] = $this->roundCmp($v, $cmpDecimals);
                        foreach (($p['chief_field'] ?? []) as $fid => $v) $cf[(string)$fid] = ($v === null ? null : $this->roundCmp($v, $cmpDecimals));

                        return [
                            'event_participant_id' => $p['event_participant_id'],
                            'full_name' => $p['full_name'],
                            'final_score_cmp' => $this->roundCmp($p['final_score'] ?? 0, $cmpDecimals),
                            'field_avg_cmp' => $fa,
                            'chief_field_cmp' => $cf,
                        ];
                    }, $group),
                ];
            }
        }

        return [
            'ranking' => $final,
            'debug' => $withDebug ? [
                'cmp_decimals' => $cmpDecimals,
                'chief_judge_id' => $chiefJudgeId,
                'chief_source' => $chiefSource,
                'field_order' => $fieldOrder,
                'tie_groups' => $tieGroups,
                'pairs' => $debugPairs,
                'mode' => 'individu',
            ] : null,
        ];
    }

    /**
     * GROUP/TEAM:
     * ranking per (contingent + event_category_id)
     * - final_score: rata-rata (avg total_score per judge) across judges
     * - field_avg[fc]: rata-rata weighted_score across ALL cells (participants x judges)
     * - chief_field[fc]: rata-rata weighted_score oleh chief judge across participants (NULL jika chief tidak menilai)
     */
    private function buildRankingGroupedByContingentCategory(EventCompetition $competition, bool $withDebug = false): array
    {
        $cmpDecimals = 2;

        $fieldOrder = EventFieldComponent::query()
            ->where('event_group_id', $competition->event_group_id)
            ->orderByRaw('COALESCE(order_number, 999999) asc')
            ->pluck('id')
            ->values()
            ->all();

        $chiefSource = null;
        $chiefJudgeId = $this->getChiefJudgeId($competition, $chiefSource);

        $rows = EventScoresheet::query()
            ->with([
                'eventParticipant:id,event_id,participant_id,event_branch_id,event_group_id,event_category_id,contingent',
                'eventParticipant.participant:id,full_name,gender',
                'eventParticipant.eventCategory:id,full_name',
                'items:id,event_scoresheet_id,event_field_component_id,score,weighted_score',
            ])
            ->where('event_competition_id', $competition->id)
            ->get();

        $groups = [];
        $judgesSet = [];

        $makeKey = function ($contingent, $cid) {
            $c = trim((string)($contingent ?? ''));
            if ($c === '') $c = '-';
            $cat = ($cid === null || $cid === '' || $cid === 'null') ? 'null' : (string)$cid;
            return $c . '||' . $cat;
        };

        foreach ($rows as $ss) {
            $ep = $ss->eventParticipant;
            if (!$ep || !$ep->participant) continue;

            $judgeId = (int)$ss->judge_id;
            $judgesSet[(string)$judgeId] = true;

            $key = $makeKey($ep->contingent, $ep->event_category_id);

            if (!isset($groups[$key])) {
                $cont = trim((string)($ep->contingent ?? ''));
                if ($cont === '') $cont = '-';

                $groups[$key] = [
                    'group_key' => $key,
                    'contingent' => $cont,

                    'event_category_id' => $ep->event_category_id,
                    'category_name'     => $ep->eventCategory?->full_name,
                    'category'          => $ep->eventCategory?->full_name,

                    // display
                    'full_name' => strtoupper($cont),
                    'member_names' => [],
                    'member_count' => 0,

                    // score aggregators
                    'judge_totals' => [], // judgeId => sum total_score
                    'judge_counts' => [], // judgeId => count

                    // field aggregators (all cells)
                    'field_totals' => [],
                    'field_counts' => [],

                    // chief aggregators
                    'chief_totals' => [],
                    'chief_counts' => [],

                    // derived
                    'scores' => [],      // judge avg total_score
                    'final_score' => 0.0,
                    'field_avg' => [],
                    'chief_field' => [],
                ];

                foreach ($fieldOrder as $fcId) {
                    $groups[$key]['field_totals'][(string)$fcId] = 0.0;
                    $groups[$key]['field_counts'][(string)$fcId] = 0;
                    $groups[$key]['chief_totals'][(string)$fcId] = 0.0;
                    $groups[$key]['chief_counts'][(string)$fcId] = 0;
                    $groups[$key]['field_avg'][(string)$fcId] = 0.0;
                    $groups[$key]['chief_field'][(string)$fcId] = null;
                }
            }

            // member
            $groups[$key]['member_names'][] = $ep->participant->full_name;
            $groups[$key]['member_count']++;

            // totals per judge
            if (!isset($groups[$key]['judge_totals'][(string)$judgeId])) {
                $groups[$key]['judge_totals'][(string)$judgeId] = 0.0;
                $groups[$key]['judge_counts'][(string)$judgeId] = 0;
            }
            $groups[$key]['judge_totals'][(string)$judgeId] += (float)$ss->total_score;
            $groups[$key]['judge_counts'][(string)$judgeId] += 1;

            // fields
            foreach (($ss->items ?? []) as $it) {
                $fcId = (int)$it->event_field_component_id;
                if (!$fcId) continue;

                $val = $it->weighted_score;
                if ($val === null) $val = $it->score;
                if ($val === null) continue;

                if (!array_key_exists((string)$fcId, $groups[$key]['field_totals'])) {
                    $groups[$key]['field_totals'][(string)$fcId] = 0.0;
                    $groups[$key]['field_counts'][(string)$fcId] = 0;
                    $groups[$key]['chief_totals'][(string)$fcId] = 0.0;
                    $groups[$key]['chief_counts'][(string)$fcId] = 0;
                    $groups[$key]['field_avg'][(string)$fcId] = 0.0;
                    $groups[$key]['chief_field'][(string)$fcId] = null;
                }

                $groups[$key]['field_totals'][(string)$fcId] += (float)$val;
                $groups[$key]['field_counts'][(string)$fcId] += 1;

                if ($chiefJudgeId && $judgeId === (int)$chiefJudgeId) {
                    $groups[$key]['chief_totals'][(string)$fcId] += (float)$val;
                    $groups[$key]['chief_counts'][(string)$fcId] += 1;
                }
            }
        }

        $judgeIds = array_map('intval', array_keys($judgesSet));
        sort($judgeIds);

        $list = array_values($groups);

        // compute derived
        foreach ($list as &$g) {
            // normalize member list
            $names = array_values(array_unique(array_filter($g['member_names'] ?? [])));
            sort($names, SORT_NATURAL | SORT_FLAG_CASE);
            $g['member_names'] = $names;
            $g['member_count'] = count($names);

            // per judge avg + final_score
            $sumAcrossJudges = 0.0;
            $cntJudges = 0;

            foreach ($judgeIds as $jid) {
                $tot = (float)($g['judge_totals'][(string)$jid] ?? 0);
                $cnt = (int)($g['judge_counts'][(string)$jid] ?? 0);
                $avg = $cnt > 0 ? ($tot / $cnt) : 0.0;

                $g['scores'][(string)$jid] = $avg;
                $sumAcrossJudges += $avg;
                $cntJudges++;
            }

            $g['final_score'] = $cntJudges > 0 ? round($sumAcrossJudges / $cntJudges, 2) : 0;

            // field_avg
            foreach (($g['field_totals'] ?? []) as $fcId => $sum) {
                $cnt = (int)($g['field_counts'][(string)$fcId] ?? 0);
                $g['field_avg'][(string)$fcId] = $cnt > 0 ? ((float)$sum / $cnt) : 0.0;
            }

            // chief_field
            foreach (($g['chief_totals'] ?? []) as $fcId => $sum) {
                $cnt = (int)($g['chief_counts'][(string)$fcId] ?? 0);
                $g['chief_field'][(string)$fcId] = $cnt > 0 ? ((float)$sum / $cnt) : null;
            }
        }
        unset($g);

        // rank per category
        $groupedByCategory = [];
        foreach ($list as $g) {
            $groupedByCategory[(string)($g['event_category_id'] ?? 'null')][] = $g;
        }

        $debugPairs = [];
        $final = [];

        foreach ($groupedByCategory as $cid => $items) {
            usort($items, function ($a, $b) use ($fieldOrder, $cmpDecimals, &$debugPairs) {
                [$cmp, $decidedBy] = $this->compareParticipants($a, $b, $fieldOrder, $cmpDecimals);

                $debugPairs[] = [
                    'a' => ['id' => $a['group_key'] ?? null, 'name' => ($a['contingent'] ?? '-')],
                    'b' => ['id' => $b['group_key'] ?? null, 'name' => ($b['contingent'] ?? '-')],
                    'decided_by' => $decidedBy,
                ];

                return $cmp;
            });

            foreach ($items as $i => $row) {
                $row['rank'] = $i + 1;
                $final[] = $row;
            }
        }

        // Debug tie groups (final_score sama)
        $tieGroups = [];
        if ($withDebug) {
            $byFinal = [];
            foreach ($final as $p) {
                $k = $this->roundCmp($p['final_score'] ?? 0, $cmpDecimals);
                $byFinal[(string)$k][] = $p;
            }
            foreach ($byFinal as $k => $group) {
                if (count($group) <= 1) continue;

                $tieGroups[] = [
                    'final_score_cmp' => (float)$k,
                    'rows' => array_map(function ($p) use ($cmpDecimals) {
                        $fa = [];
                        $cf = [];
                        foreach (($p['field_avg'] ?? []) as $fid => $v) $fa[(string)$fid] = $this->roundCmp($v, $cmpDecimals);
                        foreach (($p['chief_field'] ?? []) as $fid => $v) $cf[(string)$fid] = ($v === null ? null : $this->roundCmp($v, $cmpDecimals));

                        return [
                            'group_key' => $p['group_key'] ?? null,
                            'contingent' => $p['contingent'] ?? null,
                            'final_score_cmp' => $this->roundCmp($p['final_score'] ?? 0, $cmpDecimals),
                            'field_avg_cmp' => $fa,
                            'chief_field_cmp' => $cf,
                        ];
                    }, $group),
                ];
            }
        }

        return [
            'ranking' => $final,
            'debug' => $withDebug ? [
                'cmp_decimals' => $cmpDecimals,
                'chief_judge_id' => $chiefJudgeId,
                'chief_source' => $chiefSource,
                'field_order' => $fieldOrder,
                'tie_groups' => $tieGroups,
                'pairs' => $debugPairs,
                'mode' => 'group',
                'judge_ids' => $judgeIds,
            ] : null,
        ];
    }

    // =========================================================
    // MEDAL -> EVENT CONTINGENTS REBUILD (unchanged)
    // =========================================================
    protected function rebuildEventContingentsFromMedals(int $eventId): void
    {
        DB::transaction(function () use ($eventId) {

            /**
             * 1️⃣ RESET TOTAL POINT & MEDAL
             */
            EventContingent::where('event_id', $eventId)
                ->update(['total_point' => 0]);

            EventContingentMedal::whereIn(
                'event_contingent_id',
                EventContingent::where('event_id', $eventId)->pluck('id')
            )->delete();

            /**
             * 2️⃣ AMBIL MEDAL STANDINGS
             */
            $rows = DB::table('medal_standings as ms')
                ->leftJoin('event_medal_rules as emr', 'emr.id', '=', 'ms.event_medal_rule_id')
                ->leftJoin('medal_rules as mr', 'mr.id', '=', 'ms.medal_rule_id')
                ->where('ms.event_id', $eventId)
                ->selectRaw('
                    ms.region_type,
                    ms.region_id,
                    COALESCE(emr.order_number, mr.order_number) as order_number,
                    COALESCE(emr.medal_code, mr.medal_code)     as medal_code,
                    COALESCE(emr.medal_name, mr.medal_name)     as medal_name,
                    COALESCE(emr.point, mr.point, 0)            as point
                ')
                ->get();

            if ($rows->isEmpty()) {
                return;
            }

            /**
             * 3️⃣ GROUP BY REGION
             */
            $grouped = $rows->groupBy(fn ($r) =>
                $r->region_type . '|' . $r->region_id
            );

            foreach ($grouped as $key => $items) {
                [$regionType, $regionId] = explode('|', $key);

                /**
                 * 4️⃣ AMBIL EVENT CONTINGENT (HARUS ADA)
                 */
                $contingent = EventContingent::where([
                    'event_id'    => $eventId,
                    'region_type' => $regionType,
                    'region_id'   => $regionId,
                ])->first();

                if (!$contingent) {
                    // safety net (harusnya tidak terjadi)
                    continue;
                }

                /**
                 * 5️⃣ UPDATE TOTAL POINT
                 */
                $contingent->increment('total_point', $items->sum('point'));

                /**
                 * 6️⃣ MEDAL BREAKDOWN
                 */
                $byMedal = $items->groupBy('order_number');

                foreach ($byMedal as $order => $medals) {
                    $first = $medals->first();

                    EventContingentMedal::updateOrCreate(
                        [
                            'event_contingent_id' => $contingent->id,
                            'order_number'        => $order,
                        ],
                        [
                            'medal_code'  => $first->medal_code,
                            'medal_name'  => $first->medal_name,
                            'medal_count' => $medals->count(),
                        ]
                    );
                }
            }
        });
    }



    protected function loadEventContingentsFromRegion(Event $event): void
    {
        DB::transaction(function () use ($event) {

            switch ($event->event_level) {

                case 'national':
                    $regions = DB::table('provinces')->get(['id']);
                    $regionType = EventContingent::REGION_PROVINCE;
                    break;

                case 'province':
                    if (!$event->province_id) {
                        throw new \RuntimeException('province_id wajib untuk event level province');
                    }

                    $regions = DB::table('regencies')
                        ->where('province_id', $event->province_id)
                        ->get(['id']);

                    $regionType = EventContingent::REGION_REGENCY;
                    break;

                case 'regency':
                    if (!$event->regency_id) {
                        throw new \RuntimeException('regency_id wajib untuk event level regency');
                    }

                    $regions = DB::table('districts')
                        ->where('regency_id', $event->regency_id)
                        ->get(['id']);

                    $regionType = EventContingent::REGION_DISTRICT;
                    break;

                case 'district':
                    if (!$event->district_id) {
                        throw new \RuntimeException('district_id wajib untuk event level district');
                    }

                    $regions = DB::table('villages')
                        ->where('district_id', $event->district_id)
                        ->get(['id']);

                    $regionType = EventContingent::REGION_VILLAGE;
                    break;

                default:
                    throw new \RuntimeException('event_level tidak dikenali');
            }

            foreach ($regions as $r) {
                EventContingent::updateOrCreate(
                    [
                        'event_id'    => $event->id,
                        'region_type' => $regionType,
                        'region_id'   => $r->id,
                    ],
                    [
                        // jangan reset poin di sini
                        'total_participant' => 0,
                    ]
                );
            }
        });
    }




}
