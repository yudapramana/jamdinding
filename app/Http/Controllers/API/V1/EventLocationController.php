<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\EventLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EventLocationController extends Controller
{

    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:3',
        ]);

        $response = Http::withHeaders([
            'User-Agent' => 'MTQ-Platform/1.0 (admin@mtq.local)', // WAJIB
        ])->get('https://nominatim.openstreetmap.org/search', [
            'q' => $request->q,
            'format' => 'json',
            'limit' => 1,
            'accept-language' => 'id',
        ]);

        if (! $response->ok()) {
            return response()->json([
                'message' => 'Nominatim error'
            ], 500);
        }

        return response()->json($response->json());
    }

    public function simple(Request $request, $eventId)
    {
        return EventLocation::where('event_id', $eventId)
        ->orderBy('name')->get(['id', 'name', 'code']);
    }


    public function index(Request $request, $eventId)
    {
        // ===== SIMPLE MODE (dropdown) =====
        if ($request->boolean('simple')) {
            return response()->json([
                'data' => EventLocation::where('event_id', $eventId)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get([
                        'id',
                        'code',
                        'name',
                    ])
            ]);
        }
        
        $query = EventLocation::where('event_id', $eventId);

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        return response()->json([
            'data' => $query
                ->orderBy('name')
                ->paginate(10)
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_id'  => 'required|exists:events,id',
            'code'      => 'nullable|string|max:50',
            'name'      => 'required|string|max:255',
            'address'   => 'nullable|string',
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'notes'     => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        EventLocation::create($validated);

        return response()->json(['message' => 'Lokasi berhasil ditambahkan']);
    }

    public function update(Request $request, $id)
    {
        $location = EventLocation::findOrFail($id);

        $validated = $request->validate([
            'code'      => 'nullable|string|max:50',
            'name'      => 'required|string|max:255',
            'address'   => 'nullable|string',
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'notes'     => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $location->update($validated);

        return response()->json(['message' => 'Lokasi berhasil diperbarui']);
    }

    public function destroy($id)
    {
        EventLocation::findOrFail($id)->delete();

        return response()->json(['message' => 'Lokasi berhasil dihapus']);
    }
}
