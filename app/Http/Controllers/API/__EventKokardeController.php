<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Province;
use App\Models\Regency;
use App\Models\District;
use App\Models\Village;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class __EventKokardeController extends Controller
{
    public function exportPdf(Request $request)
    {
        $request->validate([
            'event_id'  => 'required|exists:events,id',
            'region_id' => 'required',
        ]);

        $event = Event::findOrFail($request->event_id);

        /* =========================
        * RESOLVE REGION MODEL
        * ========================= */
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
                abort(422, 'Event level tidak valid.');
        }

        $region = $regionModel::findOrFail($request->region_id);

        /* =========================
        * AMBIL PESERTA (AMAN NULL)
        * ========================= */
        $eventParticipants = EventParticipant::query()
            ->with([
                'participant',
                'eventBranch',          // ✅ SUMBER CABANG
                'eventCategory',        // kategori lengkap
            ])
            ->where('event_id', $event->id)
            ->where('registration_status', 'verified')
            ->where('reregistration_status', 'verified')
            ->whereNotNull('event_category_id')
            ->whereHas('participant', function ($q) use ($participantColumn, $region) {
                $q->where($participantColumn, $region->id);
            })
            ->orderByRaw('participant_number IS NULL, participant_number')
            ->get();

        /* =========================
        * GENERATE PDF
        * ========================= */
        $pdf = Pdf::loadView('pdf.kokarde-a5', [
            'event'              => $event,
            'region'             => $region,
            'event_participants' => $eventParticipants,
            'eventLevel'         => $event->event_level,
        ])->setPaper('a5', 'portrait');


        return $pdf->download(
            'Kokarde_' .
            $event->event_name . '_' .
            str_replace(' ', '_', $region->name) .
            '.pdf'
        );
    }

}
