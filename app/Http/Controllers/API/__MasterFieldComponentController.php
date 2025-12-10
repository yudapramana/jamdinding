<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasterFieldComponent;
use App\Models\MasterGroup;
use App\Models\ListField;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class __MasterFieldComponentController extends Controller
{
    public function index(Request $request)
    {
        $search  = $request->get('search');
        $perPage = (int) ($request->get('per_page') ?? 10);

        $query = MasterFieldComponent::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('master_group_name', 'like', "%{$search}%")
                  ->orWhere('field_name', 'like', "%{$search}%");
            });
        }

        $query->orderByRaw('COALESCE(default_order, 9999)')
              ->orderBy('master_group_name')
              ->orderBy('field_name');

        $components = $query->paginate($perPage);

        return response()->json([
            'data' => $components,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'master_group_id'   => ['required', 'exists:master_groups,id'],
            'field_id'          => ['required', 'exists:list_fields,id'],
            'default_weight'    => ['nullable', 'integer', 'min:0'],
            'default_max_score' => ['nullable', 'integer', 'min:0'],
            'default_order'     => ['nullable', 'integer', 'min:1'],
            'is_default'        => ['nullable', 'boolean'],
        ]);

        $masterGroup = MasterGroup::findOrFail($data['master_group_id']);
        $field       = ListField::findOrFail($data['field_id']);

        $payload = [
            'master_group_id'   => $masterGroup->id,
            'field_id'          => $field->id,
            'master_group_name' => $masterGroup->full_name ?? $masterGroup->branch_name . ' - ' . $masterGroup->group_name,
            'field_name'        => $field->name,
            'default_weight'    => $data['default_weight'] ?? null,
            'default_max_score' => $data['default_max_score'] ?? null,
            'default_order'     => $data['default_order'] ?? null,
            'is_default'        => $data['is_default'] ?? false,
        ];

        try {
            $component = MasterFieldComponent::create($payload);
        } catch (QueryException $e) {
            // Cek unique constraint
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Kombinasi master group dan field sudah ada.',
                ], 422);
            }
            throw $e;
        }

        return response()->json([
            'message' => 'Master field component created successfully.',
            'data'    => $component,
        ], 201);
    }

    public function update(Request $request, MasterFieldComponent $masterFieldComponent)
    {
        $data = $request->validate([
            'master_group_id'   => ['required', 'exists:master_groups,id'],
            'field_id'          => ['required', 'exists:list_fields,id'],
            'default_weight'    => ['nullable', 'integer', 'min:0'],
            'default_max_score' => ['nullable', 'integer', 'min:0'],
            'default_order'     => ['nullable', 'integer', 'min:1'],
            'is_default'        => ['nullable', 'boolean'],
        ]);

        $masterGroup = MasterGroup::findOrFail($data['master_group_id']);
        $field       = ListField::findOrFail($data['field_id']);

        $payload = [
            'master_group_id'   => $masterGroup->id,
            'field_id'          => $field->id,
            'master_group_name' => $masterGroup->full_name ?? $masterGroup->branch_name . ' - ' . $masterGroup->group_name,
            'field_name'        => $field->name,
            'default_weight'    => $data['default_weight'] ?? null,
            'default_max_score' => $data['default_max_score'] ?? null,
            'default_order'     => $data['default_order'] ?? null,
            'is_default'        => $data['is_default'] ?? false,
        ];

        try {
            $masterFieldComponent->update($payload);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'Kombinasi master group dan field sudah ada.',
                ], 422);
            }
            throw $e;
        }

        return response()->json([
            'message' => 'Master field component updated successfully.',
            'data'    => $masterFieldComponent,
        ]);
    }

    public function destroy(MasterFieldComponent $masterFieldComponent)
    {
        $masterFieldComponent->delete();

        return response()->json([
            'message' => 'Master field component deleted successfully.',
        ]);
    }
}
