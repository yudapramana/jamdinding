<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventBranch;
use App\Models\EventGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EventJudgePanelController extends Controller
{
    public function index(Request $request, Event $event)
    {
        $perPage = (int) $request->get('per_page', 10);
        $search  = trim((string) $request->get('search', ''));

        $q = EventGroup::query()
            ->where('event_id', $event->id)
            ->with(['customJudges:id,name']) // override judges
            ->orderByRaw('COALESCE(order_number, 999999) asc')
            ->orderBy('id');

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('branch_name', 'like', "%{$search}%")
                  ->orWhere('group_name', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%");
            });
        }

        $paginated = $q->paginate($perPage);

        // Ambil semua event_branches untuk event ini + default judges (tanpa N+1)
        // $eventBranches = EventBranch::query()
        //     ->where('event_id', $event->id)
        //     ->with(['judges:id,name']) // default judges cabang
        //     ->get()
        //     ->keyBy('branch_id');

        $eventBranches = EventBranch::query()
            ->where('event_id', $event->id)
            ->with(['judges' => function ($q) {
                $q->select('users.id', 'users.name'); // kolom user
                $q->withPivot('is_chief');            // ✅ kolom pivot
            }])
            ->get()
            ->keyBy('branch_id');

        // Inject info branch + judges efektif
        $paginated->getCollection()->transform(function (EventGroup $g) use ($eventBranches) {
            $eb = $eventBranches->get($g->branch_id);

            $defaultJudges = $eb?->judges ?? collect();
            $effective     = $g->use_custom_judges ? $g->customJudges : $defaultJudges;

            $mapJudge = fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'is_chief' => (bool) ($u->pivot->is_chief ?? false),
            ];



            return [
                'id' => $g->id,
                'event_id' => $g->event_id,
                'branch_id' => $g->branch_id,
                'group_id' => $g->group_id,
                'branch_name' => $g->branch_name,
                'group_name' => $g->group_name,
                'full_name' => $g->full_name,
                'status' => $g->status,
                'is_team' => (bool) $g->is_team,
                'use_custom_judges' => (bool) $g->use_custom_judges,

                'event_branch_id' => $eb?->id,

                'default_judges' => $defaultJudges->map($mapJudge)->values(),
                'custom_judges'  => $g->customJudges->map($mapJudge)->values(),
                'effective_judges' => $effective->map($mapJudge)->values(),
            ];
        });

        return response()->json($paginated);
    }

    public function getBranchJudges(EventBranch $eventBranch)
    {
        $eventBranch->load(['judges' => function ($q) {
            $q->select('users.id', 'users.name')
              ->withPivot('is_chief');
        }]);

        return response()->json([
            'event_branch_id' => $eventBranch->id,
            'event_id' => $eventBranch->event_id,
            'branch_id' => $eventBranch->branch_id,
            'branch_name' => $eventBranch->branch_name,
            'judges' => $eventBranch->judges->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'is_chief' => (bool) ($u->pivot->is_chief ?? false),
            ])->values(),
        ]);
    }

    public function syncBranchJudges(Request $request, EventBranch $eventBranch)
    {
        $data = $request->validate([
            'judges' => ['required','array','min:1'],
            'judges.*.user_id' => ['required','integer', Rule::exists('users','id')],
            'judges.*.is_chief' => ['nullable','boolean'],
        ]);

        // pastikan hanya 1 chief (opsional tapi disarankan)
        $chiefCount = collect($data['judges'])->filter(fn($j) => !empty($j['is_chief']))->count();
        if ($chiefCount > 1) {
            return response()->json(['message' => 'Ketua majelis hanya boleh 1 orang.'], 422);
        }

        $sync = [];
        foreach ($data['judges'] as $j) {
            $sync[(int)$j['user_id']] = ['is_chief' => (bool)($j['is_chief'] ?? false)];
        }

        DB::transaction(function () use ($eventBranch, $sync) {
            $eventBranch->judges()->sync($sync);
        });

        return response()->json(['message' => 'Hakim cabang berhasil disimpan.']);
    }

    public function getGroupJudges(EventGroup $eventGroup)
    {
        $eventGroup->load(['customJudges' => function ($q) {
            $q->select('users.id', 'users.name')
              ->withPivot('is_chief');
        }]);

        return response()->json([
            'event_group_id' => $eventGroup->id,
            'event_id' => $eventGroup->event_id,
            'branch_id' => $eventGroup->branch_id,
            'group_id' => $eventGroup->group_id,
            'full_name' => $eventGroup->full_name,
            'use_custom_judges' => (bool) $eventGroup->use_custom_judges,
            'judges' => $eventGroup->customJudges->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'is_chief' => (bool) ($u->pivot->is_chief ?? false),
            ])->values(),
        ]);
    }

    public function toggleUseCustom(Request $request, EventGroup $eventGroup)
    {
        $data = $request->validate([
            'use_custom_judges' => ['required','boolean'],
        ]);

        $eventGroup->use_custom_judges = (bool) $data['use_custom_judges'];
        $eventGroup->save();

        return response()->json(['message' => 'Pengaturan override golongan berhasil disimpan.']);
    }

    public function syncGroupJudges(Request $request, EventGroup $eventGroup)
    {
        $data = $request->validate([
            'judges' => ['required','array','min:1'],
            'judges.*.user_id' => ['required','integer', Rule::exists('users','id')],
            'judges.*.is_chief' => ['nullable','boolean'],
        ]);

        $chiefCount = collect($data['judges'])->filter(fn($j) => !empty($j['is_chief']))->count();
        if ($chiefCount > 1) {
            return response()->json(['message' => 'Ketua majelis hanya boleh 1 orang.'], 422);
        }

        $sync = [];
        foreach ($data['judges'] as $j) {
            $sync[(int)$j['user_id']] = ['is_chief' => (bool)($j['is_chief'] ?? false)];
        }

        DB::transaction(function () use ($eventGroup, $sync) {
            $eventGroup->customJudges()->sync($sync);
            // auto set override true (biar user ga lupa)
            $eventGroup->use_custom_judges = true;
            $eventGroup->save();
        });

        return response()->json(['message' => 'Hakim golongan berhasil disimpan.']);
    }
}
