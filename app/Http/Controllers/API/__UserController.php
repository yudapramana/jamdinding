<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class __UserController extends Controller
{
    public function index(Request $request)
    {
        $search   = $request->get('search');
        $eventId  = $request->get('event_id');
        $perPage  = (int) $request->get('per_page', 10);

        $query = User::query()
            ->with(['role', 'event', 'employee'])
            ->whereHas('role', function ($q) {
                $q->where('slug', '!=', 'DEWAN_HAKIM'); // ⬅️ exclude
            })
            ->orderBy('username');

        // hanya user dengan event_id tertentu
        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        // pencarian
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%");
            });
        }

        return response()->json(
            $query->paginate($perPage)
        );
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', 'unique:users,email'],
            'username'  => ['required', 'string', 'max:50', 'unique:users,username'],
            'password'  => ['required', 'string', 'min:8'],
            'role_id'   => ['nullable', 'exists:roles,id'],
            'event_id'  => ['nullable', 'exists:events,id'],
            'avatar'    => ['nullable', 'string'],
            'id_employee' => ['nullable', 'integer'],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dibuat.',
            'data'    => $user->load(['role', 'event', 'employee']),
        ], 201);
    }

    public function show(User $user)
    {
        return response()->json(
            $user->load(['role', 'event', 'employee'])
        );
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'      => ['sometimes', 'required', 'string', 'max:255'],
            'email'     => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'username'  => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'password'  => ['nullable', 'string', 'min:8'],
            'role_id'   => ['nullable', 'exists:roles,id'],
            'event_id'  => ['nullable', 'exists:events,id'],
            'avatar'    => ['nullable', 'string'],
            'id_employee' => ['nullable', 'integer'],
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil diperbarui.',
            'data'    => $user->load(['role', 'event', 'employee']),
        ]);
    }

    public function destroy(User $user)
    {
        // Kalau mau, bisa cegah hapus superadmin di sini
        if ($user->role && $user->role->slug === 'superadmin') {
            return response()->json([
                'success' => false,
                'message' => 'User SUPERADMIN tidak boleh dihapus.',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dihapus.',
        ]);
    }


    public function generateUsersByEvent(Request $request, Event $event)
    {
        // opsional: cek hak akses di sini
        // $this->authorize('manage-event-users', $event);

        // validasi role_id yang akan dipakai untuk semua user hasil generate
        $validated = $request->validate([
            'role_id'          => 'required|exists:roles,id',
            'default_password' => 'nullable|string|min:4',
            'email_domain'     => 'nullable|string',
        ]);

        $roleId = $validated['role_id'];

        // hanya boleh untuk tingkat province atau regency
        if (!in_array($event->event_level, ['province', 'regency'])) {
            return response()->json([
                'message' => 'Generate user hanya didukung untuk event tingkat province atau regency.',
            ], 422);
        }

        // password default boleh dikirim dari frontend, kalau tidak ada pakai event_key
        $plainPassword   = $validated['default_password'] ?? $event->event_key;
        $hashedPassword  = Hash::make($plainPassword);

        $kodeEvent       = $event->event_key;                    // contoh: mtq-prov-sumbar-2025
        $defaultDomain   = $validated['email_domain'] ?? 'mtq.local';
        $createdCount    = 0;
        $skippedCount    = 0;
        $role = Role::find($roleId);

        if ($event->event_level === 'province') {
            // Ambil semua kabupaten/kota di province_id event ini
            if (!$event->province_id) {
                return response()->json([
                    'message' => 'Event tingkat province harus memiliki province_id.',
                ], 422);
            }

            $regions = DB::table('regencies')
                ->where('province_id', $event->province_id)
                ->orderBy('name')
                ->get();

            foreach ($regions as $regency) {
                // Nama user: KODE_EVENT - Nama Kabupaten/Kota
                $name = strtoupper($role->name .' '.  $regency->name);

                // Username & email dibikin unik dengan ID wilayah
                // contoh: mtq-prov-sumbar-2025_kab_1301
                // $username = Str::slug($kodeEvent . '_kab_' . $regency->id, '_');
                $username = Str::slug($role->name .'_'. $regency->id, '_');

                // contoh email: mtq-prov-sumbar-2025_kab_1301@mtq.local
                $email = $username . '@' . $defaultDomain;

                // kalau user dengan username + event_id sudah ada → skip
                $existing = User::where('event_id', $event->id)
                    ->where('username', $username)
                    ->where('role_id', $roleId)
                    ->first();

                if ($existing) {
                    $skippedCount++;
                    continue;
                }

                User::create([
                    'name'              => $name,
                    'email'             => $email,
                    'username'          => $username,
                    'password'          => $hashedPassword,
                    'avatar'            => null,
                    'role_id'           => $roleId,    // ⬅️ role yang dipilih
                    'event_id'          => $event->id,
                    'province_id'       => $event->province_id,
                    'regency_id'        => $regency->id,
                    'district_id'       => null,
                    'village_id'        => null
                ]);

                $createdCount++;
            }

        } elseif ($event->event_level === 'regency') {

            // Ambil semua kecamatan di regency_id event ini
            if (!$event->regency_id) {
                return response()->json([
                    'message' => 'Event tingkat regency harus memiliki regency_id.',
                ], 422);
            }

            $districts = DB::table('districts')
                ->where('regency_id', $event->regency_id)
                ->orderBy('name')
                ->get();

            foreach ($districts as $district) {
                // Nama user: KODE_EVENT - Nama Kecamatan
                $name = strtoupper($role->name .' '.  $district->name);

                // Username & email: mtq-prov-sumbar-2025_kec_130101
                // $username = Str::slug($kodeEvent . '_kec_' . $district->id, '_');
                $username = Str::slug($role->name .'_'. $district->id, '_');

                $email    = $username . '@' . $defaultDomain;

                $existing = User::where('event_id', $event->id)
                    ->where('username', $username)
                    ->where('role_id', $roleId)
                    ->first();

                if ($existing) {
                    $skippedCount++;
                    continue;
                }

                User::create([
                    'name'      => $name,
                    'email'     => $email,
                    'username'  => $username,
                    'password'  => $hashedPassword,
                    'avatar'    => null,
                    'role_id'   => $roleId,   // ⬅️ role yang dipilih
                    'event_id'  => $event->id,
                    'province_id'       => $event->province_id,
                    'regency_id'        => $event->regency_id,
                    'district_id'       => $district->id,
                    'village_id'        => null
                ]);

                $createdCount++;
            }
        }

        return response()->json([
            'message'              => 'Generate user selesai.',
            'event_id'             => $event->id,
            'event_level'        => $event->event_level,
            'created'              => $createdCount,
            'skipped'              => $skippedCount,
            'role_id_used'         => $roleId,
            'default_password_used'=> $plainPassword,
        ]);
    }


}
