<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EventContingent;
use App\Models\EventContingentMedal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\EventSnapshot;

class __EventContingentStandingsController extends Controller
{
    // public function index(Request $request)
    // {
    //     $eventId = $request->integer('event_id');

    //     if (!$eventId) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'event_id is required',
    //         ], 422);
    //     }

    //     /**
    //      * 1️⃣ Ambil SEMUA kontingen event (walaupun belum ada medali)
    //      */
    //     $contingents = EventContingent::where('event_id', $eventId)
    //         ->get([
    //             'id',
    //             'region_type',
    //             'region_id',
    //             'total_point',
    //         ]);

    //     if ($contingents->isEmpty()) {
    //         return response()->json([
    //             'success' => true,
    //             'data' => [],
    //         ]);
    //     }

    //     /**
    //      * 2️⃣ Resolve nama wilayah (snapshot-friendly)
    //      */
    //     $regionNames = $contingents->mapWithKeys(function ($c) {
    //         $name = match ($c->region_type) {
    //             'province' => DB::table('provinces')->where('id', $c->region_id)->value('name'),
    //             'regency'  => DB::table('regencies')->where('id', $c->region_id)->value('name'),
    //             'district' => DB::table('districts')->where('id', $c->region_id)->value('name'),
    //             'village'  => DB::table('villages')->where('id', $c->region_id)->value('name'),
    //             default    => '-',
    //         };

    //         return [$c->id => $name ?? '-'];
    //     });

    //     /**
    //      * 3️⃣ Ambil breakdown medali (BOLEH kosong)
    //      */
    //     $medals = EventContingentMedal::whereIn(
    //             'event_contingent_id',
    //             $contingents->pluck('id')
    //         )
    //         ->get()
    //         ->groupBy('event_contingent_id');

    //     /**
    //      * 4️⃣ Gabungkan data (default = 0)
    //      */
    //     $rows = $contingents->map(function ($c) use ($medals, $regionNames) {

    //         // default medal map 1–6 = 0
    //         $medalMap = [
    //             1 => 0,
    //             2 => 0,
    //             3 => 0,
    //             4 => 0,
    //             5 => 0,
    //             6 => 0,
    //         ];

    //         foreach ($medals->get($c->id, collect()) as $m) {
    //             $medalMap[$m->order_number] = $m->medal_count;
    //         }

    //         return [
    //             'region_name' => $regionNames[$c->id] ?? '-',
    //             'total_point' => (int) $c->total_point,
    //             'medals'      => $medalMap,
    //         ];
    //     });

    //     /**
    //      * 5️⃣ SORTING MULTI-KRITERIA
    //      */
    //     $sorted = $rows->sort(function ($a, $b) {

    //         $rules = [
    //             'total_point',
    //             'medals.1', // Juara 1
    //             'medals.2', // Juara 2
    //             'medals.3', // Juara 3
    //             'medals.4', // Harapan 1
    //             'medals.5', // Harapan 2
    //             'medals.6', // Harapan 3
    //         ];

    //         foreach ($rules as $key) {
    //             $aVal = data_get($a, $key, 0);
    //             $bVal = data_get($b, $key, 0);

    //             if ($aVal !== $bVal) {
    //                 return $bVal <=> $aVal;
    //             }
    //         }

    //         return strcasecmp($a['region_name'], $b['region_name']);
    //     })->values();

    //     return response()->json([
    //         'success' => true,
    //         'data' => $sorted,
    //     ]);
    // }

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
