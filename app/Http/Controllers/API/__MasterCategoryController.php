<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasterCategory;
use App\Models\Branch;
use App\Models\Group;
use App\Models\Category;
use Illuminate\Http\Request;

class __MasterCategoryController extends Controller
{
    public function index(Request $request)
    {
        $search  = $request->get('search');
        $perPage = (int) ($request->get('per_page') ?? 10);

        $query = MasterCategory::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('branch_name', 'like', "%{$search}%")
                  ->orWhere('group_name', 'like', "%{$search}%")
                  ->orWhere('category_name', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%");
            });
        }

        $query->orderByRaw('COALESCE(order_number, 9999)')
              ->orderBy('full_name');

        $masterCategories = $query->paginate($perPage);

        return response()->json([
            'data' => $masterCategories,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id'    => ['required', 'exists:branches,id'],
            'group_id'     => ['required', 'exists:groups,id'],
            'category_id'  => ['required', 'exists:categories,id'],
            'order_number' => ['nullable', 'integer', 'min:1'],
            'is_active'    => ['nullable', 'boolean'],
        ]);

        $branch   = Branch::findOrFail($data['branch_id']);
        $group    = Group::findOrFail($data['group_id']);
        $category = Category::findOrFail($data['category_id']);

        $fullName = "{$branch->name} - {$group->name} - {$category->name}";

        $masterCategory = MasterCategory::create([
            'branch_id'     => $branch->id,
            'group_id'      => $group->id,
            'category_id'   => $category->id,
            'branch_name'   => $branch->name,
            'group_name'    => $group->name,
            'category_name' => $category->name,
            'full_name'     => $fullName,
            'order_number'  => $data['order_number'] ?? null,
            'is_active'     => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Master category created successfully.',
            'data'    => $masterCategory,
        ], 201);
    }

    public function update(Request $request, MasterCategory $masterCategory)
    {
        $data = $request->validate([
            'branch_id'    => ['required', 'exists:branches,id'],
            'group_id'     => ['required', 'exists:groups,id'],
            'category_id'  => ['required', 'exists:categories,id'],
            'order_number' => ['nullable', 'integer', 'min:1'],
            'is_active'    => ['nullable', 'boolean'],
        ]);

        $branch   = Branch::findOrFail($data['branch_id']);
        $group    = Group::findOrFail($data['group_id']);
        $category = Category::findOrFail($data['category_id']);

        $fullName = "{$branch->name} - {$group->name} - {$category->name}";

        $masterCategory->update([
            'branch_id'     => $branch->id,
            'group_id'      => $group->id,
            'category_id'   => $category->id,
            'branch_name'   => $branch->name,
            'group_name'    => $group->name,
            'category_name' => $category->name,
            'full_name'     => $fullName,
            'order_number'  => $data['order_number'] ?? null,
            'is_active'     => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Master category updated successfully.',
            'data'    => $masterCategory,
        ]);
    }

    public function destroy(MasterCategory $masterCategory)
    {
        $masterCategory->delete();

        return response()->json([
            'message' => 'Master category deleted successfully.',
        ]);
    }
}
