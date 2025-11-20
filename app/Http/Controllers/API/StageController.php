<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Stage;
use Illuminate\Http\Request;

class StageController extends Controller
{
    /**
     * GET /api/v1/stages
     * List master stages + search + pagination
     */
    public function index(Request $request)
    {
        $query = Stage::query();

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $stages = $query->ordered()->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'data'    => $stages,
        ]);
    }

    /**
     * POST /api/v1/stages
     * Simpan master stage baru
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'order_number' => 'nullable|integer|min:1',
            'days'         => 'required|integer|min:1',
            'description'  => 'nullable|string',
            'is_active'    => 'boolean',
        ]);

        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        // Jika order_number kosong → ambil max + 1
        if (empty($data['order_number'])) {
            $data['order_number'] = (Stage::max('order_number') ?? 0) + 1;
        }

        $stage = Stage::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Master stage created successfully.',
            'data'    => $stage,
        ], 201);
    }

    /**
     * GET /api/v1/stages/{stage}
     */
    public function show(Stage $stage)
    {
        return response()->json([
            'success' => true,
            'data'    => $stage,
        ]);
    }

    /**
     * PUT/PATCH /api/v1/stages/{stage}
     */
    public function update(Request $request, Stage $stage)
    {
        $data = $request->validate([
            'name'         => 'sometimes|required|string|max:255',
            'order_number' => 'sometimes|integer|min:1',
            'days'         => 'sometimes|integer|min:1',
            'description'  => 'nullable|string',
            'is_active'    => 'boolean',
        ]);

        $stage->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Master stage updated successfully.',
            'data'    => $stage,
        ]);
    }

    /**
     * DELETE /api/v1/stages/{stage}
     */
    public function destroy(Stage $stage)
    {
        $stage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Master stage deleted successfully.',
        ]);
    }
}
