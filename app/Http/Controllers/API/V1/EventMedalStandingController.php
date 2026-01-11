<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Event;
use App\Models\EventCompetition;
use App\Models\EventMedalRule;
use App\Models\MedalRule;
use App\Models\MedalStanding;
use App\Models\EventScoresheet;
use App\Models\EventParticipant;
use App\Models\EventContingent;
use App\Models\EventFieldComponent;
use App\Models\EventContingentMedal;
use App\Models\EventSnapshot;
use Carbon\Carbon;

class EventMedalStandingController extends Controller
{
    /**
     * =====================================================
     * BUILD MEDAL STANDING FROM FINAL COMPETITIONS
     * =====================================================
     */
    public function build(Request $request)
    {
        $eventId = $request->integer('event_id');
        abort_if(!$eventId, 422, 'event_id wajib');

        // 🔴 RESET LOG LAMA
        DB::table('event_build_logs')
            ->where('event_id', $eventId)
            ->delete();

        // =============================================
        // 1. Pastikan event_medal_rules tersedia
        // =============================================
        $this->ensureEventMedalRules($eventId);

        // =============================================
        // 2. Reset medal standings event
        // =============================================
        MedalStanding::where('event_id', $eventId)->delete();

        // =============================================
        // 3. Ambil semua kompetisi FINAL
        // =============================================
        $competitions = EventCompetition::with(['round', 'eventGroup'])
            ->where('event_id', $eventId)
            ->whereHas('round', fn ($q) =>
                $q->whereRaw('LOWER(name) LIKE ?', ['%final%'])
            )
            ->get();

        foreach ($competitions as $competition) {

            // 🔔 LOG MULAI (langsung commit)
            DB::table('event_build_logs')->insert([
                'event_id'   => $eventId,
                'message'    => '⏳ Menghitung ' . $competition->full_name,
                'created_at' => now(),
            ]);

             // ⏱️ delay agar frontend sempat polling
            usleep(900_000); // 0.9 detik

            // ⛔ TRANSACTION HANYA UNTUK SATU KOMPETISI
            DB::transaction(function () use ($competition) {
                $this->publish($competition);
            });

            // 🔔 LOG SELESAI (langsung commit)
            DB::table('event_build_logs')->insert([
                'event_id'   => $eventId,
                'message'    => '✅ Selesai ' . $competition->full_name,
                'created_at' => now(),
            ]);

            usleep(900_000); // 0.9 detik
        }

        // 🔴 SINYAL SELESAI GLOBAL
        DB::table('event_build_logs')->insert([
            'event_id'   => $eventId,
            'message'    => 'SELESAI SEMUA',
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Medal standing berhasil dihitung',
        ]);
    }


    /**
     * =========================================================
     * Pastikan event_medal_rules tersedia untuk event
     * - Jika belum ada → copy dari medal_rules (master)
     * - Jika sudah ada → biarkan (tidak overwrite)
     * =========================================================
     */
    protected function ensureEventMedalRules(int $eventId): void
    {
        // Cek apakah sudah ada rule untuk event ini
        $exists = EventMedalRule::where('event_id', $eventId)->exists();
        if ($exists) {
            return;
        }

        // Ambil master medal rules (aktif & berurutan)
        $masterRules = MedalRule::where('is_active', true)
            ->orderBy('order_number')
            ->get();

        if ($masterRules->isEmpty()) {
            throw new \RuntimeException(
                'Medal rules master belum tersedia. Tidak dapat membangun medal standing.'
            );
        }

        // Copy ke event_medal_rules
        foreach ($masterRules as $rule) {
            EventMedalRule::create([
                'event_id'     => $eventId,
                'order_number' => $rule->order_number,
                'medal_code'   => $rule->medal_code,
                'medal_name'   => $rule->medal_name,
                'point'        => $rule->point,
                'is_active'    => $rule->is_active,
            ]);
        }
    }

