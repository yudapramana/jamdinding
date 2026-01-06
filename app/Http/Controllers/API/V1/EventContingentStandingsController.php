<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\EventContingent;
use App\Models\EventContingentMedal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\EventSnapshot;

class EventContingentStandingsController extends Controller
{

    public function index(Request $request)
    {
        $eventId = $request->integer('event_id');
        if (!$eventId) abort(422, 'event_id is required');

        /**
         * 1️⃣ Cek snapshot leaderboard
         */
        $snapshot = EventSnapshot::where([
            'event_id' => $eventId,
            'type'     => 'leaderboard',
        ])->latest('published_at')->first();

        if ($snapshot) {
            return response()->json([
                'success' => true,
                'source'  => 'snapshot',
                'data' => $snapshot?->payload ?? [],
            ]);
        }

        /**
         * 2️⃣ FALLBACK: LIVE QUERY (dev / sebelum publish)
         */
        $rows = DB::table('event_contingents as ec')
            ->leftJoin('event_contingent_medals as ecm', 'ecm.event_contingent_id', '=', 'ec.id')
            ->leftJoin('provinces as p', fn($j) => $j->on('p.id','=','ec.region_id')->where('ec.region_type','province'))
            ->leftJoin('regencies as r', fn($j) => $j->on('r.id','=','ec.region_id')->where('ec.region_type','regency'))
            ->leftJoin('districts as d', fn($j) => $j->on('d.id','=','ec.region_id')->where('ec.region_type','district'))
            ->leftJoin('villages as v', fn($j) => $j->on('v.id','=','ec.region_id')->where('ec.region_type','village'))
            ->where('ec.event_id', $eventId)
            ->selectRaw('
                COALESCE(p.name, r.name, d.name, v.name, "-") as region_name,
                ec.total_point,
                SUM(CASE WHEN ecm.order_number = 1 THEN ecm.medal_count ELSE 0 END) as juara_1,
                SUM(CASE WHEN ecm.order_number = 2 THEN ecm.medal_count ELSE 0 END) as juara_2,
                SUM(CASE WHEN ecm.order_number = 3 THEN ecm.medal_count ELSE 0 END) as juara_3,
                SUM(CASE WHEN ecm.order_number = 4 THEN ecm.medal_count ELSE 0 END) as harapan_1,
                SUM(CASE WHEN ecm.order_number = 5 THEN ecm.medal_count ELSE 0 END) as harapan_2,
                SUM(CASE WHEN ecm.order_number = 6 THEN ecm.medal_count ELSE 0 END) as harapan_3
            ')
            ->groupBy('region_name','ec.total_point')
            ->orderByDesc('ec.total_point')
            ->orderByDesc('juara_1')
            ->orderByDesc('juara_2')
            ->orderByDesc('juara_3')
            ->orderByDesc('harapan_1')
            ->orderByDesc('harapan_2')
            ->orderByDesc('harapan_3')
            ->orderBy('region_name')
            ->get();

        return response()->json([
            'success' => true,
            'source'  => 'live',
            'data'    => $rows,
        ]);
    }


}
