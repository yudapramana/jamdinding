<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Models\Regency;
use App\Models\District;
use App\Models\Village;
use App\Models\EventCompetitionBranch;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * GET /api/v1/provinces
     */
    public function provinces()
    {
        $items = Province::orderBy('name')->get();

        return response()->json([
            'data' => $items,
        ]);
    }

    /**
     * GET /api/v1/regencies?province_id=XX
     */
    public function regencies(Request $request)
    {
        $query = Regency::query();

        if ($request->filled('province_id')) {
            $query->where('province_id', $request->get('province_id'));
        }

        $items = $query->orderBy('name')->get();

        return response()->json([
            'data' => $items,
        ]);
    }

    /**
     * GET /api/v1/districts?regency_id=XX
     */
    public function districts(Request $request)
    {
        $query = District::query();

        if ($request->filled('regency_id')) {
            $query->where('regency_id', $request->get('regency_id'));
        }

        $items = $query->orderBy('name')->get();

        return response()->json([
            'data' => $items,
        ]);
    }

    /**
     * GET /api/v1/villages?district_id=XX
     */
    public function villages(Request $request)
    {
        $query = Village::query();

        if ($request->filled('district_id')) {
            $query->where('district_id', $request->get('district_id'));
        }

        $items = $query->orderBy('name')->get();

        return response()->json([
            'data' => $items,
        ]);
    }

    /**
     * GET /api/v1/event-competition-branches?event_id=XX
     * Dipakai untuk dropdown "Cabang / Golongan Event" di form peserta.
     */
    public function eventBranches(Request $request)
    {
        $query = EventCompetitionBranch::query()
            ->where('is_active', true);

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->get('event_id'));
        }

        $items = $query
            ->orderBy('order_number')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $items,
        ]);
    }
}
