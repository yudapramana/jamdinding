<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index(Request $request)
    {
        // Jika simple=1 maka return list sederhana (tanpa pagination)
        if ($request->boolean('simple')) {
            $groups = Group::orderByRaw('COALESCE(order_number, 9999)')
                ->orderBy('name')
                ->get(['id', 'name', 'code']); // untuk dropdown / simple list

            return response()->json([
                'success' => true,
                'data' => $groups,
            ]);
        }

        // MODE PAGINATION NORMAL
        $search  = $request->get('search');
        $perPage = (int) ($request->get('per_page') ?? 10);

        $query = Group::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $query->orderByRaw('COALESCE(order_number, 9999)')
            ->orderBy('name');

        $groups = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $groups,
        ]);
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'code'         => ['nullable', 'string', 'max:50'],
            'name'         => ['required', 'string', 'max:255'],
            'order_number' => ['nullable', 'integer', 'min:1'],
            'is_active'    => ['nullable', 'boolean'],
        ]);

        $group = Group::create([
            'code'         => $data['code'] ?? null,
            'name'         => $data['name'],
            'order_number' => $data['order_number'] ?? null,
            'is_active'    => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Group created successfully.',
            'data'    => $group,
        ], 201);
    }

    public function update(Request $request, Group $group)
    {
        $data = $request->validate([
            'code'         => ['nullable', 'string', 'max:50'],
            'name'         => ['required', 'string', 'max:255'],
            'order_number' => ['nullable', 'integer', 'min:1'],
            'is_active'    => ['nullable', 'boolean'],
        ]);

        $group->update([
            'code'         => $data['code'] ?? null,
            'name'         => $data['name'],
            'order_number' => $data['order_number'] ?? null,
            'is_active'    => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Group updated successfully.',
            'data'    => $group,
        ]);
    }

    public function destroy(Group $group)
    {
        $group->delete();

        return response()->json([
            'message' => 'Group deleted successfully.',
        ]);
    }
}