    /**
     * =====================================================
     * MAINFUNCTION
     * =====================================================
     */
    protected function publish(EventCompetition $competition)
    {
        $competition->load([
            'round:id,name',
            'eventGroup:id,event_id,branch_id,is_team,full_name,judge_assignment_mode,event_judge_panel_id',
            'event',
        ]);

        /* =====================================================
        * 1️⃣ VALIDASI: HANYA FINAL
        * ===================================================== */
        $roundName = strtolower(trim($competition->round?->name ?? ''));
        if (!str_contains($roundName, 'final')) {
            return response()->json([
                'message' => 'Publish hanya diperbolehkan untuk babak final.',
            ], 422);
        }

        /* =====================================================
        * 2️⃣ TOP N (DINAMIS)
        * ===================================================== */
        $data = [
            'top_n' => 6
        ];
        $topN = (int) ($data['top_n'] ?? 6);

        /* =====================================================
        * 3️⃣ REGION TYPE
        * ===================================================== */
        $regionTypeMap = [
            'national' => EventContingent::REGION_PROVINCE,
            'province' => EventContingent::REGION_REGENCY,
            'regency'  => EventContingent::REGION_DISTRICT,
            'district' => EventContingent::REGION_VILLAGE,
        ];

        $regionType = $regionTypeMap[$competition->event->event_level] ?? null;
        if (!$regionType) {
            return response()->json([
                'message' => 'Event level tidak valid.',
            ], 422);
        }

        /* =====================================================
        * 4️⃣ BUILD RANKING DARI SCORESHEET
        * ===================================================== */
        $res = $this->buildRankingFromScoresheets($competition, false);
        $rankingByCategory = collect($res['ranking'])->groupBy(
            fn ($r) => $r['event_category_id'] ?? 'null'
        );

        /* =====================================================
        * 5️⃣ SNAPSHOT RANKING
        * ===================================================== */
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

        /* =====================================================
        * 6️⃣ MEDAL RULES
        * ===================================================== */
        $eventMedalRules = EventMedalRule::where('event_id', $competition->event_id)
            ->where('is_active', true)
            ->orderBy('order_number')
            ->get()
            ->keyBy('order_number');

        $defaultMedalRules = MedalRule::where('is_active', true)
            ->orderBy('order_number')
            ->get()
            ->keyBy('order_number');

        /* =====================================================
        * 7️⃣ TRANSACTION
        * ===================================================== */
        foreach ($rankingByCategory as $categoryId => $rows) {

            MedalStanding::where('event_id', $competition->event_id)
                ->where('event_group_id', $competition->event_group_id)
                ->when(
                    $categoryId === 'null',
                    fn ($q) => $q->whereNull('event_category_id'),
                    fn ($q) => $q->where('event_category_id', $categoryId)
                )
                ->delete();

            $topRows = collect($rows)->take($topN)->values();

            foreach ($topRows as $idx => $row) {
                $order = $idx + 1;

                $rule = $eventMedalRules[$order]
                    ?? $defaultMedalRules[$order]
                    ?? null;

                if (!$rule) continue;

                /* =============================
                * REGU
                * ============================= */
                if ($this->isTeamMode($competition)) {

                    $contingentName = trim($row['contingent'] ?? '');
                    if ($contingentName === '') continue;

                    $regionId = $this->resolveRegionIdByName(
                        $regionType,
                        $contingentName
                    );

                    if (!$regionId) continue;

                    $regionName = $contingentName;
                }
                /* =============================
                * INDIVIDU
                * ============================= */
                else {
                    if (!isset($row['event_participant_id'])) continue;

                    [$regionId, $regionName] =
                        $this->resolveParticipantRegion(
                            $row['event_participant_id'],
                            $regionType
                        );

                    if (!$regionId) continue;
                }

                MedalStanding::create([
                    'event_id'          => $competition->event_id,
                    'event_group_id'    => $competition->event_group_id,
                    'event_category_id' => $categoryId === 'null' ? null : $categoryId,
                    'order_number'      => $order,
                    'event_medal_rule_id' =>
                        $rule instanceof EventMedalRule ? $rule->id : null,
                    'medal_rule_id' =>
                        $rule instanceof MedalRule ? $rule->id : null,
                    'region_type' => $regionType,
                    'region_id'   => $regionId,
                    'region_name' => $regionName,
                ]);
            }
        }

        /* =====================================================
        * REBUILD CONTINGENT + LEADERBOARD
        * ===================================================== */
        $this->loadEventContingentsFromRegion($competition->event);
        $this->rebuildEventContingentsFromMedals($competition->event_id);

        EventSnapshot::updateOrCreate(
            [
                'event_id' => $competition->event_id,
                'type'     => 'leaderboard',
            ],
            [
                'payload' => $this->buildLeaderboard($competition->event_id),
                'published_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Published',
            'top_n'   => $topN,
            'is_team' => $this->isTeamMode($competition),
        ]);
    }

