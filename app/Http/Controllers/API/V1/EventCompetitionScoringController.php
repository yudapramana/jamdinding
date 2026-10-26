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
use App\Models\EventFieldComponentJudge;

class EventCompetitionScoringController extends Controller
{

    /**
     * ============================
     * FORM PENILAIAN (V2)
     * ============================
     */
    public function form(Request $request, EventCompetition $eventCompetition)
    {
        $request->validate([
            'event_participant_id' => ['required', 'integer'],
        ]);

        $group = $eventCompetition->eventGroup()->with([
            'judgePanel.members.eventJudge.masterJudge',
            'fieldComponents'
        ])->firstOrFail();

        // ===== Judges (from majelis)
        $judges = $group->judgePanel
            ->members
            ->map(fn ($m) => [
                'id'   => (int) $m->event_judge_id,
                'name' => $m->eventJudge->masterJudge->full_name,
            ])
            ->values();

        // ===== Components
        $components = $group->fieldComponents->map(function ($c) use ($group) {

            if ($group->judge_assignment_mode === 'BY_COMPONENT') {
                $assigned = EventFieldComponentJudge::where(
                        'event_field_component_id',
                        $c->id
                    )
                    ->pluck('event_judge_id')
                    ->map(fn ($id) => (int) $id)
                    ->values();

            } else {
                // BY_PANEL → semua hakim boleh menilai
                $assigned = null;
            }

            return [
                'id' => $c->id,
                'field_name' => $c->field_name,
                'max_score' => $c->max_score,
                'weight' => $c->weight,
                'assigned_judge_ids' => $assigned, // null = ALL
            ];
        });


        // ===== Existing Scoresheets
        $scoresheets = EventScoresheet::with('items')
            ->where('event_competition_id', $eventCompetition->id)
            ->where('event_participant_id', $request->event_participant_id)
            ->get()
            ->map(fn ($s) => [
                'event_judge_id' => $s->event_judge_id,
                'status' => $s->status,
                'items' => $s->items->map(fn ($i) => [
                    'event_field_component_id' => $i->event_field_component_id,
                    'score' => $i->score,
                    'notes' => $i->notes,
                ]),
            ]);

        return response()->json([
            'competition' => [
                'id' => $eventCompetition->id,
                'full_name' => $eventCompetition->full_name,
            ],
            'event_group' => [
                'id' => $group->id,
                'full_name' => $group->full_name,
                'is_team' => $group->is_team,
                'judge_assignment_mode' => $group->judge_assignment_mode,
            ],
            'participant' => (int) $request->event_participant_id,
            'judges' => $judges,
            'components' => $components,
            'scoresheets' => $scoresheets,
        ]);
    }

