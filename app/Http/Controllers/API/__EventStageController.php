<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventStage;
use App\Models\Stage;
use Illuminate\Http\Request;
use Carbon\Carbon;

class __EventStageController extends Controller
{
    /**
     * List tahapan untuk 1 event
     * GET /api/v1/events/{event}/stages
     */
    public function index(Request $request, Event $event)
    {
        $query = EventStage::where('event_id', $event->id)->ordered();

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $stages = $query->paginate($request->get('per_page', 25));

        return response()->json([
            'success' => true,
            'data'    => $stages,
        ]);
    }

    /**
     * Simpan 1 event stage (bisa dipakai jika tidak lewat generate)
     * POST /api/v1/event-stages
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'event_id'     => 'required|exists:events,id',
            'stage_id'     => 'nullable|exists:stages,id',
            'name'         => 'required|string|max:255',
            'order_number' => 'nullable|integer|min:1',
            'start_date'   => 'nullable|date',
            'end_date'     => 'nullable|date|after_or_equal:start_date',
            'notes'        => 'nullable|string',
            'is_active'    => 'boolean',
        ]);

        if (empty($data['order_number'])) {
            $data['order_number'] = (EventStage::where('event_id', $data['event_id'])->max('order_number') ?? 0) + 1;
        }

        $eventStage = EventStage::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Event stage created successfully.',
            'data'    => $eventStage,
        ], 201);
    }

    /**
     * GET /api/v1/event-stages/{event_stage}
     */
    public function show(EventStage $eventStage)
    {
        return response()->json([
            'success' => true,
            'data'    => $eventStage,
        ]);
    }

    /**
     * PUT/PATCH /api/v1/event-stages/{event_stage}
     */
    public function update(Request $request, EventStage $eventStage)
    {
        $data = $request->validate([
            'name'         => 'sometimes|required|string|max:255',
            'order_number' => 'sometimes|integer|min:1',
            'start_date'   => 'nullable|date',
            'end_date'     => 'nullable|date|after_or_equal:start_date',
            'notes'        => 'nullable|string',
            'is_active'    => 'boolean',
        ]);

        $eventStage->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Event stage updated successfully.',
            'data'    => $eventStage,
        ]);
    }

    /**
     * DELETE /api/v1/event-stages/{event_stage}
     */
    public function destroy(EventStage $eventStage)
    {
        $eventStage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Event stage deleted successfully.',
        ]);
    }

    /**
     * Generate tahapan default dari master stages
     * POST /api/v1/events/{event}/stages/generate-default
     */
    public function generateFromMaster(Event $event)
    {
        // 1. Bersihkan data lama (jika ini yang diinginkan)
        EventStage::where('event_id', $event->id)->delete();

        // 2. Ambil master stage
        $masterStages = Stage::ordered()->where('is_active', true)->get();

        if ($masterStages->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Master stage kosong. Tambahkan template tahapan terlebih dahulu.',
            ], 404);
        }

        // 3. Tentukan tanggal awal: 1 minggu dari hari generate
        // $currentStart = now()->addDays(7)->startOfDay();
        $currentStart = Carbon::parse($event->tanggal_mulai);

        $created = [];

        foreach ($masterStages as $stage) {

            // Durasi tahapan berdasarkan field days di table stages
            $days = $stage->days ?? 1; // default 1 hari kalau tidak ada

            // Hitung tanggal akhir tahapan
            $currentEnd = (clone $currentStart)->addDays($days - 1)->endOfDay();

            // Simpan data
            $created[] = EventStage::create([
                'event_id'     => $event->id,
                'stage_id'     => $stage->id,
                'order_number' => $stage->order_number,
                'name'         => $stage->name,

                // generate otomatis tanggal
                'start_date'   => $currentStart->format('Y-m-d'),
                'end_date'     => $currentEnd->format('Y-m-d'),

                'is_active'    => true,
                'notes'        => null,
            ]);

            // Tentukan tanggal mulai tahap berikutnya = hari berikutnya setelah end_date
            $currentStart = (clone $currentEnd)->addDay()->startOfDay();
        }

        return response()->json([
            'success' => true,
            'message' => 'Tahapan event berhasil digenerate otomatis dari template master.',
            'data'    => $created,
        ], 201);
    }

}