    /**
     * =====================================================
     * SUBFUNCTION
     * =====================================================
     */
    
    private function buildRankingFromScoresheets(EventCompetition $competition, bool $withDebug = false): array {
        $competition->load([
            'eventGroup:id,event_id,is_team,judge_assignment_mode,event_judge_panel_id',
        ]);

        return $competition->eventGroup->is_team
            ? $this->buildRankingGroupedByContingentCategory($competition, $withDebug)
            : $this->buildRankingIndividu($competition, $withDebug);
    }

    private function buildRankingIndividu(EventCompetition $competition, bool $withDebug = false): array {
        $cmpDecimals = 2;

        /* ============================================
        * FIELD ORDER
        * ============================================ */
        $fieldOrder = EventFieldComponent::query()
            ->where('event_group_id', $competition->event_group_id)
            ->orderByRaw('COALESCE(order_number, 999999)')
            ->pluck('id')
            ->map(fn ($v) => (int)$v)
            ->all();

        /* ============================================
        * CHIEF JUDGE
        * ============================================ */
        $chiefSource = null;
        $chiefJudgeId = $this->getChiefJudgeId($competition, $chiefSource);

        /* ============================================
        * LOAD SCORESHEETS
        * ============================================ */
        $sheets = EventScoresheet::query()
            ->with([
                'eventParticipant:id,participant_id,event_category_id,contingent',
                'eventParticipant.participant:id,full_name,gender',
                'eventParticipant.eventCategory:id,full_name',
                'items:id,event_scoresheet_id,event_field_component_id,score,weighted_score',
            ])
            ->where('event_competition_id', $competition->id)
            ->where('status', 'submitted')
            ->get();

        $rows = [];

        foreach ($sheets as $ss) {
            $ep = $ss->eventParticipant;
            if (!$ep || !$ep->participant) continue;

            $pid = (int) $ep->id;
            $jid = (int) $ss->event_judge_id;

            if (!isset($rows[$pid])) {
                $rows[$pid] = [
                    'event_participant_id' => $pid,
                    'full_name' => $ep->participant->full_name,
                    'contingent' => $ep->contingent,
                    'event_category_id' => $ep->event_category_id,
                    'event_category' => [
                        'id' => $ep->event_category_id,
                        'full_name' => $ep->eventCategory?->full_name,
                    ],

                    'judge_totals' => [],
                    'judge_counts' => [],

                    'field_totals' => [],
                    'field_counts' => [],

                    'chief_field' => [],
                    'field_avg' => [],

                    'final_score' => 0,
                ];

                foreach ($fieldOrder as $fid) {
                    $rows[$pid]['field_totals'][$fid] = 0;
                    $rows[$pid]['field_counts'][$fid] = 0;
                    $rows[$pid]['chief_field'][$fid]  = null;
                }
            }

            // total per judge
            $rows[$pid]['judge_totals'][$jid] =
                ($rows[$pid]['judge_totals'][$jid] ?? 0) + $ss->total_score;

            $rows[$pid]['judge_counts'][$jid] =
                ($rows[$pid]['judge_counts'][$jid] ?? 0) + 1;

            foreach ($ss->items as $it) {
                if (!$it->event_field_component_id) continue;

                $fid = (int) $it->event_field_component_id;
                $val = $it->weighted_score ?? $it->score;

                $rows[$pid]['field_totals'][$fid] += $val;
                $rows[$pid]['field_counts'][$fid]++;

                if ($chiefJudgeId && $jid === $chiefJudgeId) {
                    $rows[$pid]['chief_field'][$fid] = $val;
                }
            }
        }

        /* ============================================
        * COMPUTE FINAL SCORE
        * ============================================ */
        foreach ($rows as &$r) {
            $sum = 0;
            $cnt = 0;

            foreach ($r['judge_totals'] as $jid => $tot) {
                $avg = $tot / $r['judge_counts'][$jid];
                $sum += $avg;
                $cnt++;
            }

            $r['final_score'] = $cnt ? round($sum / $cnt, 2) : 0;

            foreach ($fieldOrder as $fid) {
                $c = $r['field_counts'][$fid] ?? 0;
                $r['field_avg'][$fid] = $c ? round($r['field_totals'][$fid] / $c, 2) : 0;
            }
        }
        unset($r);

        /* ============================================
        * SORT + TIE BREAK PER CATEGORY
        * ============================================ */
        $grouped = collect($rows)->groupBy('event_category_id');
        $final = [];

        foreach ($grouped as $cid => $items) {
            $items = $items->values()->all();

            usort($items, function ($a, $b) use ($fieldOrder, $cmpDecimals) {
                return $this->compareParticipants(
                    $a, $b, $fieldOrder, $cmpDecimals
                )[0];
            });

            foreach ($items as $i => $row) {
                $row['rank'] = $i + 1;
                $final[] = $row;
            }
        }

        return [
            'ranking' => $final,
            'debug' => $withDebug ? [
                'mode' => 'individu',
                'field_order' => $fieldOrder,
                'chief_judge_id' => $chiefJudgeId,
                'chief_source' => $chiefSource,
            ] : null,
        ];
    }

