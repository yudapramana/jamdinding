<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MasterGroup;
use App\Models\Branch;
use App\Models\Group;
use Illuminate\Http\Request;

class __MasterGroupController extends Controller
{
    public function index(Request $request)
    {
        // SIMPLE: untuk dropdown dsb (sudah kita buat sebelumnya)
        if ($request->boolean('simple')) {
            $groups = MasterGroup::orderByRaw('COALESCE(order_number, 9999)')
                ->orderBy('full_name')
                ->get(['id', 'branch_name', 'group_name', 'full_name']);

            return response()->json([
                'success' => true,
                'data' => $groups
            ]);
        }

        $search  = $request->search;
        $perPage = (int) ($request->per_page ?: 10);
        $withFields = $request->boolean('with_fields');

        $query = MasterGroup::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                ->orWhere('branch_name', 'like', "%{$search}%")
                ->orWhere('group_name', 'like', "%{$search}%");
            });
        }

        // kalau with_fields=1 → ikutkan relasi fieldComponents
        if ($withFields) {
            $query->with(['fieldComponents' => function ($q) {
                $q->orderByRaw('COALESCE(default_order, 9999)')
                ->orderBy('field_name');
            }]);
        }

        $data = $query
            ->orderByRaw('COALESCE(order_number, 9999)')
            ->orderBy('full_name')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'group_id' => 'required|exists:groups,id',
            'max_age' => 'required|integer|min:0'
        ]);

        $branch = Branch::find($request->branch_id);
        $group  = Group::find($request->group_id);

        $fullName = "{$branch->name} - {$group->name}";

        $item = MasterGroup::create([
            'branch_id' => $branch->id,
            'group_id'  => $group->id,
            'branch_name' => $branch->name,
            'group_name'  => $group->name,
            'full_name'   => $fullName,
            'max_age' => $request->max_age,
            'is_team' => $request->is_team,
            'order_number' => $request->order_number,
            'is_active' => $request->is_active
        ]);

        return response()->json(['success' => true, 'data' => $item]);
    }

    public function update(Request $request, $id)
    {
        $item = MasterGroup::findOrFail($id);

        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'group_id' => 'required|exists:groups,id',
            'max_age' => 'required|integer|min:0'
        ]);

        $branch = Branch::find($request->branch_id);
        $group  = Group::find($request->group_id);

        $fullName = "{$branch->name} - {$group->name}";

        $item->update([
            'branch_id' => $branch->id,
            'group_id'  => $group->id,
            'branch_name' => $branch->name,
            'group_name'  => $group->name,
            'full_name'   => $fullName,
            'max_age' => $request->max_age,
            'is_team' => $request->is_team,
            'order_number' => $request->order_number,
            'is_active' => $request->is_active
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        MasterGroup::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }
}
