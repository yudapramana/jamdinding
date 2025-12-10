<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EventCompetitionBranch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Event;
use App\Models\MasterCompetitionBranch;
use Illuminate\Support\Facades\DB;

class EventCompetitionBranchController extends Controller
{
    /**
     * GET /api/v1/event-competition-branches
     * List + search + filter event + pagination
     */
    public function index(Request $request)
    {
        $query = EventCompetitionBranch::with(['event', 'group', 'category', 'masterBranch']);

        // wajib ada event_id kalau mau spesifik per event
        if ($eventId = $request->get('event_id')) {
            $query->where('event_id', $eventId);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($groupId = $request->get('group_id')) {
            $query->where('master_competition_group_id', $groupId);
        }

        if ($categoryId = $request->get('category_id')) {
            $query->where('master_competition_category_id', $categoryId);
        }

        $branches = $query
            ->ordered()
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data'    => $branches,
        ]);
    }

    /**
     * POST /api/v1/event-competition-branches
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'event_id'                     => 'required|integer|exists:events,id',
            'master_competition_branch_id' => 'nullable|integer|exists:master_competition_branches,id',
            'code'                         => [
                'required',
                'string',
                'max:100',
                // unique per event
                Rule::unique('event_competition_branches')->where(function ($q) use ($request) {
                    return $q->where('event_id', $request->event_id);
                }),
            ],
            'master_competition_group_id'    => 'nullable|integer|exists:master_competition_groups,id',
            'master_competition_category_id' => 'nullable|integer|exists:master_competition_categories,id',
            'type'                           => 'required|in:Putra,Putri',
            'format'                         => 'required|in:individu,grup',
            'name'                           => 'required|string|max:255',
            'max_age'                        => 'nullable|integer|min:0',
            'order_number'                   => 'nullable|integer|min:1',
            'description'                    => 'nullable|string',
            'is_active'                      => 'boolean',
        ]);

        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        if (!isset($data['max_age'])) {
            $data['max_age'] = 0;
        }

        if (empty($data['order_number'])) {
            $data['order_number'] = (EventCompetitionBranch::where('event_id', $data['event_id'])->max('order_number') ?? 0) + 1;
        }

        $branch = EventCompetitionBranch::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Cabang/golongan kompetisi event berhasil dibuat.',
            'data'    => $branch->load(['event', 'group', 'category', 'masterBranch']),
        ], 201);
    }

    /**
     * GET /api/v1/event-competition-branches/{eventCompetitionBranch}
     */
    public function show(EventCompetitionBranch $eventCompetitionBranch)
    {
        $eventCompetitionBranch->load(['event', 'group', 'category', 'masterBranch']);

        return response()->json([
            'success' => true,
            'data'    => $eventCompetitionBranch,
        ]);
    }

    /**
     * PUT/PATCH /api/v1/event-competition-branches/{eventCompetitionBranch}
     */
    public function update(Request $request, EventCompetitionBranch $eventCompetitionBranch)
    {
        $data = $request->validate([
            'event_id'                     => 'sometimes|required|integer|exists:events,id',
            'master_competition_branch_id' => 'nullable|integer|exists:master_competition_branches,id',
            'code'                         => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('event_competition_branches')
                    ->ignore($eventCompetitionBranch->id)
                    ->where(function ($q) use ($request, $eventCompetitionBranch) {
                        $eventId = $request->event_id ?? $eventCompetitionBranch->event_id;
                        return $q->where('event_id', $eventId);
                    }),
            ],
            'master_competition_group_id'    => 'nullable|integer|exists:master_competition_groups,id',
            'master_competition_category_id' => 'nullable|integer|exists:master_competition_categories,id',
            'type'                           => 'sometimes|required|in:Putra,Putri',
            'format'                         => 'sometimes|required|in:individu,grup',
            'name'                           => 'sometimes|required|string|max:255',
            'max_age'                        => 'nullable|integer|min:0',
            'order_number'                   => 'sometimes|integer|min:1',
            'description'                    => 'nullable|string',
            'is_active'                      => 'boolean',
        ]);

        $eventCompetitionBranch->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cabang/golongan kompetisi event berhasil diperbarui.',
            'data'    => $eventCompetitionBranch->load(['event', 'group', 'category', 'masterBranch']),
        ]);
    }

    /**
     * DELETE /api/v1/event-competition-branches/{eventCompetitionBranch}
     */
    public function destroy(EventCompetitionBranch $eventCompetitionBranch)
    {
        $eventCompetitionBranch->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cabang/golongan kompetisi event berhasil dihapus.',
        ]);
    }

    public function generateFromMaster(Event $event)
    {
        // Biar aman, bungkus dalam transaction
        DB::beginTransaction();

        try {
            // Optional: hapus dulu cabang event lama agar tidak dobel
            EventCompetitionBranch::where('event_id', $event->id)->delete();

            // Ambil semua master cabang yang aktif, diurutkan
            $masterBranches = MasterCompetitionBranch::with(['group', 'category'])
                ->where('is_active', true)
                ->ordered()
                ->get();

            $created = [];

            foreach ($masterBranches as $branch) {
                $created[] = EventCompetitionBranch::create([
                    'event_id'                     => $event->id,
                    'master_competition_branch_id' => $branch->id,
                    'code'                         => $event->event_key ."-". $branch->code, // unik per event
                    'master_competition_group_id'  => $branch->master_competition_group_id,
                    'master_competition_category_id'=> $branch->master_competition_category_id,
                    'type'                         => $branch->type,
                    'format'                       => $branch->format,
                    'name'                         => $branch->name,
                    'max_age'                      => $branch->max_age,
                    'order_number'                 => $branch->order_number,
                    'description'                  => $branch->description,
                    'is_active'                    => $branch->is_active,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cabang/golongan event berhasil digenerate dari template master.',
                'data'    => $created,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal generate cabang dari master: ' . $e->getMessage(),
            ], 500);
        }
    }

}
