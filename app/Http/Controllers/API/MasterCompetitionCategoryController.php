<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MasterCompetitionCategory;
use Illuminate\Http\Request;

class MasterCompetitionCategoryController extends Controller
{
    /**
     * GET /api/v1/master-competition-categories
     * List + search + pagination
     */
    public function index(Request $request)
    {
        $query = MasterCompetitionCategory::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $categories = $query
            ->ordered()
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data'    => $categories,
        ]);
    }

    /**
     * POST /api/v1/master-competition-categories
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'code'         => 'required|string|max:50|unique:master_competition_categories,code',
            'name'         => 'required|string|max:255',
            'order_number' => 'nullable|integer|min:1',
            'description'  => 'nullable|string',
            'is_active'    => 'boolean',
        ]);

        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        if (empty($data['order_number'])) {
            $data['order_number'] = (MasterCompetitionCategory::max('order_number') ?? 0) + 1;
        }

        $category = MasterCompetitionCategory::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Kategori kompetisi berhasil dibuat.',
            'data'    => $category,
        ], 201);
    }

    /**
     * GET /api/v1/master-competition-categories/{masterCompetitionCategory}
     */
    public function show(MasterCompetitionCategory $masterCompetitionCategory)
    {
        return response()->json([
            'success' => true,
            'data'    => $masterCompetitionCategory,
        ]);
    }

    /**
     * PUT/PATCH /api/v1/master-competition-categories/{masterCompetitionCategory}
     */
    public function update(Request $request, MasterCompetitionCategory $masterCompetitionCategory)
    {
        $data = $request->validate([
            'code'         => 'sometimes|required|string|max:50|unique:master_competition_categories,code,' . $masterCompetitionCategory->id,
            'name'         => 'sometimes|required|string|max:255',
            'order_number' => 'sometimes|integer|min:1',
            'description'  => 'nullable|string',
            'is_active'    => 'boolean',
        ]);

        $masterCompetitionCategory->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Kategori kompetisi berhasil diperbarui.',
            'data'    => $masterCompetitionCategory,
        ]);
    }

    /**
     * DELETE /api/v1/master-competition-categories/{masterCompetitionCategory}
     */
    public function destroy(MasterCompetitionCategory $masterCompetitionCategory)
    {
        $masterCompetitionCategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori kompetisi berhasil dihapus.',
        ]);
    }
}
