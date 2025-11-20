<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasterCompetitionBranch;
use Illuminate\Http\Request;

class MasterCompetitionBranchController extends Controller
{
    /**
     * GET /api/v1/master-competition-branches
     * List + search + pagination
     */
    public function index(Request $request)
    {
        $query = MasterCompetitionBranch::with(['group', 'category']);

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
     * POST /api/v1/master-competition-branches
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'code'                          => 'required|string|max:50|unique:master_competition_branches,code',
            'master_competition_group_id'   => 'required|integer|exists:master_competition_groups,id',
            'master_competition_category_id'=> 'required|integer|exists:master_competition_categories,id',
            'type'                          => 'required|in:Putra,Putri',
            'format'                        => 'required|in:individu,grup',
            'name'                          => 'required|string|max:255',
            'max_age'                       => 'nullable|integer|min:0',
            'order_number'                  => 'nullable|integer|min:1',
            'description'                   => 'nullable|string',
            'is_active'                     => 'boolean',
        ]);

        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        if (!isset($data['max_age'])) {
            $data['max_age'] = 0;
        }

        if (empty($data['order_number'])) {
            $data['order_number'] = (MasterCompetitionBranch::max('order_number') ?? 0) + 1;
        }

        $branch = MasterCompetitionBranch::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Cabang/golongan kompetisi berhasil dibuat.',
            'data'    => $branch->load(['group', 'category']),
        ], 201);
    }

    /**
     * GET /api/v1/master-competition-branches/{branch}
     */
    public function show(MasterCompetitionBranch $masterCompetitionBranch)
    {
        $masterCompetitionBranch->load(['group', 'category']);

        return response()->json([
            'success' => true,
            'data'    => $masterCompetitionBranch,
        ]);
    }

    /**
     * PUT/PATCH /api/v1/master-competition-branches/{branch}
     */
    public function update(Request $request, MasterCompetitionBranch $masterCompetitionBranch)
    {
        $data = $request->validate([
            'code'                          => 'sometimes|required|string|max:50|unique:master_competition_branches,code,' . $masterCompetitionBranch->id,
            'master_competition_group_id'   => 'sometimes|required|integer|exists:master_competition_groups,id',
            'master_competition_category_id'=> 'sometimes|required|integer|exists:master_competition_categories,id',
            'type'                          => 'sometimes|required|in:Putra,Putri',
            'format'                        => 'sometimes|required|in:individu,grup',
            'name'                          => 'sometimes|required|string|max:255',
            'max_age'                       => 'nullable|integer|min:0',
            'order_number'                  => 'sometimes|integer|min:1',
            'description'                   => 'nullable|string',
            'is_active'                     => 'boolean',
        ]);

        $masterCompetitionBranch->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Cabang/golongan kompetisi berhasil diperbarui.',
            'data'    => $masterCompetitionBranch->load(['group', 'category']),
        ]);
    }

    /**
     * DELETE /api/v1/master-competition-branches/{branch}
     */
    public function destroy(MasterCompetitionBranch $masterCompetitionBranch)
    {
        $masterCompetitionBranch->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cabang/golongan kompetisi berhasil dihapus.',
        ]);
    }
}
