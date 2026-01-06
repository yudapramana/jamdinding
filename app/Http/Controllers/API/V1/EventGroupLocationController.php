<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\EventGroup;
use App\Models\EventLocation;
use Illuminate\Http\Request;

class EventGroupLocationController extends Controller
{
    public function assign(Request $request, $id)
    {

        
        $validated = $request->validate([
            'event_location_id' => 'nullable|exists:event_locations,id',
        ]);

        $group = EventGroup::findOrFail($id);

        // if ($group->event->isStageActive('pelaksanaan')) {
        //     abort(403, 'Tidak dapat mengubah majelis saat pelaksanaan');
        // }


        // optional: pastikan masih event yang sama
        if ($validated['event_location_id']) {
            $location = EventLocation::find($validated['event_location_id']);

            if ($location->event_id !== $group->event_id) {
                return response()->json([
                    'message' => 'Lokasi tidak sesuai dengan event'
                ], 422);
            }
        }

        $group->update([
            'event_location_id' => $validated['event_location_id']
        ]);

        return response()->json([
            'message' => 'Cabang berhasil di-assign ke majelis'
        ]);
    }
}
