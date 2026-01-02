<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\MedalRule;
use Illuminate\Http\Request;

class MedalRuleController extends Controller
{
    /**
     * GET /api/v1/medal-rules
     * List + search + pagination.
     */
    public function index(Request $request)
    {
        // SIMPLE MODE: untuk dropdown / select
        if ($request->boolean('simple')) {
            $rules = MedalRule::where('is_active', true)
                ->orderBy('order_number')
                ->get([
                    'id',
                    'medal_code',
                    'medal_name',
                    'point',
                    'order_number',
                ]);

            return response()->json([
                'success' => true,
                'data'    => $rules,
            ]);
        }

        // NORMAL MODE (paginated)
        $search  = $request->get('search');
        $perPage = (int) ($request->get('per_page') ?? 10);

        $query = MedalRule::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('medal_name', 'like', "%{$search}%")
                  ->orWhere('medal_code', 'like', "%{$search}%");
            });
        }

        $query->orderBy('order_number');

        $medalRules = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $medalRules,
        ]);
    }

    /**
     * POST /api/v1/medal-rules
     * Store.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'medal_code'   => ['required', 'string', 'max:50', 'unique:medal_rules,medal_code'],
            'medal_name'   => ['required', 'string', 'max:100'],
            'order_number' => ['required', 'integer', 'min:1'],
            'point'        => ['required', 'integer', 'min:0'],
            'is_active'    => ['boolean'],
        ]);

        $medalRule = MedalRule::create([
            'medal_code'   => $data['medal_code'],
            'medal_name'   => $data['medal_name'],
            'order_number' => $data['order_number'],
            'point'        => $data['point'],
            'is_active'    => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Aturan medali berhasil ditambahkan.',
            'data'    => $medalRule,
        ], 201);
    }

    /**
     * PUT /api/v1/medal-rules/{medalRule}
     * Update.
     */
    public function update(Request $request, MedalRule $medalRule)
    {
        $data = $request->validate([
            'medal_code'   => ['required', 'string', 'max:50', 'unique:medal_rules,medal_code,' . $medalRule->id],
            'medal_name'   => ['required', 'string', 'max:100'],
            'order_number' => ['required', 'integer', 'min:1'],
            'point'        => ['required', 'integer', 'min:0'],
            'is_active'    => ['boolean'],
        ]);

        $medalRule->update([
            'medal_code'   => $data['medal_code'],
            'medal_name'   => $data['medal_name'],
            'order_number' => $data['order_number'],
            'point'        => $data['point'],
            'is_active'    => $data['is_active'] ?? $medalRule->is_active,
        ]);

        return response()->json([
            'message' => 'Aturan medali berhasil diperbarui.',
            'data'    => $medalRule,
        ]);
    }

    /**
     * DELETE /api/v1/medal-rules/{medalRule}
     */
    public function destroy(MedalRule $medalRule)
    {
        $medalRule->delete();

        return response()->json([
            'message' => 'Aturan medali berhasil dihapus.',
        ]);
    }
}
