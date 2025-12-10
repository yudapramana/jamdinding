<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventCategory;
use App\Models\Event;
use App\Models\Branch;
use App\Models\Group;
use App\Models\Category;
use App\Models\MasterCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class __EventCategoryController extends Controller
{
    public function index(Request $request)
    {
        $eventId = $request->get('event_id');
        if (!$eventId) {
            return response()->json([
                'message' => 'event_id is required.',
            ], 422);
        }

        $search   = $request->get('search');
        $perPage  = (int) ($request->get('per_page') ?? 10);
        $branchId = $request->get('branch_id'); // 👈
        $groupId  = $request->get('group_id');  // 👈

        $query = EventCategory::where('event_id', $eventId);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        if ($groupId) {
            $query->where('group_id', $groupId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('branch_name', 'like', "%{$search}%")
                ->orWhere('group_name', 'like', "%{$search}%")
                ->orWhere('category_name', 'like', "%{$search}%")
                ->orWhere('full_name', 'like', "%{$search}%");
            });
        }

        $query->orderByRaw('COALESCE(order_number, 9999)')
            ->orderBy('branch_name')
            ->orderBy('group_name')
            ->orderBy('category_name');

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
            'category_id'  => ['required', 'exists:categories,id'],
            'full_name'    => ['nullable', 'string', 'max:255'],
            'status'       => ['nullable', 'in:inactive,active'],
            'order_number' => ['nullable', 'integer', 'min:1'],
        ]);

        $event    = Event::findOrFail($data['event_id']);
        $branch   = Branch::findOrFail($data['branch_id']);
        $group    = Group::findOrFail($data['group_id']);
        $category = Category::findOrFail($data['category_id']);

        $exists = EventCategory::where('event_id', $event->id)
            ->where('branch_id', $branch->id)
            ->where('group_id', $group->id)
            ->where('category_id', $category->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Kombinasi cabang + golongan + kategori ini sudah terdaftar pada event tersebut.',
            ], 422);
        }

        $branchName   = $branch->name;
        $groupName    = $group->name;
        $categoryName = $category->name;

        $fullName = $data['full_name'] ?? "{$branchName} - {$groupName} - {$categoryName}";

        $eventCategory = EventCategory::create([
            'event_id'      => $event->id,
            'branch_id'     => $branch->id,
            'group_id'      => $group->id,
            'category_id'   => $category->id,
            'branch_name'   => $branchName,
            'group_name'    => $groupName,
            'category_name' => $categoryName,
            'full_name'     => $fullName,
            'status'        => $data['status'] ?? 'active',
            'order_number'  => $data['order_number'] ?? null,
        ]);

        return response()->json([
            'message' => 'Event category created successfully.',
            'data'    => $eventCategory,
        ], 201);
    }

    public function update(Request $request, EventCategory $eventCategory)
    {
        $data = $request->validate([
            'event_id'     => ['nullable', 'exists:events,id'],
            'branch_id'    => ['nullable', 'exists:branches,id'],
            'group_id'     => ['nullable', 'exists:groups,id'],
            'category_id'  => ['nullable', 'exists:categories,id'],
            'full_name'    => ['nullable', 'string', 'max:255'],
            'status'       => ['nullable', 'in:inactive,active'],
            'order_number' => ['nullable', 'integer', 'min:1'],
        ]);

        $eventId    = $data['event_id'] ?? $eventCategory->event_id;
        $branchId   = $data['branch_id'] ?? $eventCategory->branch_id;
        $groupId    = $data['group_id'] ?? $eventCategory->group_id;
        $categoryId = $data['category_id'] ?? $eventCategory->category_id;

        if ($eventId != $eventCategory->event_id ||
            $branchId != $eventCategory->branch_id ||
            $groupId != $eventCategory->group_id ||
            $categoryId != $eventCategory->category_id
        ) {
            $exists = EventCategory::where('event_id', $eventId)
                ->where('branch_id', $branchId)
                ->where('group_id', $groupId)
                ->where('category_id', $categoryId)
                ->where('id', '<>', $eventCategory->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Kombinasi cabang + golongan + kategori ini sudah terdaftar pada event tersebut.',
                ], 422);
            }
        }

        if (array_key_exists('event_id', $data) && $data['event_id']) {
            $eventCategory->event_id = $data['event_id'];
        }

        if (array_key_exists('branch_id', $data) && $data['branch_id']) {
            $branch = Branch::findOrFail($data['branch_id']);
            $eventCategory->branch_id   = $branch->id;
            $eventCategory->branch_name = $branch->name;
        }

        if (array_key_exists('group_id', $data) && $data['group_id']) {
            $group = Group::findOrFail($data['group_id']);
            $eventCategory->group_id   = $group->id;
            $eventCategory->group_name = $group->name;
        }

        if (array_key_exists('category_id', $data) && $data['category_id']) {
            $category = Category::findOrFail($data['category_id']);
            $eventCategory->category_id   = $category->id;
            $eventCategory->category_name = $category->name;
        }

        if (array_key_exists('full_name', $data)) {
            $eventCategory->full_name = $data['full_name']
                ?: "{$eventCategory->branch_name} - {$eventCategory->group_name} - {$eventCategory->category_name}";
        }

        if (array_key_exists('status', $data)) {
            $eventCategory->status = $data['status'];
        }

        if (array_key_exists('order_number', $data)) {
            $eventCategory->order_number = $data['order_number'];
        }

        $eventCategory->save();

        return response()->json([
            'message' => 'Event category updated successfully.',
            'data'    => $eventCategory,
        ]);
    }

    public function destroy(EventCategory $eventCategory)
    {
        $eventCategory->delete();

        return response()->json([
            'message' => 'Event category deleted successfully.',
        ]);
    }

    public function generateFromTemplate(Request $request)
    {
        $data = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
        ]);

        $event = Event::findOrFail($data['event_id']);

        DB::beginTransaction();

        try {
            $masterCategories = MasterCategory::where('is_active', true)
                ->orderByRaw('COALESCE(order_number, 9999)')
                ->orderBy('full_name')
                ->get();

            if ($masterCategories->isEmpty()) {
                return response()->json([
                    'message' => 'Tidak ada master categories aktif untuk digenerate.',
                ], 422);
            }

            $createdCount = 0;

            foreach ($masterCategories as $mc) {
                $exists = EventCategory::where('event_id', $event->id)
                    ->where('branch_id', $mc->branch_id)
                    ->where('group_id', $mc->group_id)
                    ->where('category_id', $mc->category_id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $branchName   = $mc->branch_name;
                $groupName    = $mc->group_name;
                $categoryName = $mc->category_name;
                $fullName     = $mc->full_name ?? "{$branchName} - {$groupName} - {$categoryName}";

                EventCategory::create([
                    'event_id'      => $event->id,
                    'branch_id'     => $mc->branch_id,
                    'group_id'      => $mc->group_id,
                    'category_id'   => $mc->category_id,
                    'branch_name'   => $branchName,
                    'group_name'    => $groupName,
                    'category_name' => $categoryName,
                    'full_name'     => $fullName,
                    'status'        => 'active',
                    'order_number'  => $mc->order_number,
                ]);

                $createdCount++;
            }

            DB::commit();

            return response()->json([
                'message' => 'Generate event categories dari template berhasil.',
                'created' => $createdCount,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Terjadi kesalahan saat generate kategori dari template.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
