<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Event;
use App\Models\Participant;

class ParticipantPolicy
{
    /**
     * Akses dokumen peserta (lampiran)
     */
    public function viewDocument(User $user, Participant $participant): bool
    {
        // ==============================
        // ROLE BEBAS (FULL ACCESS)
        // ==============================
        if (in_array($user->role->name ?? null, [
            'SUPERADMIN',
            'ADMIN_EVENT',
            'VERIFIKATOR',
        ], true)) {
            return true;
        }

        // ==============================
        // KHUSUS ROLE PENDAFTARAN
        // ==============================
        if (($user->role->name ?? null) !== 'PENDAFTARAN') {
            return false;
        }

        if (! $user->event_id) {
            return false;
        }

        $event = Event::find($user->event_id);
        if (! $event) {
            return false;
        }

        // helper aman: null ≠ null
        $same = fn ($a, $b) =>
            !is_null($a) && !is_null($b) && (int) $a === (int) $b;

        return match ($event->event_level) {

            // event provinsi → cek sampai kab/kota
            'province' =>
                $same($user->province_id, $participant->province_id) &&
                $same($user->regency_id,  $participant->regency_id),

            // event kab/kota → cek sampai kecamatan
            'regency' =>
                $same($user->province_id, $participant->province_id) &&
                $same($user->regency_id,  $participant->regency_id) &&
                $same($user->district_id, $participant->district_id),

            // event kecamatan → cek sampai desa
            'district' =>
                $same($user->province_id, $participant->province_id) &&
                $same($user->regency_id,  $participant->regency_id) &&
                $same($user->district_id, $participant->district_id) &&
                $same($user->village_id,  $participant->village_id),

            default => false,
        };
    }

    /**
     * Upload / update dokumen peserta (lampiran)
     */
    public function updateDocument(User $user, Participant $participant): bool
    {
        // ==============================
        // ROLE BEBAS (FULL ACCESS)
        // ==============================
        if (in_array($user->role->name ?? null, [
            'SUPERADMIN',
            'ADMIN_EVENT',
        ], true)) {
            return true;
        }

        // ==============================
        // ROLE YANG DILARANG UPDATE
        // ==============================
        if (in_array($user->role->name ?? null, [
            'VERIFIKATOR',
        ], true)) {
            return false;
        }

        // ==============================
        // KHUSUS ROLE PENDAFTARAN
        // ==============================
        if (($user->role->name ?? null) !== 'PENDAFTARAN') {
            return false;
        }

        if (! $user->event_id) {
            return false;
        }

        $event = Event::find($user->event_id);
        if (! $event) {
            return false;
        }

        // helper aman: null ≠ null
        $same = fn ($a, $b) =>
            !is_null($a) && !is_null($b) && (int) $a === (int) $b;

        // ==============================
        // CEK WILAYAH (IDENTIK DENGAN viewDocument)
        // ==============================
        return match ($event->event_level) {

            // event provinsi → cek sampai kab/kota
            'province' =>
                $same($user->province_id, $participant->province_id) &&
                $same($user->regency_id,  $participant->regency_id),

            // event kab/kota → cek sampai kecamatan
            'regency' =>
                $same($user->province_id, $participant->province_id) &&
                $same($user->regency_id,  $participant->regency_id) &&
                $same($user->district_id, $participant->district_id),

            // event kecamatan → cek sampai desa
            'district' =>
                $same($user->province_id, $participant->province_id) &&
                $same($user->regency_id,  $participant->regency_id) &&
                $same($user->district_id, $participant->district_id) &&
                $same($user->village_id,  $participant->village_id),

            default => false,
        };
    }


}
