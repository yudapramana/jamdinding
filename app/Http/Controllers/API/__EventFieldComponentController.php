<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EventFieldComponent;
use App\Models\EventGroup;
use App\Models\ListField;
use App\Models\MasterGroup;
use App\Models\MasterFieldComponent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class __EventFieldComponentController extends Controller
{
    public function index(Request $request)
    {
        $eventGroupId = $request->get('event_group_id');
        $search       = $request->get('search');
        $perPage      = (int) ($request->get('per_page') ?? 10);

        $query = EventFieldComponent::query()
            ->with('eventGroup', 'field');

        if ($eventGroupId) {
            $query->where('event_group_id', $eventGroupId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('field_name', 'like', "%{$search}%")
                  ->orWhere('event_group_name', 'like', "%{$search}%");
            });
        }

        $query->orderByRaw('COALESCE(order_number, 9999)')
              ->orderBy('field_name');

        $data = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_group_id' => 'required|exists:event_groups,id',
            'field_id'       => 'required|exists:list_fields,id',
            'weight'         => 'nullable|integer|min:0',
            'max_score'      => 'nullable|integer|min:0',
            'order_number'   => 'nullable|integer|min:1',
        ]);

        $eventGroup = EventGroup::findOrFail($validated['event_group_id']);
        $field      = ListField::findOrFail($validated['field_id']);

        // jangan duplikasi
        $exists = EventFieldComponent::where('event_group_id', $eventGroup->id)
            ->where('field_id', $field->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Komponen ini sudah terdaftar pada golongan event tersebut.',
            ], 422);
        }

        $data = EventFieldComponent::create([
            'event_group_id'   => $eventGroup->id,
            'field_id'         => $field->id,
            'event_group_name' => $eventGroup->full_name ?? ($eventGroup->branch_name . ' - ' . $eventGroup->group_name),
            'field_name'       => $field->name,
            'weight'           => $request->input('weight'),
            'max_score'        => $request->input('max_score'),
            'order_number'     => $request->input('order_number'),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $component = EventFieldComponent::findOrFail($id);

        $validated = $request->validate([
            'event_group_id' => 'required|exists:event_groups,id',
            'field_id'       => 'required|exists:list_fields,id',
            'weight'         => 'nullable|integer|min:0',
            'max_score'      => 'nullable|integer|min:0',
            'order_number'   => 'nullable|integer|min:1',
        ]);

        $eventGroup = EventGroup::findOrFail($validated['event_group_id']);
        $field      = ListField::findOrFail($validated['field_id']);

        $exists = EventFieldComponent::where('event_group_id', $eventGroup->id)
            ->where('field_id', $field->id)
            ->where('id', '<>', $component->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Komponen ini sudah terdaftar pada golongan event tersebut.',
            ], 422);
        }

        $component->event_group_id   = $eventGroup->id;
        $component->field_id         = $field->id;
        $component->event_group_name = $eventGroup->full_name ?? ($eventGroup->branch_name . ' - ' . $eventGroup->group_name);
        $component->field_name       = $field->name;
        $component->weight           = $request->input('weight');
        $component->max_score        = $request->input('max_score');
        $component->order_number     = $request->input('order_number');
        $component->save();

        return response()->json([
            'success' => true,
            'data'    => $component,
        ]);
    }

    public function destroy($id)
    {
        $component = EventFieldComponent::findOrFail($id);
        $component->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Generate event_field_components dari master_field_components
     * berdasarkan pasangan branch_id + group_id.
     */
    public function generateFromTemplate(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
        ]);

        $eventId = $validated['event_id'];

        $eventGroups = EventGroup::where('event_id', $eventId)->get();

        if ($eventGroups->isEmpty()) {
            return response()->json([
                'message' => 'Belum ada Event Group untuk event ini. Generate Event Groups terlebih dahulu.',
            ], 422);
        }

        $created = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            foreach ($eventGroups as $eg) {
                $masterGroup = MasterGroup::where('branch_id', $eg->branch_id)
                    ->where('group_id', $eg->group_id)
                    ->where('is_active', true)
                    ->first();

                if (!$masterGroup) {
                    $skipped++;
                    continue;
                }

                $masterComponents = MasterFieldComponent::where('master_group_id', $masterGroup->id)->get();

                foreach ($masterComponents as $mc) {
                    $exists = EventFieldComponent::where('event_group_id', $eg->id)
                        ->where('field_id', $mc->field_id)
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    EventFieldComponent::create([
                        'event_group_id'   => $eg->id,
                        'field_id'         => $mc->field_id,
                        'event_group_name' => $eg->full_name ?? ($eg->branch_name . ' - ' . $eg->group_name),
                        'field_name'       => $mc->field_name,
                        'weight'           => $mc->default_weight,
                        'max_score'        => $mc->default_max_score,
                        'order_number'     => $mc->default_order,
                    ]);

                    $created++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Generate selesai. Dibuat: {$created}, dilewati (sudah ada / tanpa master): {$skipped}.",
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Terjadi kesalahan saat generate komponen dari template.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
