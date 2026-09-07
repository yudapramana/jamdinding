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
    public function meta(Event $event)
    {
        $rounds = Round::query()
            ->orderByRaw('COALESCE(order_number, 999999) asc')
            ->orderBy('id')
            ->get(['id','name','order_number']);

        $groups = EventGroup::query()
            ->where('event_id', $event->id)
            ->orderByRaw('COALESCE(order_number, 999999) asc')
            ->orderBy('id')
            ->get(['id','event_id','branch_id','group_id','branch_name','group_name','full_name','order_number','status','is_team']);

        return response()->json([
            'rounds' => $rounds,
            'event_groups' => $groups,
        ]);
    }


    public function tree(Request $request, Event $event)
    {
        $search = trim((string) $request->get('search', ''));

        $rounds = Round::query()
            ->orderByRaw('COALESCE(order_number, 999999) asc')
            ->orderBy('id')
            ->get(['id','name','order_number'])
            ->map(function ($r) use ($event, $search) {

                $q = EventCompetition::query()
                    ->from('event_competitions') // biar jelas base table
                    ->where('event_competitions.event_id', $event->id)
                    ->where('event_competitions.round_id', $r->id)
                    ->leftJoin('event_groups as eg', 'eg.id', '=', 'event_competitions.event_group_id')
                    ->select('event_competitions.*')
                    ->with(['eventGroup:id,full_name,branch_name,group_name,order_number'])
                    ->orderByRaw('COALESCE(eg.order_number, 999999) asc')
                    ->orderBy('event_competitions.id', 'desc');

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


    public function store(Request $request, Event $event)
    {
        $data = $request->validate([
            'event_group_id' => ['required', 'integer', 'exists:event_groups,id'],
            'round_id'       => ['required', 'integer', 'exists:rounds,id'],

            // ✅ dari ajax sekarang tetap dikirim, tapi kita buat fleksibel
            'full_name'      => ['nullable', 'string', 'max:255'],

            // ✅ ajax sudah tidak mengirim status -> default draft
            'status'         => ['nullable', Rule::in(['draft','ongoing','finished','cancelled'])],

            // ✅ ajax masih mengirim scheduled_at (nullable)
            'scheduled_at'   => ['nullable', 'date'],

            // ✅ ajax sudah tidak mengirim venue -> tetap nullable
            'venue'          => ['nullable', 'string', 'max:255'],
        ]);

        // ✅ pastikan group ini milik event yang sedang aktif
        $group = \App\Models\EventGroup::query()
            ->where('id', $data['event_group_id'])
            ->where('event_id', $event->id)
            ->first();

        if (!$group) {
            return response()->json([
                'message' => 'Event group tidak valid untuk event ini.',
            ], 422);
        }

        // ✅ enforce unique (event_id,event_group_id,round_id)
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

        // ✅ ambil is_team dari event group
        $isTeam = (bool) ($group->is_team ?? false);

        // ✅ full_name fallback ke group->full_name kalau tidak dikirim
        $fullName = trim((string)($data['full_name'] ?? ''));
        if ($fullName === '') {
            $fullName = (string) ($group->full_name ?? '');
        }

        // ✅ status fallback ke draft
        $status = $data['status'] ?? 'draft';

        $comp = \App\Models\EventCompetition::create([
            'event_id'       => $event->id,
            'event_group_id' => $data['event_group_id'],
            'round_id'       => $data['round_id'],
            'full_name'      => $fullName,
            'status'         => $status,
            'is_team'        => $isTeam,
            'scheduled_at'   => $data['scheduled_at'] ?? null,
            'venue'          => $data['venue'] ?? null, // akan null kalau tidak dikirim
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

            // ✅ dibuat fleksibel: kalau tidak dikirim, fallback ke eventGroup->full_name atau existing
            'full_name'      => ['nullable','string','max:255'],

            // ✅ modal sudah tidak mengirim status -> default pakai existing (atau draft)
            'status'         => ['nullable', Rule::in(['draft','ongoing','finished','cancelled'])],

            'scheduled_at'   => ['nullable','date'],

            // ✅ modal sudah tidak mengirim venue -> tetap nullable (kalau tidak ada, biarkan existing)
            'venue'          => ['nullable','string','max:255'],
        ]);

        // ✅ pastikan event_group_id yang dipilih masih 1 event dengan competition ini
        $group = \App\Models\EventGroup::query()
            ->where('id', $data['event_group_id'])
            ->where('event_id', $eventCompetition->event_id)
            ->first();

        if (!$group) {
            return response()->json([
                'message' => 'Event group tidak valid untuk event ini.',
            ], 422);
        }

        // ✅ unique check (exclude self)
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

        // ✅ is_team selalu dari group (bukan dari request)
        $isTeam = (bool) ($group->is_team ?? false);

        // ✅ full_name: pakai input kalau ada, kalau kosong fallback ke group->full_name, terakhir existing
        $fullName = trim((string)($data['full_name'] ?? ''));
        if ($fullName === '') {
            $fullName = (string)($group->full_name ?? $eventCompetition->full_name);
        }

        // ✅ status: kalau tidak dikirim, pertahankan existing
        $status = $data['status'] ?? $eventCompetition->status ?? 'draft';

        // ✅ venue: kalau field tidak dikirim sama sekali -> pertahankan existing
        // Catatan: validate() tidak membedakan "tidak dikirim" vs "dikirim null" kalau key tidak ada.
        // Tapi kita bisa cek pakai $request->has('venue')
        $venue = $eventCompetition->venue;
        if ($request->has('venue')) {
            $venue = $data['venue'] ?? null; // boleh null untuk mengosongkan
        }

        // ✅ scheduled_at: sama; kalau tidak dikirim -> pertahankan existing
        $scheduledAt = $eventCompetition->scheduled_at;
        if ($request->has('scheduled_at')) {
            $scheduledAt = $data['scheduled_at'] ?? null;
        }

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
