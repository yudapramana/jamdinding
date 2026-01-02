<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventContingent;
use Illuminate\Http\Request;

class EventContingentController extends Controller
{
    public function indexByEvent(Request $request, Event $event)
    {
        // ====== query params (match Vue server mode) ======
        $search  = trim((string) $request->get('search', ''));
        $perPage = (int) $request->get('per_page', 25);
        $page    = (int) $request->get('page', 1);

        $sort = (string) $request->get('sort', 'total_point');
        $dir  = strtolower((string) $request->get('dir', 'desc'));

        // clamp
        if ($perPage < 1) $perPage = 25;
        if ($perPage > 200) $perPage = 200;
        if ($page < 1) $page = 1;

        // whitelist sortable columns
        $allowedSort = [
            'contingent',
            'total_participant',
            'gold_count',
            'silver_count',
            'bronze_count',
            'fourth_count',
            'total_point',
            'created_at',
            'updated_at',
        ];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'total_point';
        }

        $dir = in_array($dir, ['asc', 'desc'], true) ? $dir : 'desc';

        // ====== build query ======
        $q = EventContingent::query()
            ->where('event_id', $event->id);

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('contingent', 'like', "%{$search}%");
            });
        }

        // sorting (tambahkan tie-breaker agar stabil)
        $q->orderBy($sort, $dir);

        if ($sort !== 'total_point') {
            $q->orderBy('total_point', 'desc');
        }
        if ($sort !== 'gold_count') {
            $q->orderBy('gold_count', 'desc');
        }

        $q->orderBy('contingent', 'asc');

        // paginate (Laravel otomatis pakai param page)
        $data = $q->paginate($perPage);

        return response()->json($data);
    }
}