    private function buildRankingGroupedByContingentCategory(EventCompetition $competition, bool $withDebug = false): array {
        $cmpDecimals = 2;

        $fieldOrder = EventFieldComponent::query()
            ->where('event_group_id', $competition->event_group_id)
            ->orderByRaw('COALESCE(order_number, 999999)')
            ->pluck('id')
            ->map(fn ($v) => (int)$v)
            ->all();

        $chiefSource = null;
        $chiefJudgeId = $this->getChiefJudgeId($competition, $chiefSource);

        $sheets = EventScoresheet::query()
            ->with([
                'eventParticipant:id,event_category_id,contingent',
                'eventParticipant.participant:id,full_name',
                'eventParticipant.eventCategory:id,full_name',
                'items:id,event_scoresheet_id,event_field_component_id,score,weighted_score',
            ])
            ->where('event_competition_id', $competition->id)
            ->where('status', 'submitted')
            ->get();

        $groups = [];

        foreach ($sheets as $ss) {
            $ep = $ss->eventParticipant;
            if (!$ep) continue;

            $key = ($ep->contingent ?: '-') . '|' . ($ep->event_category_id ?? 'null');
            $jid = (int)$ss->event_judge_id;

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'group_key' => $key,
                    'contingent' => $ep->contingent ?: '-',
                    'event_category_id' => $ep->event_category_id,
                    'event_category' => [
                        'id' => $ep->event_category_id,
                        'full_name' => $ep->eventCategory?->full_name,
                    ],

                    'member_names' => [],
                    'judge_totals' => [],
                    'judge_counts' => [],
                    'field_totals' => [],
                    'field_counts' => [],
                    'chief_field' => [],
                    'field_avg' => [],
                    'final_score' => 0,
                ];

                foreach ($fieldOrder as $fid) {
                    $groups[$key]['field_totals'][$fid] = 0;
                    $groups[$key]['field_counts'][$fid] = 0;
                    $groups[$key]['chief_field'][$fid] = null;
                }
            }

            $groups[$key]['member_names'][] = $ep->participant?->full_name;

            $groups[$key]['judge_totals'][$jid] =
                ($groups[$key]['judge_totals'][$jid] ?? 0) + $ss->total_score;

            $groups[$key]['judge_counts'][$jid] =
                ($groups[$key]['judge_counts'][$jid] ?? 0) + 1;

