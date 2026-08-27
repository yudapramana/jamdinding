<?php

namespace App\Http\Controllers\Guest;

use App\Models\Event;
use App\Models\EventGroup;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class EventVenueController extends Controller
{
    /**
     * Menampilkan halaman "Lokasi Lomba" — daftar bidang lomba beserta
     * tempat pelaksanaan (venue) dan QR code ke lokasi Google Maps.
     *
     * Data diambil dari:
     * - event_groups   (bidang/golongan lomba, kolom full_name, status, order_number)
     * - event_judge_panels (majelis, relasi ke event_groups via event_judge_panel_id)
     * - event_locations (venue, relasi ke event_judge_panels via event_location_id)
     *
     * ⚠️ ASUMSI RELASI (sesuaikan nama relasi kalau berbeda di model Anda):
     * - EventGroup::judgePanel()      -> belongsTo(EventJudgePanel::class, 'event_judge_panel_id')
     * - EventJudgePanel::location()   -> belongsTo(EventLocation::class, 'event_location_id')
     */
    public function index(Request $request)
    {
        $eventId = $request->query('event_id', 1);

        $event = Event::findOrFail($eventId);

        $groups = EventGroup::query()
            ->where('event_id', $event->id)
            ->where('status', 'active')
            ->whereNotNull('event_judge_panel_id')
            ->with(['judgePanel.location'])
            ->orderBy('order_number')
            ->get()
            ->map(function ($group) {

                $panel    = $group->judgePanel;
                $location = $panel?->location;

                $mapsUrl = null;
                if ($location && $location->latitude && $location->longitude) {
                    $mapsUrl = "https://www.google.com/maps?q={$location->latitude},{$location->longitude}";
                }

                return [
                    'bidang_lomba' => $group->full_name,
                    'majelis'      => $panel->roman_name ?? $panel->name ?? null,
                    'tempat_lomba' => $location->name ?? '-',
                    'address'      => $location->address ?? null,
                    'photo_url'    => $location->photo_url ?? null, // ➕ TAMBAHAN
                    'maps_url'     => $mapsUrl,

                    // QR code digenerate via API publik qrserver.com (tanpa perlu install package tambahan).
                    // Kalau butuh QR offline / self-hosted, ganti dengan package "simplesoftwareio/simple-qrcode".
                    'qr_code_url' => $mapsUrl
                        ? 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($mapsUrl)
                        : null,
                ];
            });

        return view('event.venues', [
            'event'  => $event,
            'groups' => $groups,
        ]);
    }
}