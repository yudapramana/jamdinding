<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        // Jika simple=1 maka return list sederhana (tanpa pagination)
        if ($request->boolean('simple')) {
            $branches = Branch::orderByRaw('COALESCE(order_number, 9999)')
                ->orderBy('name')
                ->get(['id', 'name', 'code']); // hanya kolom yang diperlukan untuk dropdown

            return response()->json([
                'success' => true,
                'data' => $branches,
            ]);
        }

        // Mode normal (paginated)
        $search  = $request->get('search');
        $perPage = (int) ($request->get('per_page') ?? 10);

        $query = Branch::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $query->orderByRaw('COALESCE(order_number, 9999)')
            ->orderBy('name');

        $branches = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $branches,
        ]);
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'code'         => ['nullable', 'string', 'max:50'],
            'name'         => ['required', 'string', 'max:255'],
            'is_team'      => ['nullable', 'boolean'],
            'order_number' => ['nullable', 'integer', 'min:1'],
            'is_active'    => ['nullable', 'boolean'],
        ]);

        $branch = Branch::create([
            'code'         => $data['code'] ?? null,
            'name'         => $data['name'],
            'is_team'      => $data['is_team'] ?? false,
            'order_number' => $data['order_number'] ?? null,
            'is_active'    => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Branch created successfully.',
            'data'    => $branch,
        ], 201);
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $request->validate([
            'code'         => ['nullable', 'string', 'max:50'],
            'name'         => ['required', 'string', 'max:255'],
            'is_team'      => ['nullable', 'boolean'],
            'order_number' => ['nullable', 'integer', 'min:1'],
            'is_active'    => ['nullable', 'boolean'],
        ]);

        $branch->update([
            'code'         => $data['code'] ?? null,
            'name'         => $data['name'],
            'is_team'      => $data['is_team'] ?? false,
            'order_number' => $data['order_number'] ?? null,
            'is_active'    => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Branch updated successfully.',
            'data'    => $branch,
        ]);
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();

        return response()->json([
            'message' => 'Branch deleted successfully.',
        ]);
    }
}
