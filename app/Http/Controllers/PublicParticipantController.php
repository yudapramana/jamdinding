<?php

namespace App\Http\Controllers;

use App\Models\EventParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicParticipantController extends Controller
{
    /** Kunci session captcha khusus halaman peserta (terpisah dari captcha login). */
    private const CAPTCHA_SESSION_KEY = 'captcha_code_participant';

    /** Lama akses terbuka setelah captcha benar (menit). */
    private const VERIFIED_TTL_MINUTES = 30;

    /**
     * Halaman detail peserta.
     * Belum lolos captcha -> tampilkan form captcha (tanpa data peserta).
     */
    public function show(EventParticipant $eventParticipant)
    {
        if (!$this->isVerified($eventParticipant)) {
            return view('public.participant.captcha', [
                'uuid' => $eventParticipant->uuid,
            ]);
        }

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
     * Proses submit captcha.
     */
    public function verify(Request $request, EventParticipant $eventParticipant)
    {
        $request->validate(
            ['captcha' => ['required', 'string', 'max:10']],
            ['captcha.required' => 'Kode captcha wajib diisi.']
        );

        // pull = ambil lalu hapus -> satu kode hanya berlaku untuk satu kali percobaan
        $expected = $request->session()->pull(self::CAPTCHA_SESSION_KEY);
        $input    = strtoupper(trim($request->input('captcha')));

        if (!$expected || !hash_equals($expected, $input)) {
            return back()->withErrors(['captcha' => 'Kode captcha salah. Silakan coba lagi.']);
        }

        $request->session()->put($this->verifiedKey($eventParticipant), now()->timestamp);

        return redirect()->route('public.participant.show', $eventParticipant->uuid);
    }

    /**
     * CETAK KOKARDE A6
     */
    public function printKokarde(EventParticipant $eventParticipant)
    {
        // kokarde memuat data peserta yang sama, jadi wajib lolos captcha juga
        if (!$this->isVerified($eventParticipant)) {
            return redirect()->route('public.participant.show', $eventParticipant->uuid);
        }

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

    /* ======================
     |  HELPER
     ====================== */
    private function verifiedKey(EventParticipant $eventParticipant): string
    {
        return 'participant_verified_' . $eventParticipant->uuid;
    }

    private function isVerified(EventParticipant $eventParticipant): bool
    {
        $verifiedAt = session($this->verifiedKey($eventParticipant));

        return $verifiedAt
            && (now()->timestamp - (int) $verifiedAt) <= self::VERIFIED_TTL_MINUTES * 60;
    }
}