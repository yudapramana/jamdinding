<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventJudgePanelResource;
use App\Models\Event;
use App\Models\EventJudge;
use App\Models\EventJudgePanel;
use App\Models\EventJudgePanelMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Models\EventLocation;


class EventJudgePanelController extends Controller
{

    /**
     * Mengambil semua daftar panel/majelis berdasarkan query event_id
     * Endpoint: GET /api/v1/event-judge-panels?event_id=X
     */
    public function allPanels(Request $request)
    {
        $eventId = $request->get('event_id');

        $query = EventJudgePanel::query();

        // Filter berdasarkan event_id jika dikirim dari frontend
        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        // Jika frontend meminta format simple (opsional, untuk dropdown biasa)
        if ($request->boolean('simple')) {
            return response()->json([
                'data' => $query->where('is_active', true)
                                ->orderBy('name')
                                ->get(['id', 'code', 'name', 'event_location_id'])
            ]);
        }

        $perPage = (int) $request->get('per_page', 100);

        // Mengembalikan data majelis menggunakan pagination
        $panels = $query->with('location') // Load relasi lokasi jika diperlukan nanti
                        ->orderBy('name')
                        ->paginate($perPage);

        return response()->json($panels);
    }

    public function assignLocation(EventJudgePanel $panel, Request $request)
    {
        $data = $request->validate([
            'event_location_id' => 'nullable|exists:event_locations,id'
        ]);

        if (!empty($data['event_location_id'])) {
            $location = EventLocation::find($data['event_location_id']);

            // VALIDASI: lokasi harus dari event yang sama
            if ($location->event_id !== $panel->event_id) {
                return response()->json([
                    'message' => 'Lokasi tidak berasal dari event yang sama.'
                ], 422);
            }
        }

        $panel->update([
            'event_location_id' => $data['event_location_id'] ?? null
        ]);

        return response()->json([
            'message' => 'Lokasi majelis berhasil diperbarui'
        ]);
    }



    /**
     * List panel + anggota
     */
    /**
     * List panel + anggota
     */
    public function index(Request $request, Event $event)
    {
        $user = $request->user();
        $roleSlug = $user ? strtolower(optional($user->role)->slug ?? optional($user->role)->name ?? '') : '';
        $isPanitera = str_contains($roleSlug, 'panitera');
        
        // Ambil ID Panel/Majelis jika user adalah Panitera
        $paniteraPanelId = null;
        if ($isPanitera) {
            $paniteraPanelId = optional($user->clerkAssignment)->event_judge_panel_id;
        }

        // SIMPLE MODE (untuk dropdown)
        if ($request->boolean('simple')) {
            $simpleQuery = EventJudgePanel::where('event_id', $event->id)
                ->where('is_active', true);
            
            // Filter panitera untuk dropdown
            if ($isPanitera) {
                if ($paniteraPanelId) {
                    $simpleQuery->where('id', $paniteraPanelId);
                } else {
                    $simpleQuery->whereNull('id'); // Pastikan kosong jika belum di-assign
                }
            }
                
            return response()->json([
                'data' => $simpleQuery->orderBy('name')
                    ->get(['id', 'code', 'name', 'event_location_id'])
            ]);
        }

        $perPage = (int) $request->get('per_page', 10);
        $search  = $request->get('search');

        $panelsQuery = EventJudgePanel::query()
            ->where('event_id', $event->id);

        // Filter khusus panitera pada tabel utama
        if ($isPanitera) {
            if ($paniteraPanelId) {
                $panelsQuery->where('id', $paniteraPanelId);
            } else {
                $panelsQuery->whereNull('id'); // Pastikan kosong jika belum di-assign
            }
        }

        $panels = $panelsQuery
            ->when($search, function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhereHas('eventLocation', function ($loc) use ($search) {
                        $loc->where('name', 'like', "%{$search}%");
                    });
                });
            })
            ->with([
                'members.eventJudge.masterJudge'
            ])
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'data' => EventJudgePanelResource::collection($panels),
            'meta' => [
                'current_page' => $panels->currentPage(),
                'per_page'     => $panels->perPage(),
                'total'        => $panels->total(),
                'from'         => $panels->firstItem(),
                'to'           => $panels->lastItem(),
                'last_page'    => $panels->lastPage(),
            ]
        ]);
    }





    public function store(Event $event, Request $request)
    {
        // Ambil urutan numerik berikutnya (int)
        $nextNumberInt = EventJudgePanel::nextNumberForEvent($event->id);

        // Format jadi 2 digit: 01, 02, 03, dst
        $nextNumber = str_pad($nextNumberInt, 2, '0', STR_PAD_LEFT);

        $name = "Majelis {$nextNumber}";
        $code = strtoupper($event->event_key) . "-MJ-{$nextNumber}";

        $panel = EventJudgePanel::create([
            'event_id'  => $event->id,
            'name'      => $name,
            'code'      => $code,
            'notes'     => null,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Majelis hakim berhasil ditambahkan',
            'data' => [
                'id'        => $panel->id,
                'name'      => $panel->name,
                'code'      => $panel->code,
                'notes'     => $panel->notes,
                'is_active' => $panel->is_active,
                'judges'    => [],
            ]
        ], 201);
    }




    /**
     * Search hakim dalam event
     */
    public function searchEventJudges(Event $event, Request $request)
    {
        $q = trim($request->search);

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $judges = EventJudge::query()
            ->where('event_id', $event->id)
            ->whereHas('masterJudge', function ($qr) use ($q) {
                $qr->where('full_name', 'like', "%{$q}%");
            })
            ->with('masterJudge:id,full_name')
            ->limit(10)
            ->get()
            ->map(fn ($j) => [
                'id'        => $j->id,
                'full_name'=> $j->masterJudge->full_name,
            ]);

        return response()->json($judges);
    }

    /**
     * Ambil anggota panel
     */
    public function members(EventJudgePanel $panel)
    {
        $members = $panel->members()
            ->with('eventJudge.masterJudge')
            ->orderBy('order_number')
            ->get();

        return response()->json([
            'judges' => $members->map(fn ($m) => [
                'event_judge_id' => $m->event_judge_id,
                'full_name'      => $m->eventJudge->masterJudge->full_name,
                'is_chief'       => $m->is_chief,
                'order_number'   => $m->order_number,
            ])
        ]);
    }

    /**
     * Simpan anggota panel
     */
    public function saveMembers(EventJudgePanel $panel, Request $request)
    {
        $data = $request->validate([
            'judges'                   => 'required|array|min:1',
            'judges.*.event_judge_id'  => 'required|exists:event_judges,id',
            'judges.*.is_chief'        => 'boolean',
        ]);

        DB::transaction(function () use ($panel, $data) {

            EventJudgePanelMember::where('event_judge_panel_id', $panel->id)->delete();

            foreach ($data['judges'] as $i => $j) {
                EventJudgePanelMember::create([
                    'event_judge_panel_id' => $panel->id,
                    'event_judge_id'       => $j['event_judge_id'],
                    'is_chief'             => $j['is_chief'] ?? false,
                    'order_number'         => $i + 1,
                ]);
            }
        });

        return response()->json([
            'message' => 'Anggota majelis berhasil disimpan'
        ]);
    }
}
