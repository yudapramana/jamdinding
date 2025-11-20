<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $search  = $request->get('search');
        $perPage = (int) $request->get('per_page', 10);
        $user    = $request->user();

        $query = Event::query()
            ->orderByDesc('tanggal_mulai');

        // 🔍 filter search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_event', 'like', "%{$search}%")
                  ->orWhere('nama_aplikasi', 'like', "%{$search}%")
                  ->orWhere('lokasi_event', 'like', "%{$search}%");
            });
        }

        // 🔒 BATASAN AKSES: SUPERADMIN vs ROLE LAIN
        $roleSlug = optional($user->role)->slug ?? null;

        if ($roleSlug !== 'superadmin') {
            // ambil event_id dari request atau dari user (kalau ada)
            $eventId = $request->get('event_id') ?? $user->event_id ?? null;

            if ($eventId) {
                $query->where('id', $eventId);
            } else {
                // kalau tidak ada event_id → jangan tampilkan apa-apa
                $query->whereRaw('1 = 0');
            }
        }

        $events = $query->paginate($perPage);

        return response()->json($events);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $roleSlug = optional($user->role)->slug ?? null;

        // Optional: hanya superadmin yang boleh create
        if ($roleSlug !== 'superadmin') {
            return response()->json([
                'message' => 'Hanya SUPERADMIN yang boleh membuat event.',
            ], 403);
        }

        $data = $request->validate([
            'event_key'             => ['required', 'string', 'max:100', 'unique:events,event_key'],
            'nama_event'            => ['required', 'string', 'max:255'],
            'nama_aplikasi'         => ['required', 'string', 'max:255'],
            'lokasi_event'          => ['nullable', 'string', 'max:255'],
            'tagline'               => ['nullable', 'string', 'max:255'],
            'token_hasil_penilaian' => ['nullable', 'string', 'max:255'],

            'tanggal_mulai'         => ['required', 'date'],
            'tanggal_selesai'       => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'tanggal_batas_umur'    => ['nullable', 'date'],

            'logo_event'            => ['nullable', 'string', 'max:255'],
            'logo_sponsor_1'        => ['nullable', 'string', 'max:255'],
            'logo_sponsor_2'        => ['nullable', 'string', 'max:255'],
            'logo_sponsor_3'        => ['nullable', 'string', 'max:255'],

            'tingkat_event'         => ['required', Rule::in(['nasional', 'provinsi', 'kabupaten_kota', 'kecamatan'])],

            'province_id'           => ['nullable', 'exists:provinces,id'],
            'regency_id'            => ['nullable', 'exists:regencies,id'],
            'district_id'           => ['nullable', 'exists:districts,id'],

            'is_active'             => ['boolean'],
        ]);

        $event = Event::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Event created successfully.',
            'data'    => $event,
        ], 201);
    }

    public function show(Event $event)
    {
        return response()->json($event);
    }

    public function update(Request $request, Event $event)
    {
        $user = $request->user();
        $roleSlug = optional($user->role)->slug ?? null;

        if ($roleSlug !== 'superadmin') {
            // opsional: role selain superadmin hanya boleh update event miliknya
            if ($user->event_id && $user->event_id !== $event->id) {
                return response()->json([
                    'message' => 'Anda tidak berhak mengubah event ini.',
                ], 403);
            }
        }

        $data = $request->validate([
            'event_key'             => ['sometimes', 'required', 'string', 'max:100', Rule::unique('events','event_key')->ignore($event->id)],
            'nama_event'            => ['sometimes', 'required', 'string', 'max:255'],
            'nama_aplikasi'         => ['sometimes', 'required', 'string', 'max:255'],
            'lokasi_event'          => ['nullable', 'string', 'max:255'],
            'tagline'               => ['nullable', 'string', 'max:255'],
            'token_hasil_penilaian' => ['nullable', 'string', 'max:255'],

            'tanggal_mulai'         => ['nullable', 'date'],
            'tanggal_selesai'       => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'tanggal_batas_umur'    => ['nullable', 'date'],

            'logo_event'            => ['nullable', 'string', 'max:255'],
            'logo_sponsor_1'        => ['nullable', 'string', 'max:255'],
            'logo_sponsor_2'        => ['nullable', 'string', 'max:255'],
            'logo_sponsor_3'        => ['nullable', 'string', 'max:255'],

            'tingkat_event'         => ['nullable', Rule::in(['nasional', 'provinsi', 'kabupaten_kota', 'kecamatan'])],

            'province_id'           => ['nullable', 'exists:provinces,id'],
            'regency_id'            => ['nullable', 'exists:regencies,id'],
            'district_id'           => ['nullable', 'exists:districts,id'],

            'is_active'             => ['boolean'],
        ]);

        $event->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully.',
            'data'    => $event,
        ]);
    }

    public function destroy(Request $request, Event $event)
    {
        $user = $request->user();
        $roleSlug = optional($user->role)->slug ?? null;

        if ($roleSlug !== 'superadmin') {
            return response()->json([
                'message' => 'Hanya SUPERADMIN yang boleh menghapus event.',
            ], 403);
        }

        $event->delete();

        return response()->json([
            'success' => true,
            'message' => 'Event deleted successfully.',
        ]);
    }
}
