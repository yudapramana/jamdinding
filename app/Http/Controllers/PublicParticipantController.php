<?php

namespace App\Http\Controllers;

use App\Models\EventParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicParticipantController extends Controller
{
    public function show(EventParticipant $eventParticipant)
    {
        $cacheKey = 'public_participant_' . $eventParticipant->uuid;

        $ep = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($eventParticipant) {
            return $eventParticipant->load([
                'participant',
                'event',
                'eventBranch',
                'eventGroup',
                'eventCategory',
                'verifications.verifier',
            ]);
        });

        return view('public.participant.show', compact('ep'));
    }

    /**
     * CETAK KOKARDE A6
     */
    public function printKokarde(EventParticipant $eventParticipant)
    {
        // pastikan foto ada (UX + keamanan)
        if (!$eventParticipant->participant?->photo_url) {
            abort(404, 'Foto peserta belum tersedia');
        }

        $eventParticipant->load([
            'participant',
            'event',
            'eventBranch',
            'eventGroup',
            'eventCategory',
        ]);

        return view('public.participant.kokarde', [
            'ep' => $eventParticipant,
            'event' => $eventParticipant->event
        ]);
    }
}
