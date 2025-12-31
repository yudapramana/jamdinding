<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicEventController extends Controller
{
    /**
     * Ambil daftar event yang akan muncul di landing page.
     */
    public function index(Request $request)
    {
        $limit = (int) $request->get('limit', 6); // default 6
        $cacheKey = 'public_events_limit_' . $limit;

        $events = Cache::remember($cacheKey, 60, function () use ($limit) {
            return Event::query()
                ->where('is_active', true)
                ->orderBy('start_date', 'desc')
                ->limit($limit)
                ->get();
        });

        return response()->json([
            'data' => $events,
        ]);
    }
}
