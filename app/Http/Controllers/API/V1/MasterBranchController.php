<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\MasterBranch;
use Illuminate\Http\Request;

class MasterBranchController extends Controller
{
    /**
     * GET /api/v1/master-branches
     * List + search + pagination.
     */
    public function index(Request $request)
    {
        $search  = $request->get('search');
        $perPage = (int) ($request->get('per_page') ?? 10);

        $query = MasterBranch::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('branch_name', 'like', "%{$search}%")
                  ->orWhere('full_name', 'like', "%{$search}%");
            });
        }

        $query->orderByRaw('COALESCE(order_number, 9999)')
              ->orderBy('full_name');

        $masterBranches = $query->paginate($perPage);

        return response()->json([
            'data' => $masterBranches,
        ]);
    }

    /**
     * POST /api/v1/master-branches
     * Store.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id'    => ['required', 'exists:branches,id'],
            'branch_name'  => ['required', 'string', 'max:255'],
            'full_name'    => ['required', 'string', 'max:255'],
            'order_number' => ['nullable', 'integer', 'min:1'],
            'is_active'    => ['nullable', 'boolean'],
        ]);

        $masterBranch = MasterBranch::create([
            'branch_id'    => $data['branch_id'],
            'branch_name'  => $data['branch_name'],
            'full_name'    => $data['full_name'],
            'order_number' => $data['order_number'] ?? null,
            'is_active'    => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Master branch created successfully.',
            'data'    => $masterBranch,
        ], 201);
    }

    /**
     * PUT /api/v1/master-branches/{masterBranch}
     * Update.
     */
    public function update(Request $request, MasterBranch $masterBranch)
    {
        $data = $request->validate([
            'branch_id'    => ['required', 'exists:branches,id'],
            'branch_name'  => ['required', 'string', 'max:255'],
            'full_name'    => ['required', 'string', 'max:255'],
            'order_number' => ['nullable', 'integer', 'min:1'],
            'is_active'    => ['nullable', 'boolean'],
        ]);

        $masterBranch->update([
            'branch_id'    => $data['branch_id'],
            'branch_name'  => $data['branch_name'],
            'full_name'    => $data['full_name'],
            'order_number' => $data['order_number'] ?? null,
            'is_active'    => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Master branch updated successfully.',
            'data'    => $masterBranch,
        ]);
    }

    /**
     * DELETE /api/v1/master-branches/{masterBranch}
     */
    public function destroy(MasterBranch $masterBranch)
    {
        $masterBranch->delete();

        return response()->json([
            'message' => 'Master branch deleted successfully.',
        ]);
    }
}
