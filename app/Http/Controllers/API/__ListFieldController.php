<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ListField;
use Illuminate\Http\Request;

class __ListFieldController extends Controller
{
    /**
     * GET /api/v1/list-fields
     * List + search + pagination.
     */
    public function index(Request $request)
    {
        // SIMPLE MODE: tanpa pagination, untuk dropdown
        if ($request->boolean('simple')) {
            $fields = ListField::orderByRaw('COALESCE(order_number, 9999)')
                ->orderBy('name')
                ->get(['id', 'name', 'code']);

            return response()->json([
                'success' => true,
                'data' => $fields,
            ]);
        }

        // NORMAL MODE (paginated)
        $search  = $request->get('search');
        $perPage = (int) ($request->get('per_page') ?? 10);

        $query = ListField::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $query->orderByRaw('COALESCE(order_number, 9999)')
            ->orderBy('name');

        $listFields = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $listFields,
        ]);
    }


    /**
     * POST /api/v1/list-fields
     * Store.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'code'         => ['nullable', 'string', 'max:50'],
            'name'         => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'order_number' => ['nullable', 'integer', 'min:1'],
        ]);

        $listField = ListField::create([
            'code'         => $data['code'] ?? null,
            'name'         => $data['name'],
            'description'  => $data['description'] ?? null,
            'order_number' => $data['order_number'] ?? null,
        ]);

        return response()->json([
            'message' => 'List field created successfully.',
            'data'    => $listField,
        ], 201);
    }

    /**
     * PUT /api/v1/list-fields/{listField}
     * Update.
     */
    public function update(Request $request, ListField $listField)
    {
        $data = $request->validate([
            'code'         => ['nullable', 'string', 'max:50'],
            'name'         => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'order_number' => ['nullable', 'integer', 'min:1'],
        ]);

        $listField->update([
            'code'         => $data['code'] ?? null,
            'name'         => $data['name'],
            'description'  => $data['description'] ?? null,
            'order_number' => $data['order_number'] ?? null,
        ]);

        return response()->json([
            'message' => 'List field updated successfully.',
            'data'    => $listField,
        ]);
    }

    /**
     * DELETE /api/v1/list-fields/{listField}
     */
    public function destroy(ListField $listField)
    {
        $listField->delete();

        return response()->json([
            'message' => 'List field deleted successfully.',
        ]);
    }
}
