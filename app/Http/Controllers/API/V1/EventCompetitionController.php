<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventCompetition;
use App\Models\EventGroup;
use App\Models\Round;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventCompetitionController extends Controller
{
    public function meta(Request $request, Event $event)
    {
        $rounds = Round::query()
            ->orderByRaw('COALESCE(order_number, 999999) asc')
            ->orderBy('id')
            ->get(['id', 'name', 'order_number']);

        $groupsQuery = EventGroup::query()
            ->where('event_id', $event->id);

        // ➕ AMBIL DATA USER DAN CEK ROLE
        $user = $request->user();
        $roleSlug = $user ? strtolower(optional($user->role)->slug ?? optional($user->role)->name ?? '') : '';

        // ➕ FILTER KHUSUS PANITERA
        if (str_contains($roleSlug, 'panitera')) {
            // Ambil ID Majelis dari tabel event_judge_panel_clerks miliknya
            $panelId = optional($user->clerkAssignment)->event_judge_panel_id;
            
            if ($panelId) {
                // Saring query golongan berdasarkan majelis tersebut
                $groupsQuery->where('event_judge_panel_id', $panelId);
            } else {
                // Jika panitera belum ditugaskan ke majelis manapun, kembalikan data kosong
                $groupsQuery->whereNull('id');
            }
        }

        // Eksekusi query dengan pengurutan dan ambil kolom yang dibutuhkan
        // (Ditambahkan 'event_judge_panel_id' di array get agar frontend bisa memanfaatkannya jika perlu)
        $groups = $groupsQuery
            ->orderByRaw('COALESCE(order_number, 999999) asc')
            ->orderBy('id')
            ->get([
                'id', 'event_id', 'branch_id', 'group_id', 'branch_name', 
                'group_name', 'full_name', 'order_number', 'status', 'is_team', 'event_judge_panel_id'
            ]);

        return response()->json([
            'rounds' => $rounds,
            'event_groups' => $groups,
        ]);
    }

    public function tree(Request $request, Event $event)
    {
        $search = trim((string) $request->get('search', ''));

        // ➕ AMBIL DATA USER DAN CEK ROLE DI LUAR LOOP
        $user = $request->user();
        $roleSlug = $user ? strtolower(optional($user->role)->slug ?? optional($user->role)->name ?? '') : '';
        $isPanitera = str_contains($roleSlug, 'panitera');
        $panelId = $isPanitera ? optional($user->clerkAssignment)->event_judge_panel_id : null;

        $rounds = Round::query()
            ->orderByRaw('COALESCE(order_number, 999999) asc')
            ->orderBy('id')
            ->get(['id','name','order_number'])
            ->map(function ($r) use ($event, $search, $isPanitera, $panelId) {

                $q = EventCompetition::query()
                    ->from('event_competitions')
                    ->where('event_competitions.event_id', $event->id)
                    ->where('event_competitions.round_id', $r->id)
                    ->leftJoin('event_groups as eg', 'eg.id', '=', 'event_competitions.event_group_id')
                    ->select('event_competitions.*')
                    ->with(['eventGroup:id,full_name,branch_name,group_name,order_number'])
                    ->orderByRaw('COALESCE(eg.order_number, 999999) asc')
                    ->orderBy('event_competitions.id', 'desc');

                // ➕ FILTER KHUSUS PANITERA PADA EVENT COMPETITION
                if ($isPanitera) {
                    if ($panelId) {
                        // Saring kompetisi (yang terhubung dengan eg/event_groups) berdasarkan majelis tersebut
                        $q->where('eg.event_judge_panel_id', $panelId);
                    } else {
                        // Jika panitera belum ditugaskan ke majelis manapun, pastikan query mengembalikan data kosong
                        $q->whereNull('event_competitions.id');
                    }
                }

                if ($search !== '') {
                    $q->where(function ($qq) use ($search) {
                        $qq->where('event_competitions.full_name', 'like', "%{$search}%")
                        ->orWhere('event_competitions.venue', 'like', "%{$search}%");
                    });
                }

                $comps = $q->get([
                    'event_competitions.id',
                    'event_competitions.event_id',
                    'event_competitions.event_group_id',
                    'event_competitions.round_id',
                    'event_competitions.full_name',
                    'event_competitions.status',
                    'event_competitions.is_team',
                    'event_competitions.scheduled_at',
                    'event_competitions.venue',
                ]);

                return [
                    'id' => $r->id,
                    'name' => $r->name,
                    'competitions' => $comps->map(function ($c) {
                        return [
                            'id' => $c->id,
                            'full_name' => $c->full_name,
                            'status' => $c->status,
                            'is_team' => (bool) $c->is_team,
                            'scheduled_at' => optional($c->scheduled_at)->toISOString(),
                            'venue' => $c->venue,
                            'event_group' => $c->eventGroup ? [
                                'id' => $c->eventGroup->id,
                                'full_name' => $c->eventGroup->full_name,
                                'branch_name' => $c->eventGroup->branch_name,
                                'group_name' => $c->eventGroup->group_name,
                                'order_number' => $c->eventGroup->order_number,
                            ] : null,
                            'event_group_id' => $c->event_group_id,
                            'round_id' => $c->round_id,
                        ];
                    })->values(),
                ];
            });

        return response()->json(['rounds' => $rounds]);
    }
    
    // public function tree(Request $request, Event $event)
    // {
    //     $search = trim((string) $request->get('search', ''));

    //     $rounds = Round::query()
    //         ->orderByRaw('COALESCE(order_number, 999999) asc')
    //         ->orderBy('id')
    //         ->get(['id','name','order_number'])
    //         ->map(function ($r) use ($event, $search) {

    //             $q = EventCompetition::query()
    //                 ->from('event_competitions')
    //                 ->where('event_competitions.event_id', $event->id)
    //                 ->where('event_competitions.round_id', $r->id)
    //                 ->leftJoin('event_groups as eg', 'eg.id', '=', 'event_competitions.event_group_id')
    //                 ->select('event_competitions.*')
    //                 ->with(['eventGroup:id,full_name,branch_name,group_name,order_number'])
    //                 ->orderByRaw('COALESCE(eg.order_number, 999999) asc')
    //                 ->orderBy('event_competitions.id', 'desc');

    //             if ($search !== '') {
    //                 $q->where(function ($qq) use ($search) {
    //                     $qq->where('event_competitions.full_name', 'like', "%{$search}%")
    //                     ->orWhere('event_competitions.venue', 'like', "%{$search}%");
    //                 });
    //             }

    //             $comps = $q->get([
    //                 'event_competitions.id',
    //                 'event_competitions.event_id',
    //                 'event_competitions.event_group_id',
    //                 'event_competitions.round_id',
    //                 'event_competitions.full_name',
    //                 'event_competitions.status',
    //                 'event_competitions.is_team',
    //                 'event_competitions.scheduled_at',
    //                 'event_competitions.venue',
    //             ]);

    //             return [
    //                 'id' => $r->id,
    //                 'name' => $r->name,
    //                 'competitions' => $comps->map(function ($c) {
    //                     return [
    //                         'id' => $c->id,
    //                         'full_name' => $c->full_name,
    //                         'status' => $c->status,
    //                         'is_team' => (bool) $c->is_team,
    //                         'scheduled_at' => optional($c->scheduled_at)->toISOString(),
    //                         'venue' => $c->venue,
    //                         'event_group' => $c->eventGroup ? [
    //                             'id' => $c->eventGroup->id,
    //                             'full_name' => $c->eventGroup->full_name,
    //                             'branch_name' => $c->eventGroup->branch_name,
    //                             'group_name' => $c->eventGroup->group_name,
    //                             'order_number' => $c->eventGroup->order_number,
    //                         ] : null,
    //                         'event_group_id' => $c->event_group_id,
    //                         'round_id' => $c->round_id,
    //                     ];
    //                 })->values(),
    //             ];
    //         });

    //     return response()->json(['rounds' => $rounds]);
    // }

    public function store(Request $request, Event $event)
    {
        $data = $request->validate([
            'event_group_id' => ['required', 'integer', 'exists:event_groups,id'],
            'round_id'       => ['required', 'integer', 'exists:rounds,id'],
            'full_name'      => ['nullable', 'string', 'max:255'],
            'status'         => ['nullable', Rule::in(['draft','ongoing','finished','cancelled'])],
            'scheduled_at'   => ['nullable', 'date'],
            'venue'          => ['nullable', 'string', 'max:255'],
        ]);

        $group = \App\Models\EventGroup::query()
            ->where('id', $data['event_group_id'])
            ->where('event_id', $event->id)
            ->first();

        if (!$group) {
            return response()->json([
                'message' => 'Event group tidak valid untuk event ini.',
            ], 422);
        }

        // enforce unique (event_id,event_group_id,round_id)
        $exists = \App\Models\EventCompetition::query()
            ->where('event_id', $event->id)
            ->where('event_group_id', $data['event_group_id'])
            ->where('round_id', $data['round_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Kompetisi untuk kombinasi event + group + round ini sudah ada.',
            ], 422);
        }

        $isTeam = (bool) ($group->is_team ?? false);

        $fullName = trim((string)($data['full_name'] ?? ''));
        if ($fullName === '') {
            $fullName = (string) ($group->full_name ?? '');
        }

        // default ke draft
        $status = $data['status'] ?? 'draft';

        $comp = \App\Models\EventCompetition::create([
            'event_id'       => $event->id,
            'event_group_id' => $data['event_group_id'],
            'round_id'       => $data['round_id'],
            'full_name'      => $fullName,
            'status'         => $status,
            'is_team'        => $isTeam,
            'scheduled_at'   => $data['scheduled_at'] ?? null,
            'venue'          => $data['venue'] ?? null,
        ]);

        return response()->json($comp->fresh(), 201);
    }

    public function show(EventCompetition $eventCompetition)
    {
        $eventCompetition->load(['eventGroup:id,full_name,is_team','round:id,name']);
        return response()->json($eventCompetition);
    }

    public function update(Request $request, EventCompetition $eventCompetition)
    {
        $data = $request->validate([
            'event_group_id' => ['required','integer','exists:event_groups,id'],
            'round_id'       => ['required','integer','exists:rounds,id'],
            'full_name'      => ['nullable','string','max:255'],
            'status'         => ['nullable', Rule::in(['draft','ongoing','finished','cancelled'])],
            'scheduled_at'   => ['nullable','date'],
            'venue'          => ['nullable','string','max:255'],
        ]);

        $group = \App\Models\EventGroup::query()
            ->where('id', $data['event_group_id'])
            ->where('event_id', $eventCompetition->event_id)
            ->first();

        if (!$group) {
            return response()->json([
                'message' => 'Event group tidak valid untuk event ini.',
            ], 422);
        }

        // unique check (exclude self)
        $exists = \App\Models\EventCompetition::query()
            ->where('event_id', $eventCompetition->event_id)
            ->where('event_group_id', $data['event_group_id'])
            ->where('round_id', $data['round_id'])
            ->where('id', '!=', $eventCompetition->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Kompetisi untuk kombinasi event + group + round ini sudah ada.',
            ], 422);
        }

        $isTeam = (bool) ($group->is_team ?? false);

        $fullName = trim((string)($data['full_name'] ?? ''));
        if ($fullName === '') {
            $fullName = (string)($group->full_name ?? $eventCompetition->full_name);
        }

        // Ambil status / venue terbaru
        $status = $request->has('status') ? ($data['status'] ?? 'draft') : ($eventCompetition->status ?? 'draft');
        $venue = $request->has('venue') ? ($data['venue'] ?? null) : $eventCompetition->venue;
        $scheduledAt = $request->has('scheduled_at') ? ($data['scheduled_at'] ?? null) : $eventCompetition->scheduled_at;

        $eventCompetition->update([
            'event_group_id' => $data['event_group_id'],
            'round_id'       => $data['round_id'],
            'full_name'      => $fullName,
            'status'         => $status,
            'is_team'        => $isTeam,
            'scheduled_at'   => $scheduledAt,
            'venue'          => $venue,
        ]);

        return response()->json($eventCompetition->fresh());
    }

    public function destroy(EventCompetition $eventCompetition)
    {
        $eventCompetition->delete();
        return response()->json(['message' => 'Deleted']);
    }
}