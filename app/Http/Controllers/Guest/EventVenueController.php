<?php

namespace App\Http\Controllers\Guest;

use App\Models\Event;
use App\Models\EventGroup;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class EventVenueController extends Controller
{
    /**
     * Menampilkan halaman "Lokasi Lomba" — dikelompokkan berdasarkan
     * VENUE (event_locations), diurutkan dari id venue terkecil.
     * Di dalam setiap venue ditampilkan semua bidang/cabang lomba
     * beserta nama majelisnya.
     *
     * Data diambil dari:
     * - event_groups        (bidang/golongan lomba, kolom full_name, status, order_number)
     * - event_judge_panels  (majelis, relasi ke event_groups via event_judge_panel_id)
     * - event_locations     (venue, relasi ke event_judge_panels via event_location_id)
     *
     * ⚠️ ASUMSI RELASI (sesuaikan nama relasi kalau berbeda di model Anda):
     * - EventGroup::judgePanel()      -> belongsTo(EventJudgePanel::class, 'event_judge_panel_id')
     * - EventJudgePanel::location()   -> belongsTo(EventLocation::class, 'event_location_id')
     */
    public function index(Request $request)
    {
        $eventId = $request->query('event_id', 1);

        $event = Event::findOrFail($eventId);

        $rawGroups = EventGroup::query()
            ->where('event_id', $event->id)
            ->where('status', 'active')
            ->whereNotNull('event_judge_panel_id')
            ->with(['judgePanel.location'])
            ->orderBy('order_number')
            ->get();

        // Kelompokkan bidang lomba berdasarkan id venue (event_locations.id).
        // Kalau ada panel yang belum punya lokasi, dikumpulkan di key 0 (paling atas).
        $venues = $rawGroups
            ->groupBy(function ($group) {
                return optional(optional($group->judgePanel)->location)->id ?? 0;
            })
            ->sortKeys() // urut berdasarkan id venue terkecil
            ->map(function ($items, $locationId) {

                $panel    = $items->first()->judgePanel;
                $location = $panel?->location;

                $mapsUrl = null;
                if ($location && $location->latitude && $location->longitude) {
                    $mapsUrl = "https://www.google.com/maps?q={$location->latitude},{$location->longitude}";
                }

                return [
                    'location_id'  => $locationId,
                    'tempat_lomba' => $location->name ?? 'Lokasi Belum Ditentukan',
                    'address'      => $location->address ?? null,
                    'photo_url'    => $location->photo_url ?? null,
                    'maps_url'     => $mapsUrl,

                    // QR code digenerate secara lokal pakai BaconQrCode (simplesoftwareio/simple-qrcode),
                    // sama seperti dipakai di halaman kokarde. Di-encode sebagai data URI SVG base64
                    // supaya bisa langsung dipasang ke atribut src <img> tanpa route/controller tambahan.
                    'qr_code_url' => $mapsUrl
                        ? 'data:image/svg+xml;base64,' . base64_encode(
                            QrCode::format('svg')->margin(1)->size(220)->generate($mapsUrl)
                        )
                        : null,

                    // Semua cabang lomba + majelis yang berada di venue ini.
                    'cabang' => $items->map(function ($group) {
                        $panel = $group->judgePanel;

                        return [
                            'bidang_lomba' => $group->full_name,
                            'majelis'      => $panel->roman_name ?? $panel->name ?? null,
                        ];
                    })->values(),
                ];
            })
            ->values();

        return view('event.venues', [
            'event'  => $event,
            'venues' => $venues,
        ]);
    }
}