            foreach ($ss->items as $it) {
                if (!$it->event_field_component_id) continue;
                $fid = (int)$it->event_field_component_id;
                $val = $it->weighted_score ?? $it->score;

                $groups[$key]['field_totals'][$fid] += $val;
                $groups[$key]['field_counts'][$fid]++;

                if ($chiefJudgeId && $jid === $chiefJudgeId) {
                    $groups[$key]['chief_field'][$fid] = $val;
                }
            }
        }

        foreach ($groups as &$g) {
            $sum = 0;
            $cnt = 0;

            foreach ($g['judge_totals'] as $jid => $tot) {
                $avg = $tot / $g['judge_counts'][$jid];
                $sum += $avg;
                $cnt++;
            }

            $g['final_score'] = $cnt ? round($sum / $cnt, 2) : 0;

            foreach ($fieldOrder as $fid) {
                $c = $g['field_counts'][$fid] ?? 0;
                $g['field_avg'][$fid] = $c
                    ? round($g['field_totals'][$fid] / $c, 2)
                    : 0;
            }

            $g['member_names'] = array_values(
                array_unique($g['member_names'])
            );
            sort($g['member_names'], SORT_NATURAL);
            $g['member_count'] = count($g['member_names']);
        }
        unset($g);

        $grouped = collect($groups)->groupBy('event_category_id');
        $final = [];

        foreach ($grouped as $cid => $items) {
            $items = $items->values()->all();

            usort($items, function ($a, $b) use ($fieldOrder, $cmpDecimals) {
                return $this->compareParticipants(
                    $a, $b, $fieldOrder, $cmpDecimals
                )[0];
            });

            foreach ($items as $i => $row) {
                $row['rank'] = $i + 1;
                $final[] = $row;
            }
        }

        return [
            'ranking' => $final,
            'debug' => $withDebug ? [
                'mode' => 'group',
                'field_order' => $fieldOrder,
                'chief_judge_id' => $chiefJudgeId,
                'chief_source' => $chiefSource,
            ] : null,
        ];
    }

    private function compareParticipants(array $a, array $b, array $fieldOrder, int $cmpDecimals = 2): array{
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

    private function isTeamMode(EventCompetition $competition): bool {
        // pastikan eventGroup ter-load jika perlu
        if (!$competition->relationLoaded('eventGroup')) {
            $competition->load(['eventGroup:id,event_id,branch_id,is_team,full_name']);
        }

        if (!is_null($competition->eventGroup->is_team)) return (bool) $competition->is_team;
        return (bool) optional($competition->eventGroup)->is_team;
    }

    private function roundCmp($v, int $decimals){
        if ($v === null) return null;
        $n = (float) $v;
        return round($n, $decimals);
    }

    protected function getChiefJudgeId(EventCompetition $competition, ?string &$source = null): ?int{
        $group = $competition->eventGroup;

        if ($group->judge_assignment_mode === 'BY_PANEL') {
            $row = DB::table('event_judge_panel_members')
                ->where('event_judge_panel_id', $group->event_judge_panel_id)
                ->where('is_chief', true)
                ->first();

            if ($row) {
                $source = 'panel';
                return (int) $row->event_judge_id;
            }
        }

        if ($group->judge_assignment_mode === 'BY_COMPONENT') {
            $row = DB::table('event_field_component_judges')
                ->where('is_chief', true)
                ->first();

            if ($row) {
                $source = 'component';
                return (int) $row->event_judge_id;
            }
        }

        $source = 'none';
        return null;
    }

    protected function resolveRegionIdByName(string $regionType, string $name): ?int {
        return match ($regionType) {
            EventContingent::REGION_PROVINCE =>
                DB::table('provinces')->where('name', $name)->value('id'),

            EventContingent::REGION_REGENCY =>
                DB::table('regencies')->where('name', $name)->value('id'),

            EventContingent::REGION_DISTRICT =>
                DB::table('districts')->where('name', $name)->value('id'),

            EventContingent::REGION_VILLAGE =>
                DB::table('villages')->where('name', $name)->value('id'),

            default => null,
        };
    }

    protected function resolveParticipantRegion( int $eventParticipantId, string $regionType ): array {
        $ep = EventParticipant::with('participant')->find($eventParticipantId);
        if (!$ep || !$ep->participant) return [null, null];

        $p = $ep->participant;

        $regionId = match ($regionType) {
            EventContingent::REGION_PROVINCE => $p->province_id,
            EventContingent::REGION_REGENCY  => $p->regency_id,
            EventContingent::REGION_DISTRICT => $p->district_id,
            EventContingent::REGION_VILLAGE  => $p->village_id,
            default => null,
        };

        if (!$regionId) return [null, null];

        $regionName = match ($regionType) {
            EventContingent::REGION_PROVINCE =>
                DB::table('provinces')->where('id', $regionId)->value('name'),
            EventContingent::REGION_REGENCY =>
                DB::table('regencies')->where('id', $regionId)->value('name'),
            EventContingent::REGION_DISTRICT =>
                DB::table('districts')->where('id', $regionId)->value('name'),
            EventContingent::REGION_VILLAGE =>
                DB::table('villages')->where('id', $regionId)->value('name'),
        };

        return [$regionId, $regionName];
    }

    protected function loadEventContingentsFromRegion(Event $event): void {

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
    }

    protected function rebuildEventContingentsFromMedals(int $eventId): void {

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
    }

    private function buildLeaderboard(int $eventId){
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
   
}