    /**
     * ============================
     * SAVE DRAFT
     * ============================
     */
    public function saveDraft(Request $request, EventCompetition $eventCompetition)
    {
        $data = $request->validate([
            'event_participant_id' => ['required', 'integer'],
            'rows' => ['required', 'array', 'min:1'],

            'rows.*.event_judge_id' => ['required', 'exists:event_judges,id'],
            'rows.*.items' => ['required', 'array'],

            'rows.*.items.*.event_field_component_id' => [
                'required',
                'exists:event_field_components,id'
            ],
            'rows.*.items.*.score' => ['nullable', 'numeric', 'min:0'],
            'rows.*.items.*.notes' => ['nullable', 'string'],
        ]);

        // ==========================================
        // Ambil peserta (SUMBER KATEGORI & TEAM)
        // ==========================================
        $eventParticipant = \App\Models\EventParticipant::query()
            ->where('id', $data['event_participant_id'])
            ->where('event_id', $eventCompetition->event_id)
            ->firstOrFail();

        \Log::info('EVENT PARTICIPANT', [
            'eventParticipant' => $eventParticipant,
            
        ]);

        // ==========================================
        // Ambil group + scoring mode
        // ==========================================
        $group = $eventCompetition->eventGroup()
            ->with('fieldComponents:id,event_group_id,max_score,weight')
            ->firstOrFail();

        $scoringMode = $group->judge_assignment_mode; // BY_PANEL | BY_COMPONENT

        // ==========================================
        // Map komponen → max_score, weight
        // ==========================================
        $componentMap = $group->fieldComponents
            ->keyBy('id')
            ->map(fn ($c) => [
                'max_score' => (float) ($c->max_score ?? 0),
                'weight'    => (float) ($c->weight ?? 0),
            ]);

        // ==========================================
        // Map komponen → hakim (BY_COMPONENT)
        // ==========================================
        $componentJudgeMap = [];

        if ($scoringMode === 'BY_COMPONENT') {
            $componentJudgeMap = EventFieldComponentJudge::query()
                ->whereIn('event_field_component_id', $componentMap->keys())
                ->get()
                ->groupBy('event_field_component_id')
                ->map(fn ($rows) => $rows->pluck('event_judge_id')->all())
                ->toArray();
        }

        \Log::info('SAVE DRAFT DEBUG', [
            'scoring_mode' => $scoringMode,
            'component_map_count' => $componentMap->count(),
            'component_judge_map' => $componentJudgeMap,
        ]);

        DB::transaction(function () use (
            $data,
            $eventCompetition,
            $eventParticipant,
            $group,
            $scoringMode,
            $componentMap,
            $componentJudgeMap
        ) {
            foreach ($data['rows'] as $row) {
                $judgeId = (int) $row['event_judge_id'];

                // ==========================================
                // Ambil / buat scoresheet
                // (JANGAN pakai updateOrCreate dengan status => 'draft',
                //  karena akan menimpa status submitted/locked)
                // ==========================================
                $scoresheet = EventScoresheet::firstOrNew([
                    'event_competition_id' => $eventCompetition->id,
                    'event_participant_id' => $eventParticipant->id,
                    'event_judge_id'       => $judgeId,
                ]);

                // ==========================================
                // Skip jika sudah submitted / locked
                // (hanya bisa diedit setelah di-revert ke draft)
                // ==========================================
                if ($scoresheet->exists && in_array($scoresheet->status, ['submitted', 'locked'], true)) {
                    continue;
                }

                $scoresheet->fill([
                    'event_group_id'    => $group->id,
                    'event_category_id' => $eventParticipant->event_category_id,
                    'event_team_id'     => $eventParticipant->event_team_id,
                    'scoring_mode'      => $scoringMode,
                    'status'            => 'draft',
                ])->save();

                // ==========================================
                // Reset items
                // ==========================================
                EventScoreItem::where('event_scoresheet_id', $scoresheet->id)->delete();

                $totalScore = 0;

                foreach ($row['items'] as $item) {
                    $componentId = (int) $item['event_field_component_id'];

                    \Log::info('ITEM CHECK', [
                        'judge_id' => $judgeId,
                        'component_id' => $componentId,
                        'allowed_judges' => $componentJudgeMap[$componentId] ?? null,
                    ]);


                    // Komponen bukan milik group
                    if (!$componentMap->has($componentId)) {
                        continue;
                    }

                    // ==========================================
                    // BY_COMPONENT → cek assignment
                    // ==========================================
                    if ($scoringMode === 'BY_COMPONENT') {
                        $allowedJudges = $componentJudgeMap[$componentId] ?? [];

                        if ($scoringMode === 'BY_COMPONENT' && empty($allowedJudges)) {
                            throw new \RuntimeException(
                                "Component {$componentId} has no assigned judges"
                            );
                        }


                        if (!in_array($judgeId, $allowedJudges, true)) {
                            continue;
                        }
                    }

                    $maxScore = $componentMap[$componentId]['max_score'];
                    $weight   = $componentMap[$componentId]['weight'];

                    $score = (float) ($item['score'] ?? 0);
                    if ($score < 0) $score = 0;
                    if ($maxScore > 0) $score = min($score, $maxScore);

                    $weighted = $weight > 0
                        ? round($score * ($weight / 100), 2)
                        : round($score, 2);

                    $totalScore += $weighted;

                    EventScoreItem::create([
                        'event_scoresheet_id'       => $scoresheet->id,
                        'event_field_component_id' => $componentId,
                        'score'                    => $score,
                        'max_score'                => $maxScore,
                        'weight'                   => $weight,
                        'weighted_score'           => $weighted,
                        'notes'                    => $item['notes'] ?? null,
                    ]);
                }

                $scoresheet->update([
                    'total_score' => round($totalScore, 2),
                    'status'      => 'draft',
                ]);
            }
        });

        


        return response()->json([
            'message' => 'Draft penilaian berhasil disimpan.',
        ]);
    }




