<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MasterCompetitionGroup;
use Illuminate\Http\Request;

class MasterCompetitionGroupController extends Controller
{
    /**
     * GET /api/v1/master-competition-groups
     * List + search + pagination
     */
    public function index(Request $request)
    {
        $query = MasterCompetitionGroup::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $groups = $query
            ->orderBy('order_number')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data'    => $groups,
        ]);
    }

    /**
     * POST /api/v1/master-competition-groups
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'code'         => 'required|string|max:50|unique:master_competition_groups,code',
            'name'         => 'required|string|max:255',
            'order_number' => 'nullable|integer|min:1',
            'description'  => 'nullable|string',
            'is_active'    => 'boolean',
        ]);

        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        if (empty($data['order_number'])) {
            $data['order_number'] = (MasterCompetitionGroup::max('order_number') ?? 0) + 1;
        }

        $group = MasterCompetitionGroup::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Group cabang kompetisi berhasil dibuat.',
            'data'    => $group,
        ], 201);
    }

    /**
     * GET /api/v1/master-competition-groups/{group}
     */
    public function show(MasterCompetitionGroup $masterCompetitionGroup)
    {
        return response()->json([
            'success' => true,
            'data'    => $masterCompetitionGroup,
        ]);
    }

    /**
     * PUT/PATCH /api/v1/master-competition-groups/{group}
     */
    public function update(Request $request, MasterCompetitionGroup $masterCompetitionGroup)
    {
        $data = $request->validate([
            'code'         => 'sometimes|required|string|max:50|unique:master_competition_groups,code,' . $masterCompetitionGroup->id,
            'name'         => 'sometimes|required|string|max:255',
            'order_number' => 'sometimes|integer|min:1',
            'description'  => 'nullable|string',
            'is_active'    => 'boolean',
        ]);

        $masterCompetitionGroup->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Group cabang kompetisi berhasil diperbarui.',
            'data'    => $masterCompetitionGroup,
        ]);
    }

    /**
     * DELETE /api/v1/master-competition-groups/{group}
     */
    public function destroy(MasterCompetitionGroup $masterCompetitionGroup)
    {
        $masterCompetitionGroup->delete();

        return response()->json([
            'success' => true,
            'message' => 'Group cabang kompetisi berhasil dihapus.',
        ]);
    }
}
