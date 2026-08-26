<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\User;
use App\Models\Province;
use App\Models\Regency;
use App\Models\District;
use App\Models\Role;
use App\Models\Village;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class EventKokardeController extends Controller
{
    /**
     * EXPORT KOKARDE PDF PER KONTINGEN
     */
    public function exportPdf(Request $request)
    {

        $request->validate([
            'event_id' => 'required|exists:events,id',
            'type'     => 'required|in:participant,role',
        ]);

        $event = Event::findOrFail($request->event_id);

        /**
         * =========================================
         * MODE 1️⃣ : PESERTA (BERDASARKAN KONTINGEN)
         * =========================================
         */
        if ($request->type === 'participant') {
            $request->validate([
                'region_id' => 'required',
            ]);

            /* === Resolve region & kolom participant === */
            switch ($event->event_level) {
                case 'national':
                    $regionModel = Province::class;
                    $participantColumn = 'province_id';
                    break;
                case 'province':
                    $regionModel = Regency::class;
                    $participantColumn = 'regency_id';
                    break;
                case 'regency':
                    $regionModel = District::class;
                    $participantColumn = 'district_id';
                    break;
                case 'district':
                    $regionModel = Village::class;
                    $participantColumn = 'village_id';
                    break;
                default:
                    abort(422, 'Event level tidak valid');
            }

            $region = $regionModel::findOrFail($request->region_id);

            $rows = EventParticipant::query()
                ->with(['participant','event','eventBranch','eventGroup','eventCategory'])
                ->where('event_id', $event->id)
                ->whereNotNull('event_category_id')
                ->whereHas('participant', fn ($q) =>
                    $q->where($participantColumn, $region->id)
                )
                ->whereHas('participant', fn ($q) =>
                    $q->whereNotNull('photo_url')
                )
                ->orderByRaw('participant_number IS NULL, participant_number')
                ->get();

            if ($rows->isEmpty()) {
                abort(404, 'Tidak ada peserta siap cetak kokarde');
            }

            return view('pdf.kokarde-mass', [
                'event' => $event,
                'rows'  => $rows,
                'mode'  => 'participant',
            ]);
        }

        /**
         * =========================================
         * MODE 2️⃣ : ROLE / PANITIA
         * =========================================
         */
        if ($request->type === 'role') {

            $request->validate([
                'role_id' => 'required|exists:roles,id',
            ]);

            $role = Role::findOrFail($request->role_id);

            $rows = User::where('role_id', $role->id)
                ->where('event_id', $event->id)
                ->with('event', 'province', 'regency', 'district', 'village')
                ->get();

            if ($rows->isEmpty()) {
                abort(404, 'Tidak ada panitia untuk role tersebut');
            }


            return view('pdf.kokarde-mass', [
                'event' => $event,
                'rows'  => $rows,
                'mode'  => 'role',
                'role'  => $role,
            ]);
        }
    }


}
