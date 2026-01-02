<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\EventMedalRule;
use App\Models\MedalRule;
use Illuminate\Http\Request;

class EventMedalRuleController extends Controller
{
    /**
     * GET /api/v1/event-medal-rules
     * List + search + pagination (event-based)
     */
    public function index(Request $request)
    {
        $eventId = $request->get('event_id');

        if (!$eventId) {
            return response()->json([
                'success' => false,
                'message' => 'event_id is required.',
            ], 422);
        }

        $search  = $request->get('search');
        $perPage = (int) ($request->get('per_page') ?? 10);

        $query = EventMedalRule::where('event_id', $eventId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('medal_name', 'like', "%{$search}%")
                  ->orWhere('medal_code', 'like', "%{$search}%");
            });
        }

        $query->orderBy('order_number');

        $eventMedalRules = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $eventMedalRules,
        ]);
    }

    /**
     * POST /api/v1/event-medal-rules
     * Store.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'event_id'     => ['required', 'integer', 'exists:events,id'],
            'medal_code'   => ['required', 'string', 'max:50'],
            'medal_name'   => ['required', 'string', 'max:100'],
            'order_number' => ['required', 'integer', 'min:1'],
            'point'        => ['required', 'integer', 'min:0'],
            'is_active'    => ['boolean'],
        ]);

        $rule = EventMedalRule::create([
            'event_id'     => $data['event_id'],
            'medal_code'   => $data['medal_code'],
            'medal_name'   => $data['medal_name'],
            'order_number' => $data['order_number'],
            'point'        => $data['point'],
            'is_active'    => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Aturan medali event berhasil ditambahkan.',
            'data'    => $rule,
        ], 201);
    }

    /**
     * PUT /api/v1/event-medal-rules/{eventMedalRule}
     * Update.
     */
    public function update(Request $request, EventMedalRule $eventMedalRule)
    {
        $data = $request->validate([
            'medal_code'   => ['required', 'string', 'max:50'],
            'medal_name'   => ['required', 'string', 'max:100'],
            'order_number' => ['required', 'integer', 'min:1'],
            'point'        => ['required', 'integer', 'min:0'],
            'is_active'    => ['boolean'],
        ]);

        $eventMedalRule->update([
            'medal_code'   => $data['medal_code'],
            'medal_name'   => $data['medal_name'],
            'order_number' => $data['order_number'],
            'point'        => $data['point'],
            'is_active'    => $data['is_active'] ?? $eventMedalRule->is_active,
        ]);

        return response()->json([
            'message' => 'Aturan medali event berhasil diperbarui.',
            'data'    => $eventMedalRule,
        ]);
    }

    /**
     * DELETE /api/v1/event-medal-rules/{eventMedalRule}
     */
    public function destroy(EventMedalRule $eventMedalRule)
    {
        $eventMedalRule->delete();

        return response()->json([
            'message' => 'Aturan medali event berhasil dihapus.',
        ]);
    }

    /**
     * POST /api/v1/event-medal-rules/generate-from-template
     * Copy dari medal_rules → event_medal_rules
     */
    public function generateFromTemplate(Request $request)
    {
        $data = $request->validate([
            'event_id' => ['required', 'integer', 'exists:events,id'],
        ]);

        $eventId = $data['event_id'];

        // Hapus aturan event lama (reset)
        EventMedalRule::where('event_id', $eventId)->delete();

        // Ambil template aktif
        $rules = MedalRule::where('is_active', true)
            ->orderBy('order_number')
            ->get();

        foreach ($rules as $rule) {
            EventMedalRule::create([
                'event_id'     => $eventId,
                'order_number' => $rule->order_number,
                'medal_code'   => $rule->medal_code,
                'medal_name'   => $rule->medal_name,
                'point'        => $rule->point,
                'is_active'    => true,
            ]);
        }

        return response()->json([
            'message' => 'Aturan medali event berhasil digenerate dari template.',
        ]);
    }
}
