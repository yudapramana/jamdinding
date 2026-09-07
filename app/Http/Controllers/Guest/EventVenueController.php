<?php

namespace App\Http\Controllers\Guest;

use App\Models\Event;
use App\Models\EventGroup;
use App\Models\EventLocation;
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

    public function schedules() { 
        $eventId = request()->query('event_id', 1);
 
        $event = Event::findOrFail($eventId);
    
        $locations = EventLocation::query()
            ->active()
            ->forEvent($eventId)
            ->with([
                'judgePanels' => function ($panel) {
                    $panel->where('is_active', true)
                        ->with([
                            'eventGroups' => function ($group) {
                                $group->where('status', 'active');
                            }
                        ]);
                }
            ])
            ->orderBy('id')
            ->get()
            ->map(function ($location) {
    
                /**
                 * PERBAIKAN #1:
                 * Urutkan majelis 1 s.d. 12 secara NUMERIK berdasarkan
                 * angka pada kolom 'code' (MJL-01, MJL-02, ... MJL-12),
                 * bukan berdasarkan urutan relasi/insert di database.
                 */
                $sortedPanels = $location->judgePanels
                    ->sortBy(function ($panel) {
                        preg_match('/(\d+)/', $panel->code ?? '', $matches);
                        return isset($matches[1]) ? (int) $matches[1] : 0;
                    })
                    ->values();
    
                /**
                 * PERBAIKAN #2:
                 * Bug: pluck('roman_name ') sebelumnya ada spasi di belakang
                 * nama kolom sehingga Eloquent mencari kolom yang salah.
                 * Sudah diperbaiki menjadi 'roman_name'.
                 */
                $majelis = $sortedPanels
                    ->pluck('roman_name')
                    ->filter()
                    ->unique()
                    ->values();
    
                $cabang = $sortedPanels
                    ->flatMap(fn ($panel) => $panel->eventGroups)
                    ->map(fn ($group) => $group->full_name)
                    ->filter()
                    ->unique()
                    ->values();
    
                return [
                    'location_name' => $location->name,
                    'majelis'       => $majelis,
                    'cabang'        => $cabang,
                ];
            });
    
        /* ===============================
        * VIEW LANGSUNG DI ROUTE
        * =============================== */
    
        return response()->view('event.jadwal', [
            'eventId'   => $eventId,
            'event'     => $event,
            'locations' => $locations,
        ]);
    }

    public function venuesJson() { 

        $eventId = request()->query('event_id', 1);

        $locations = EventLocation::query()
            ->active()
            ->forEvent($eventId)
            ->with([
                'judgePanels' => function ($panel) {
                    $panel->where('is_active', true)
                        ->with([
                            'eventGroups' => function ($group) {
                                $group->where('status', 'active');
                            }
                        ]);
                }
            ])
            ->orderBy('name')
            ->get()
            ->map(function ($location) {

                // ===== KUMPULKAN MAJELIS =====
                $majelis = $location->judgePanels
                    ->pluck('name')
                    ->unique()
                    ->values();

                // ===== KUMPULKAN CABANG / GOLONGAN =====
                $cabang = $location->judgePanels
                    ->flatMap(fn ($panel) => $panel->eventGroups)
                    ->map(fn ($group) => $group->full_name)
                    ->unique()
                    ->values();

                return [
                    'location_id'   => $location->id,
                    'location_name' => $location->name,

                    // dipakai untuk judul seperti gambar
                    'majelis_count' => $majelis->count(),
                    'majelis'       => $majelis,

                    // isi teks kecil di bawah
                    'cabang'        => $cabang,
                ];
            });

        return response()->json([
            'event_id' => $eventId,
            'total_locations' => $locations->count(),
            'data' => $locations,
        ]);
    }

    public function venuesDetails() {  
        $eventId = request()->query('event_id', 1);

        $locations = EventLocation::query()
            ->active()
            ->forEvent($eventId)
            ->with([
                'judgePanels' => function ($panel) {
                    $panel->where('is_active', true)
                        ->with([
                            'eventGroups' => function ($group) {
                                $group->where('status', 'active')
                                        ->orderBy('order_number');
                            }
                        ])
                        ->orderBy('name');
                }
            ])
            ->orderBy('name')
            ->get()
            ->map(function ($location) {
                return [
                    'id'   => $location->id,
                    'code' => $location->code,
                    'name' => $location->name,
                    'address' => $location->address,
                    'coordinate' => $location->coordinate,

                    'event_judge_panels' => $location->judgePanels->map(function ($panel) {
                        return [
                            'id'   => $panel->id,
                            'code' => $panel->code,
                            'name' => $panel->name,
                            'notes'=> $panel->notes,

                            'event_groups' => $panel->eventGroups->map(function ($group) {
                                return [
                                    'id'          => $group->id,
                                    'branch_id'   => $group->branch_id,
                                    'branch_name' => $group->branch_name,
                                    'group_id'    => $group->group_id,
                                    'group_name'  => $group->group_name,
                                    'full_name'   => $group->full_name,
                                    'is_team'     => $group->is_team,
                                ];
                            }),
                        ];
                    }),
                ];
            });

        return response()->json([
            'event_id' => $eventId,
            'total_locations' => $locations->count(),
            'data' => $locations,
        ]);
    }
}