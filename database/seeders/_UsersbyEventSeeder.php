<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class _UsersbyEventSeeder extends Seeder
{
    public function run(): void
    {
        $eventId = 1;
        $defaultPassword = '12345678';
        $defaultDomain = 'mtq.local';

        $event = Event::find($eventId);
        if (!$event) {
            $this->command?->error("Event id={$eventId} tidak ditemukan.");
            return;
        }

        if (!in_array($event->event_level, ['province', 'regency'])) {
            $this->command?->error("Generate user hanya didukung untuk event tingkat province/regency. Event ini: {$event->event_level}");
            return;
        }

        $hashedPassword = Hash::make($defaultPassword);

        // Role yang akan digenerate
        $roleSlugs = ['pendaftaran', 'verifikator'];

        $roles = Role::query()
            ->whereIn('slug', array_merge($roleSlugs, ['panitera']))
            ->get()
            ->keyBy('slug');

        // =========================
        // TAMBAHAN: 5 USER PANITERA
        // =========================
        if (!$roles->has('panitera')) {
            $this->command?->warn("Role slug 'panitera' tidak ditemukan. Skip create user panitera.");
        } else {
            $paniteraRole = $roles['panitera'];

            $createdPanitera = 0;
            $skippedPanitera = 0;

            for ($i = 1; $i <= 5; $i++) {
                $uname = "panitera1301_{$i}";
                $email = $uname . '@' . $defaultDomain;

                $exists = User::query()
                    ->where('event_id', $event->id)
                    ->where('username', $uname)
                    ->where('role_id', $paniteraRole->id)
                    ->exists();

                if ($exists) {
                    $skippedPanitera++;
                    continue;
                }

                // Wilayah mengikuti level event biar rapi
                $provinceId = $event->province_id ?? null;
                $regencyId  = $event->regency_id ?? null;
                $districtId = null;

                if ($event->event_level === 'regency') {
                    // untuk event regency: panitera level kabupaten (tanpa district)
                    $districtId = null;
                } else {
                    // event province: panitera level provinsi (tanpa regency/district)
                    $regencyId = null;
                    $districtId = null;
                }

                User::create([
                    'name'        => $uname,     // sesuai request
                    'email'       => $email,
                    'username'    => $uname,
                    'password'    => $hashedPassword,
                    'avatar'      => null,
                    'role_id'     => $paniteraRole->id,
                    'event_id'    => $event->id,
                    'province_id' => $provinceId,
                    'regency_id'  => $regencyId,
                    'district_id' => $districtId,
                    'village_id'  => null,
                ]);

                $createdPanitera++;
            }

            $this->command?->info("Seeder UsersByEventSeeder: role=panitera created={$createdPanitera} skipped={$skippedPanitera}");
        }

        // =========================
        // EXISTING: pendaftaran & verifikator per wilayah
        // =========================
        foreach ($roleSlugs as $slug) {
            if (!$roles->has($slug)) {
                $this->command?->warn("Role slug '{$slug}' tidak ditemukan. Skip.");
                continue;
            }

            $role = $roles[$slug];

            $createdCount = 0;
            $skippedCount = 0;

            if ($event->event_level === 'province') {
                if (!$event->province_id) {
                    $this->command?->error("Event level province harus punya province_id.");
                    continue;
                }

                $regions = DB::table('regencies')
                    ->where('province_id', $event->province_id)
                    ->orderBy('name')
                    ->get();

                foreach ($regions as $regency) {
                    $name = strtoupper($role->name . ' ' . $regency->name);
                    $username = Str::slug($role->name . '_' . $regency->id, '_');
                    $email = $username . '@' . $defaultDomain;

                    $exists = User::query()
                        ->where('event_id', $event->id)
                        ->where('username', $username)
                        ->where('role_id', $role->id)
                        ->exists();

                    if ($exists) {
                        $skippedCount++;
                        continue;
                    }

                    User::create([
                        'name'        => $name,
                        'email'       => $email,
                        'username'    => $username,
                        'password'    => $hashedPassword,
                        'avatar'      => null,
                        'role_id'     => $role->id,
                        'event_id'    => $event->id,
                        'province_id' => $event->province_id,
                        'regency_id'  => $regency->id,
                        'district_id' => null,
                        'village_id'  => null,
                    ]);

                    $createdCount++;
                }

            } elseif ($event->event_level === 'regency') {
                if (!$event->regency_id) {
                    $this->command?->error("Event level regency harus punya regency_id.");
                    continue;
                }

                $districts = DB::table('districts')
                    ->where('regency_id', $event->regency_id)
                    ->orderBy('name')
                    ->get();

                foreach ($districts as $district) {
                    $name = strtoupper($role->name . ' ' . $district->name);
                    $username = Str::slug($role->name . '_' . $district->id, '_');
                    $email = $username . '@' . $defaultDomain;

                    $exists = User::query()
                        ->where('event_id', $event->id)
                        ->where('username', $username)
                        ->where('role_id', $role->id)
                        ->exists();

                    if ($exists) {
                        $skippedCount++;
                        continue;
                    }

                    User::create([
                        'name'        => $name,
                        'email'       => $email,
                        'username'    => $username,
                        'password'    => $hashedPassword,
                        'avatar'      => null,
                        'role_id'     => $role->id,
                        'event_id'    => $event->id,
                        'province_id' => $event->province_id,
                        'regency_id'  => $event->regency_id,
                        'district_id' => $district->id,
                        'village_id'  => null,
                    ]);

                    $createdCount++;
                }
            }

            $this->command?->info("Seeder UsersByEventSeeder: role={$role->slug} created={$createdCount} skipped={$skippedCount}");
        }

        $this->command?->info("Selesai generate user untuk event_id={$eventId} dengan password default={$defaultPassword}");
    }
}