    /**
     * ============================
     * SUBMIT
     * ============================
     */
    public function submit(Request $request, EventCompetition $eventCompetition)
    {
        $request->validate([
            'event_participant_id' => ['required', 'integer'],
        ]);

        EventScoresheet::where('event_competition_id', $eventCompetition->id)
            ->where('event_participant_id', $request->event_participant_id)
            ->update(['status' => 'submitted']);

        return response()->json([
            'message' => 'Nilai berhasil disubmit.',
        ]);
    }

    /**
     * ============================
     * LOCK
     * ============================
     */
    public function lock(Request $request, EventCompetition $eventCompetition)
    {
        $request->validate([
            'event_participant_id' => ['required', 'integer'],
        ]);

        EventScoresheet::where('event_competition_id', $eventCompetition->id)
            ->where('event_participant_id', $request->event_participant_id)
            ->update(['status' => 'locked']);

        return response()->json([
            'message' => 'Nilai berhasil dikunci.',
        ]);
    }





































    /**
     * ============================
     * REVERT KE DRAFT
     * Hanya superadmin / admin_event.
     * Wajib menyertakan dasar tertulis (Berita Acara / Status Quo).
     * ============================
     */
    public function revertToDraft(Request $request, EventCompetition $eventCompetition)
    {
        if (!$this->canRevert($request->user())) {
            return response()->json([
                'message' => 'Anda tidak memiliki hak untuk mengembalikan status nilai ke draft.',
            ], 403);
        }

        $data = $request->validate([
            'event_participant_id' => ['required', 'integer'],
            'document_type'        => ['required', 'in:berita_acara,status_quo'],
            'document_number'      => ['required', 'string', 'max:100'],
            'document_date'        => ['required', 'date', 'before_or_equal:today'],
            'reason'               => ['required', 'string', 'min:15', 'max:1000'],
            'confirm_document'     => ['accepted'],
        ], [
            'document_type.required'       => 'Jenis dokumen dasar (Berita Acara / Status Quo) wajib dipilih.',
            'document_number.required'     => 'Nomor dokumen wajib diisi.',
            'document_date.required'       => 'Tanggal dokumen wajib diisi.',
            'document_date.before_or_equal'=> 'Tanggal dokumen tidak boleh melebihi hari ini.',
            'reason.required'              => 'Alasan revisi wajib diisi.',
            'reason.min'                   => 'Alasan revisi minimal 15 karakter.',
            'confirm_document.accepted'    => 'Anda harus menyatakan dokumen dasar sudah ada dan diarsipkan.',
        ]);

        // Pastikan peserta memang milik event kompetisi ini
        EventParticipant::query()
            ->where('id', $data['event_participant_id'])
            ->where('event_id', $eventCompetition->event_id)
            ->firstOrFail();

        $affected = DB::transaction(function () use ($request, $eventCompetition, $data) {
            $sheets = EventScoresheet::where('event_competition_id', $eventCompetition->id)
                ->where('event_participant_id', $data['event_participant_id'])
                ->whereIn('status', ['submitted', 'locked'])
                ->lockForUpdate()
                ->get();

            if ($sheets->isEmpty()) {
                return 0;
            }

            $previousStatuses = $sheets->pluck('status')->unique()->values()->all();

            EventScoresheet::whereIn('id', $sheets->pluck('id'))
                ->update(['status' => 'draft']);

            // Jejak audit
            DB::table('event_scoresheet_revert_logs')->insert([
                'event_competition_id' => $eventCompetition->id,
                'event_participant_id' => $data['event_participant_id'],
                'user_id'              => $request->user()->id,
                'document_type'        => $data['document_type'],
                'document_number'      => $data['document_number'],
                'document_date'        => $data['document_date'],
                'reason'               => $data['reason'],
                'previous_status'      => implode(',', $previousStatuses),
                'affected_sheets'      => $sheets->count(),
                'ip_address'           => $request->ip(),
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            \Log::warning('SCORESHEET REVERTED TO DRAFT', [
                'user_id'              => $request->user()->id,
                'event_competition_id' => $eventCompetition->id,
                'event_participant_id' => $data['event_participant_id'],
                'document'             => $data['document_type'] . ' ' . $data['document_number'],
                'sheets'               => $sheets->count(),
            ]);

            return $sheets->count();
        });

        if ($affected === 0) {
            return response()->json([
                'message' => 'Tidak ada nilai berstatus submitted/locked yang dapat dikembalikan.',
            ], 422);
        }

        return response()->json([
            'message' => 'Status nilai berhasil dikembalikan ke draft.',
            'affected_sheets' => $affected,
        ]);
    }

    /**
     * Role yang boleh melakukan revert.
     * Sumber role sama dengan frontend (EventGroups.vue): $user->role->name
     * (contoh nilai: SUPERADMIN, ADMIN_EVENT).
     */
    private const REVERT_ROLES = ['SUPERADMIN', 'ADMIN_EVENT'];

    private function canRevert($user): bool
    {
        $roleName = $user?->role?->name;

        if (!is_string($roleName) || $roleName === '') {
            return false;
        }

        $norm = strtoupper(preg_replace('/[\s-]+/', '_', trim($roleName)));

        return in_array($norm, self::REVERT_ROLES, true);
    }


    // =================================================
    /**
     * Resolve apakah group ini TEAM atau INDIVIDUAL.
     */
    // private function isTeamMode(EventCompetition $competition): bool
    // {
    //     return (bool) EventGroup::query()
    //         ->whereKey($competition->event_group_id)
    //         ->value('is_team');
    // }

    // /**
    //  * Ambil list EP target:
    //  * - INDIVIDUAL: hanya EP itu
    //  * - TEAM: semua EP yang contingent + category + group + event sama
    //  */
    // private function resolveTargetEventParticipantIds(EventCompetition $competition, EventParticipant $ep): array
    // {
    //     $isTeam = $this->isTeamMode($competition);

    //     if (!$isTeam) return [$ep->id];

    //     $contingent = trim((string) $ep->contingent);

    //     // kalau contingent kosong, team tidak bisa dibuat → fallback ke individual
    //     if ($contingent === '') return [$ep->id];

    //     return EventParticipant::query()
    //         ->where('event_id', $competition->event_id)
    //         ->where('event_group_id', $competition->event_group_id)
    //         ->where('event_category_id', $ep->event_category_id)
    //         ->where('contingent', $contingent)
    //         ->pluck('id')
    //         ->map(fn($x) => (int) $x)
    //         ->all();
    // }

    // /**
    //  * FORM
    //  */
    // public function form(Request $request, EventCompetition $competition)
    // {
    //     $validated = $request->validate([
    //         'event_participant_id' => ['required','integer','exists:event_participants,id'],
    //     ]);

    //     $ep = EventParticipant::with(['participant:id,nik,full_name'])
    //         ->findOrFail($validated['event_participant_id']);

    //     if ((int) $ep->event_id !== (int) $competition->event_id) {
    //         return response()->json(['message' => 'Peserta bukan untuk event kompetisi ini.'], 422);
    //     }

    //     $isTeam = $this->isTeamMode($competition);
    //     $targetEpIds = $this->resolveTargetEventParticipantIds($competition, $ep);

    //     // components sesuai event_group di competition
    //     $components = EventFieldComponent::query()
    //         ->where('event_group_id', $competition->event_group_id)
    //         ->orderByRaw('COALESCE(order_number, 999999) asc')
    //         ->orderBy('id')
    //         ->get(['id','event_group_id','field_id','field_name','weight','max_score','order_number']);

    //     $judges = $this->getJudgesForCompetition($competition);

    //     /**
    //      * TEAM MODE:
    //      * scoresheets bisa ada banyak per anggota.
    //      * Namun UI kamu menganggap 1 sheet per judge untuk “tim”.
    //      *
    //      * Strategi aman:
    //      * - Ambil scoresheets milik representative EP (yang dipilih).
    //      * - Karena saveDraft/submit/lock nanti akan menyamakan ke semua anggota,
    //      *   maka data representative sudah cukup sebagai “tampilan form”.
    //      */
    //     $sheetEpIdForView = $ep->id;

    //     $scoresheets = EventScoresheet::query()
    //         ->where('event_competition_id', $competition->id)
    //         ->where('event_participant_id', $sheetEpIdForView)
    //         ->with([
    //             'items:id,event_scoresheet_id,event_field_component_id,score,max_score,weight,weighted_score,notes',
    //         ])
    //         ->get([
    //             'id','event_competition_id','event_group_id','event_category_id',
    //             'event_participant_id','judge_id','total_score','rank_in_round','status','created_at','updated_at'
    //         ]);

    //     return response()->json([
    //         'competition' => [
    //             'id' => $competition->id,
    //             'event_id' => $competition->event_id,
    //             'event_group_id' => $competition->event_group_id,
    //             'round_id' => $competition->round_id,
    //             'full_name' => $competition->full_name,
    //             'status' => $competition->status,
    //             'is_team' => $isTeam,
    //         ],
    //         'participant' => [
    //             'event_participant_id' => $ep->id,
    //             'participant_id' => $ep->participant_id,
    //             'nik' => $ep->participant?->nik,
    //             'full_name' => $ep->participant?->full_name,
    //             'event_group_id' => $ep->event_group_id,
    //             'event_category_id' => $ep->event_category_id,
    //             'contingent' => $ep->contingent,
    //             'team_member_count' => $isTeam ? count($targetEpIds) : 1,
    //         ],
    //         'judges' => $judges,
    //         'components' => $components,
    //         'scoresheets' => $scoresheets,
    //     ]);
    // }

    // /**
    //  * SAVE DRAFT
    //  * - INDIVIDUAL: sama seperti sebelumnya
    //  * - TEAM: apply nilai yang sama ke seluruh anggota tim (targetEpIds)
    //  */
    // public function saveDraft(Request $request, EventCompetition $competition)
    // {
    //     $data = $request->validate([
    //         'event_participant_id' => ['required','integer','exists:event_participants,id'],
    //         'rows' => ['required','array','min:1'],
    //         'rows.*.judge_id' => ['required','integer','exists:users,id'],
    //         'rows.*.items' => ['required','array'],
    //         'rows.*.items.*.event_field_component_id' => ['required','integer','exists:event_field_components,id'],
    //         'rows.*.items.*.score' => ['nullable','numeric','min:0'],
    //         'rows.*.items.*.notes' => ['nullable','string'],
    //     ]);

    //     $ep = EventParticipant::findOrFail($data['event_participant_id']);

    //     if ((int) $ep->event_id !== (int) $competition->event_id) {
    //         return response()->json(['message' => 'Peserta bukan untuk event kompetisi ini.'], 422);
    //     }

    //     // komponen harus sesuai group kompetisi
    //     $componentMap = EventFieldComponent::query()
    //         ->where('event_group_id', $competition->event_group_id)
    //         ->get(['id','weight','max_score'])
    //         ->keyBy('id');

    //     $targetEpIds = $this->resolveTargetEventParticipantIds($competition, $ep);

    //     DB::transaction(function () use ($data, $competition, $ep, $componentMap, $targetEpIds) {
    //         foreach ($data['rows'] as $row) {
    //             $judgeId = (int) $row['judge_id'];

    //             // hitung total sekali (nilai tim/individu sama)
    //             $total = 0;
    //             $normalizedItems = [];

    //             foreach ($row['items'] as $it) {
    //                 $cid = (int) $it['event_field_component_id'];
    //                 if (!$componentMap->has($cid)) continue;

    //                 $comp = $componentMap->get($cid);
    //                 $maxScore = (float) ($comp->max_score ?? 0);
    //                 $weight = (int) ($comp->weight ?? 0);

    //                 $score = (float) ($it['score'] ?? 0);
    //                 if ($score < 0) $score = 0;
    //                 if ($maxScore > 0) $score = min($score, $maxScore);

    //                 $weighted = $weight ? round($score * ($weight / 100), 2) : round($score, 2);
    //                 $total += $weighted;

    //                 $normalizedItems[] = [
    //                     'cid' => $cid,
    //                     'score' => $score,
    //                     'maxScore' => $maxScore,
    //                     'weight' => $weight ?: null,
    //                     'weighted' => $weighted,
    //                     'notes' => $it['notes'] ?? null,
    //                 ];
    //             }

    //             $total = round($total, 2);

    //             // APPLY ke semua EP target (TEAM: banyak, INDIV: 1)
    //             foreach ($targetEpIds as $epId) {
    //                 // ambil event_category_id berdasarkan EP masing-masing (harus sama saat team)
    //                 $epRow = $epId === $ep->id ? $ep : null;

    //                 if (!$epRow) {
    //                     $epRow = EventParticipant::query()
    //                         ->whereKey($epId)
    //                         ->first(['id','event_category_id']);
    //                 }

    //                 $sheet = EventScoresheet::query()->firstOrCreate(
    //                     [
    //                         'event_competition_id' => $competition->id,
    //                         'event_participant_id' => $epId,
    //                         'judge_id' => $judgeId,
    //                     ],
    //                     [
    //                         'event_group_id' => $competition->event_group_id,
    //                         'event_category_id' => $epRow?->event_category_id,
    //                         'total_score' => 0,
    //                         'status' => 'draft',
    //                     ]
    //                 );

    //                 if ($sheet->status === 'locked') {
    //                     continue;
    //                 }

    //                 foreach ($normalizedItems as $ni) {
    //                     EventScoreItem::query()->updateOrCreate(
    //                         [
    //                             'event_scoresheet_id' => $sheet->id,
    //                             'event_field_component_id' => $ni['cid'],
    //                         ],
    //                         [
    //                             'score' => $ni['score'],
    //                             'max_score' => $ni['maxScore'],
    //                             'weight' => $ni['weight'],
    //                             'weighted_score' => $ni['weighted'],
    //                             'notes' => $ni['notes'],
    //                         ]
    //                     );
    //                 }

    //                 $sheet->update([
    //                     'event_group_id' => $competition->event_group_id,
    //                     'event_category_id' => $epRow?->event_category_id,
    //                     'total_score' => $total,
    //                     'status' => 'draft',
    //                 ]);
    //             }
    //         }
    //     });

    //     return response()->json([
    //         'message' => 'Draft tersimpan.',
    //         'applied_to' => count($targetEpIds),
    //     ]);
    // }

    // /**
    //  * SUBMIT
    //  * - TEAM: submit semua EP dalam tim
    //  */
    // public function submit(Request $request, EventCompetition $competition)
    // {
    //     $validated = $request->validate([
    //         'event_participant_id' => ['required','integer','exists:event_participants,id'],
    //     ]);

    //     $ep = EventParticipant::findOrFail($validated['event_participant_id']);

    //     if ((int) $ep->event_id !== (int) $competition->event_id) {
    //         return response()->json(['message' => 'Peserta bukan untuk event kompetisi ini.'], 422);
    //     }

    //     $targetEpIds = $this->resolveTargetEventParticipantIds($competition, $ep);

    //     EventScoresheet::query()
    //         ->where('event_competition_id', $competition->id)
    //         ->whereIn('event_participant_id', $targetEpIds)
    //         ->where('status', '!=', 'locked')
    //         ->update(['status' => 'submitted']);

    //     return response()->json([
    //         'message' => 'Nilai dikirim (submitted).',
    //         'applied_to' => count($targetEpIds),
    //     ]);
    // }

    // /**
    //  * LOCK
    //  * - TEAM: lock semua EP dalam tim
    //  */
    // public function lock(Request $request, EventCompetition $competition)
    // {
    //     $validated = $request->validate([
    //         'event_participant_id' => ['required','integer','exists:event_participants,id'],
    //     ]);

    //     $ep = EventParticipant::findOrFail($validated['event_participant_id']);

    //     if ((int) $ep->event_id !== (int) $competition->event_id) {
    //         return response()->json(['message' => 'Peserta bukan untuk event kompetisi ini.'], 422);
    //     }

    //     $targetEpIds = $this->resolveTargetEventParticipantIds($competition, $ep);

    //     EventScoresheet::query()
    //         ->where('event_competition_id', $competition->id)
    //         ->whereIn('event_participant_id', $targetEpIds)
    //         ->update(['status' => 'locked']);

    //     return response()->json([
    //         'message' => 'Nilai dikunci (locked).',
    //         'applied_to' => count($targetEpIds),
    //     ]);
    // }

    // /**
    //  * Judges resolver (tetap).
    //  */
    // private function getJudgesForCompetition(EventCompetition $competition): array
    // {
    //     $group = EventGroup::query()
    //         ->whereKey($competition->event_group_id)
    //         ->first(['id','event_id','branch_id','use_custom_judges']);

    //     if (!$group) return [];

    //     if ((bool) $group->use_custom_judges) {
    //         return EventGroupJudge::query()
    //             ->where('event_group_id', $group->id)
    //             ->with(['user:id,name'])
    //             ->get(['id','event_group_id','user_id','is_chief'])
    //             ->map(fn($gj) => [
    //                 'id' => $gj->user?->id,
    //                 'name' => $gj->user?->name,
    //                 'is_chief' => (bool) $gj->is_chief,
    //                 'source' => 'group',
    //             ])
    //             ->filter(fn($x) => !empty($x['id']))
    //             ->values()
    //             ->all();
    //     }

    //     $eventBranchId = EventBranch::query()
    //         ->where('event_id', $competition->event_id)
    //         ->where('branch_id', $group->branch_id)
    //         ->value('id');

    //     if (!$eventBranchId) return [];

    //     return EventBranchJudge::query()
    //         ->where('event_branch_id', $eventBranchId)
    //         ->with(['user:id,name'])
    //         ->get(['id','event_branch_id','user_id','is_chief'])
    //         ->map(fn($bj) => [
    //             'id' => $bj->user?->id,
    //             'name' => $bj->user?->name,
    //             'is_chief' => (bool) $bj->is_chief,
    //             'source' => 'branch',
    //         ])
    //         ->filter(fn($x) => !empty($x['id']))
    //         ->values()
    //         ->all();
    // }
}