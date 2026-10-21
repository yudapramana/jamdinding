<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\User;
use App\Models\Province;
use App\Models\Regency;
use App\Models\District;
use App\Models\EventGroup;
use App\Models\Role;
use App\Models\Village;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class EventKokardeController extends Controller
{
    /**
     * EXPORT KOKARDE PDF (KONTINGEN / ROLE / GOLONGAN)
     */
    public function exportPdf(Request $request)
    {
        // ➕ Tambahkan 'group' ke validasi in:
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'type'     => 'required|in:participant,role,group',
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
                ->where('registration_status', 'verified')
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
                return 'Tidak ada peserta siap cetak kokarde pada kontingen ini.';
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
                ->whereNotNull('avatar')
                ->get();

            if ($rows->isEmpty()) {
                return 'Tidak ada panitia untuk role tersebut.';
            }

            return view('pdf.kokarde-mass', [
                'event' => $event,
                'rows'  => $rows,
                'mode'  => 'role',
                'role'  => $role,
            ]);
        }

        /**
         * =========================================
         * MODE 3️⃣ : PESERTA PER GOLONGAN
         * =========================================
         */
        if ($request->type === 'group') {
            $request->validate([
                'event_group_id' => 'required|exists:event_groups,id',
            ]);

            $group = EventGroup::findOrFail($request->event_group_id);

            $rows = EventParticipant::query()
                ->with(['participant','event','eventBranch','eventGroup','eventCategory'])
                ->where('event_id', $event->id)
                ->where('event_group_id', $group->id)
                ->where('registration_status', 'verified')
                ->whereNotNull('event_category_id')
                ->whereHas('participant', fn ($q) =>
                    $q->whereNotNull('photo_url')
                )
                ->orderByRaw('participant_number IS NULL, participant_number')
                ->get();

            if ($rows->isEmpty()) {
                return 'Tidak ada peserta siap cetak kokarde pada golongan ini.';
            }

            // Kita reuse view 'participant' karena layout kokarde peserta 100% sama
            return view('pdf.kokarde-mass', [
                'event' => $event,
                'rows'  => $rows,
                'mode'  => 'participant',
            ]);
        }
    }


    /**
     * EXPORT LEMBAR VERIFIKASI FOTO PER GOLONGAN/CABANG
     */
    public function exportVerificationPdf(Request $request)
    {
        // ... (Kode untuk exportVerificationPdf tetap sama 100%)
        $request->validate([
            'event_id'       => 'required|exists:events,id',
            'event_group_id' => 'required|exists:event_groups,id',
        ]);

        $event = Event::findOrFail($request->event_id);
        $group = EventGroup::findOrFail($request->event_group_id);

        $rows = EventParticipant::query()
            ->with(['participant', 'eventBranch', 'eventGroup', 'eventCategory'])
            ->where('event_id', $event->id)
            ->where('event_group_id', $request->event_group_id)
            ->where('registration_status', 'verified')
            ->whereHas('participant', fn ($q) => $q->whereNotNull('photo_url'))
            ->orderByRaw('participant_number IS NULL, participant_number')
            ->get();

        if ($rows->isEmpty()) {
            return 'Tidak ada peserta pada golongan tersebut';
        }

        if ($group->is_team) {
            $groupedRows = $rows->groupBy(function ($item) {
                return $item->event_category_id . '_' . ($item->contingent ?? 'non-contingent');
            });

            return view('pdf.verifikasi-tim', [
                'event' => $event,
                'group' => $group,
                'groupedRows' => $groupedRows,
            ]);
        }

        return view('pdf.verifikasi-golongan', [
            'event' => $event,
            'group' => $group, 
            'rows'  => $rows,
        ]);
    }

}