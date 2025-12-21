<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventGroup;
use App\Models\Event;
use App\Models\Branch;
use App\Models\Group;
use App\Models\MasterGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class __EventGroupController extends Controller
{
    public function index(Request $request)
    {
        $eventId = $request->get('event_id');
        if (!$eventId) {
            return response()->json([
                'message' => 'event_id is required.',
            ], 422);
        }

        $search     = $request->get('search');
        $perPage    = (int) ($request->get('per_page') ?? 10);
        $branchId   = $request->get('branch_id');        // optional filter
        $simple     = $request->boolean('simple');       // simple list
        $withFields = $request->boolean('with_fields');  // include fieldComponents
        $fromCrud   = $request->boolean('from_crud');    // 👈 PENENTU MODE

        $query = EventGroup::query()
            ->where('event_id', $eventId);

        /**
         * DEFAULT FILTER:
         * jika BUKAN dari halaman CRUD → hanya tampilkan status ACTIVE
         */
        if (!$fromCrud) {
            $query->where('status', 'active');
        }

        // filter optional by branch
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        // search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('branch_name', 'like', "%{$search}%")
                ->orWhere('group_name', 'like', "%{$search}%")
                ->orWhere('full_name', 'like', "%{$search}%");
            });
        }

        // relasi komponen (dipakai di fetchGroupsWithComponents: with_fields=1)
        if ($withFields) {
            $query->with([
                'fieldComponents' => function ($q) {
                    $q->orderByRaw('COALESCE(order_number, 9999)');
                }
            ]);
        }

        // urutan default
        $query->orderByRaw('COALESCE(order_number, 9999)')
            ->orderBy('branch_name')
            ->orderBy('group_name');

        /**
         * SIMPLE MODE
         * dipakai oleh dropdown / selector
         */
        if ($simple) {
            $data = $query->get([
                'id',
                'event_id',
                'branch_id',
                'branch_name',
                'group_name',
                'full_name',
                'max_age',
                'is_team',
                'order_number',
                'status',
            ]);

            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        }

        /**
         * DEFAULT: PAGINATED
         */
        $data = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }




    public function store(Request $request)
    {
        $data = $request->validate([
            'event_id'     => ['required', 'exists:events,id'],
            'branch_id'    => ['required', 'exists:branches,id'],
            'group_id'     => ['required', 'exists:groups,id'],
            'full_name'    => ['nullable', 'string', 'max:255'],
            'max_age'      => ['nullable', 'integer', 'min:0'],
            'status'       => ['nullable', 'in:inactive,active'],
            'is_team'      => ['nullable', 'boolean'],
            'order_number' => ['nullable', 'integer', 'min:1'],
        ]);

        $event  = Event::findOrFail($data['event_id']);
        $branch = Branch::findOrFail($data['branch_id']);
        $group  = Group::findOrFail($data['group_id']);

        // pastikan unik per event + branch + group
        $exists = EventGroup::where('event_id', $event->id)
            ->where('branch_id', $branch->id)
            ->where('group_id', $group->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Kombinasi cabang + golongan ini sudah terdaftar pada event tersebut.',
            ], 422);
        }

        $branchName = $branch->name;
        $groupName  = $group->name;
        $fullName   = $data['full_name'] ?? "{$branchName} - {$groupName}";

        $eventGroup = EventGroup::create([
            'event_id'     => $event->id,
            'branch_id'    => $branch->id,
            'group_id'     => $group->id,
            'branch_name'  => $branchName,
            'group_name'   => $groupName,
            'full_name'    => $fullName,
            'max_age'      => $data['max_age'] ?? 0,
            'status'       => $data['status'] ?? 'active',
            'is_team'      => $data['is_team'] ?? false,
            'order_number' => $data['order_number'] ?? null,
        ]);

        return response()->json([
            'message' => 'Event group created successfully.',
            'data'    => $eventGroup,
        ], 201);
    }

    public function update(Request $request, EventGroup $eventGroup)
    {
        $data = $request->validate([
            'event_id'     => ['nullable', 'exists:events,id'],
            'branch_id'    => ['nullable', 'exists:branches,id'],
            'group_id'     => ['nullable', 'exists:groups,id'],
            'full_name'    => ['nullable', 'string', 'max:255'],
            'max_age'      => ['nullable', 'integer', 'min:0'],
            'status'       => ['nullable', 'in:inactive,active'],
            'is_team'      => ['nullable', 'boolean'],
            'order_number' => ['nullable', 'integer', 'min:1'],
        ]);

        // handle perubahan branch_id / group_id → cek unique
        $branchId = $data['branch_id'] ?? $eventGroup->branch_id;
        $groupId  = $data['group_id'] ?? $eventGroup->group_id;
        $eventId  = $data['event_id'] ?? $eventGroup->event_id;

        if ($branchId != $eventGroup->branch_id || $groupId != $eventGroup->group_id || $eventId != $eventGroup->event_id) {
            $exists = EventGroup::where('event_id', $eventId)
                ->where('branch_id', $branchId)
                ->where('group_id', $groupId)
                ->where('id', '<>', $eventGroup->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Kombinasi cabang + golongan ini sudah terdaftar pada event tersebut.',
                ], 422);
            }
        }

        // jika branch_id diubah, update nama
        if (array_key_exists('branch_id', $data) && $data['branch_id']) {
            $branch = Branch::findOrFail($data['branch_id']);
            $eventGroup->branch_id   = $branch->id;
            $eventGroup->branch_name = $branch->name;
        }

        // jika group_id diubah, update nama
        if (array_key_exists('group_id', $data) && $data['group_id']) {
            $group = Group::findOrFail($data['group_id']);
            $eventGroup->group_id   = $group->id;
            $eventGroup->group_name = $group->name;
        }

        if (array_key_exists('event_id', $data) && $data['event_id']) {
            $eventGroup->event_id = $data['event_id'];
        }

        if (array_key_exists('full_name', $data)) {
            $eventGroup->full_name = $data['full_name'] ?: "{$eventGroup->branch_name} - {$eventGroup->group_name}";
        }
        if (array_key_exists('max_age', $data)) {
            $eventGroup->max_age = $data['max_age'] ?? 0;
        }
        if (array_key_exists('status', $data)) {
            $eventGroup->status = $data['status'];
        }
        if (array_key_exists('is_team', $data)) {
            $eventGroup->is_team = (bool) $data['is_team'];
        }
        if (array_key_exists('order_number', $data)) {
            $eventGroup->order_number = $data['order_number'];
        }

        $eventGroup->save();

        return response()->json([
            'message' => 'Event group updated successfully.',
            'data'    => $eventGroup,
        ]);
    }

    public function destroy(EventGroup $eventGroup)
    {
        $eventGroup->delete();

        return response()->json([
            'message' => 'Event group deleted successfully.',
        ]);
    }

    /**
     * Generate event_groups berdasarkan master_groups.
     */
    public function generateFromTemplate(Request $request)
    {
        $data = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
        ]);

        $event = Event::findOrFail($data['event_id']);

        DB::beginTransaction();

        try {
            $masterGroups = MasterGroup::where('is_active', true)
                ->orderByRaw('COALESCE(order_number, 9999)')
                ->orderBy('full_name')
                ->get();

            if ($masterGroups->isEmpty()) {
                return response()->json([
                    'message' => 'Tidak ada master groups aktif untuk digenerate.',
                ], 422);
            }

            $createdCount = 0;

            foreach ($masterGroups as $mg) {
                $exists = EventGroup::where('event_id', $event->id)
                    ->where('branch_id', $mg->branch_id)
                    ->where('group_id', $mg->group_id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $branchName = $mg->branch_name;
                $groupName  = $mg->group_name;
                $fullName   = $mg->full_name ?? "{$branchName} - {$groupName}";

                EventGroup::create([
                    'event_id'     => $event->id,
                    'branch_id'    => $mg->branch_id,
                    'group_id'     => $mg->group_id,
                    'branch_name'  => $branchName,
                    'group_name'   => $groupName,
                    'full_name'    => $fullName,
                    'max_age'      => $mg->max_age ?? 0,
                    'status'       => 'active',
                    'is_team'      => $mg->is_team ?? false,
                    'order_number' => $mg->order_number,
                ]);

                $createdCount++;
            }

            DB::commit();

            return response()->json([
                'message' => 'Generate event groups dari template berhasil.',
                'created' => $createdCount,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Terjadi kesalahan saat generate golongan dari template.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
