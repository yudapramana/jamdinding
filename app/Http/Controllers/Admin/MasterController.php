<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventStage;
use App\Models\EventBranch;
use App\Models\EventGroup;
use App\Models\EventCategory;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Province;
use App\Models\Regency;
use App\Models\District;
use App\Models\Village;


class MasterController extends Controller
{
    public function index(Request $request)
    {
        $type    = $request->query('type');
        $eventId = $request->query('event_id');

        if (! $type || ! $eventId) {
            return response()->json(['data' => []]);
        }

        switch ($type) {

            /* =========================
             * EVENT STAGES
             * ========================= */
            case 'event_stages':
                $data = EventStage::where('event_id', $eventId)
                    ->where('is_active', true)
                    ->orderBy('order_number')
                    ->get()
                    ->map(fn ($s) => [
                        'id'         => $s->id,
                        'stage_id'   => $s->stage_id,
                        'name'       => $s->name,
                        'order'      => $s->order_number,
                        'start_date' => $s->start_date,
                        'end_date'   => $s->end_date,
                        'notes'      => $s->notes,
                        'is_active'  => $s->is_active,
                    ]);
                break;

            /* =========================
             * EVENT BRANCHES
             * ========================= */
            case 'event_branches':
                $data = EventBranch::where('event_id', $eventId)
                    ->where('status', 'active')
                    ->orderBy('order_number')
                    ->get()
                    ->map(fn ($b) => [
                        'id'          => $b->id,
                        'branch_id'   => $b->branch_id,
                        'branch_code' => $b->branch_code,
                        'name'        => $b->branch_name,
                        'full_name'   => $b->full_name,
                        'order'       => $b->order_number,
                        'is_active'   => $b->is_active,
                    ]);
                break;

            /* =========================
             * EVENT GROUPS (FILTER OPTIONAL)
             * ========================= */
            case 'event_groups':
                $branchId = $request->query('event_branch_id'); // optional

                $data = EventGroup::where('event_id', $eventId)
                    ->where('status', 'active')
                    ->when($branchId, fn ($q) =>
                        $q->where('branch_id', $branchId)
                    )
                    ->orderBy('order_number')
                    ->get()
                    ->map(fn ($g) => [
                        'id'        => $g->id,
                        'branch_id' => $g->branch_id,
                        'group_id'  => $g->group_id,
                        'name'      => $g->group_name,
                        'full_name' => $g->full_name,
                        'is_team'   => (bool) $g->is_team,
                        'order'     => $g->order_number,
                        'max_age'   => $g->max_age,
                        'is_active' => $g->status === 'active',
                    ]);
                break;

            /* =========================
             * EVENT CATEGORIES (FILTER OPTIONAL)
             * ========================= */
            case 'event_categories':
                $groupId = $request->query('event_group_id'); // optional

                $data = EventCategory::where('event_id', $eventId)
                    ->where('status', 'active')
                    ->when($groupId, fn ($q) =>
                        $q->where('group_id', $groupId)
                    )
                    ->orderBy('order_number')
                    ->get()
                    ->map(fn ($c) => [
                        'id'          => $c->id,
                        'branch_id'   => $c->branch_id,
                        'group_id'    => $c->group_id,
                        'category_id' => $c->category_id,
                        'name'        => $c->category_name,
                        'full_name'   => $c->full_name,
                        'order'       => $c->order_number,
                        'is_active'   => $c->status === 'active',
                    ]);
                break;

            case 'event_regions':

                $event = Event::find($eventId);

                if (! $event) {
                    $data = [];
                    break;
                }

                switch ($event->event_level) {

                    /* =========================
                    * NASIONAL → PROVINSI
                    * ========================= */
                    case 'national':
                        $data = Province::orderBy('name')
                            ->get()
                            ->map(fn ($p) => [
                                'id'   => $p->id,
                                'name' => $p->name,
                            ]);
                        break;

                    /* =========================
                    * PROVINSI → KAB/KOTA
                    * ========================= */
                    case 'province':
                        $data = Regency::where('province_id', $event->province_id)
                            ->orderBy('name')
                            ->get()
                            ->map(fn ($r) => [
                                'id'   => $r->id,
                                'name' => $r->name,
                            ]);
                        break;

                    /* =========================
                    * KAB/KOTA → KECAMATAN
                    * ========================= */
                    case 'regency':
                        $data = District::where('regency_id', $event->regency_id)
                            ->orderBy('name')
                            ->get()
                            ->map(fn ($d) => [
                                'id'   => $d->id,
                                'name' => $d->name,
                            ]);
                        break;

                    /* =========================
                    * KECAMATAN → NAGARI/DESA
                    * ========================= */
                    case 'district':
                        $data = Village::where('district_id', $event->district_id)
                            ->orderBy('name')
                            ->get()
                            ->map(fn ($v) => [
                                'id'   => $v->id,
                                'name' => $v->name,
                            ]);
                        break;

                    default:
                        $data = [];
                }

                break;


            default:
                $data = [];
        }

        return response()->json([
            'data' => $data,
        ]);
    }
}
