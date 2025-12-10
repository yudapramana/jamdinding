<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class __CategoryController extends Controller
{
    public function index(Request $request)
    {
        // Jika simple=1 → return non-paginated simple list
        if ($request->boolean('simple')) {
            $categories = Category::orderByRaw('COALESCE(order_number, 9999)')
                ->orderBy('name')
                ->get(['id', 'name', 'code']); // kolom minimal untuk dropdown

            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);
        }

        // MODE NORMAL (paginated)
        $search  = $request->get('search');
        $perPage = (int) ($request->get('per_page') ?? 10);

        $query = Category::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $query->orderByRaw('COALESCE(order_number, 9999)')
            ->orderBy('name');

        $categories = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $categories,
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

        $category = Category::create([
            'code'         => $data['code'] ?? null,
            'name'         => $data['name'],
            'order_number' => $data['order_number'] ?? null,
            'is_active'    => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Category created successfully.',
            'data'    => $category,
        ], 201);
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'code'         => ['nullable', 'string', 'max:50'],
            'name'         => ['required', 'string', 'max:255'],
            'order_number' => ['nullable', 'integer', 'min:1'],
            'is_active'    => ['nullable', 'boolean'],
        ]);

        $category->update([
            'code'         => $data['code'] ?? null,
            'name'         => $data['name'],
            'order_number' => $data['order_number'] ?? null,
            'is_active'    => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Category updated successfully.',
            'data'    => $category,
        ]);
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully.',
        ]);
    }
}
