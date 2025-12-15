<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class _RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Definisi PERMISSIONS
        $perms = [
            ['name' => 'Manage Core',                               'slug' => 'manage.core'],
            ['name' => 'Manage Core Branch',                        'slug' => 'manage.core.branches'],
            ['name' => 'Manage Core Fields',                        'slug' => 'manage.core.fields'],
            ['name' => 'Manage Core Permission',                    'slug' => 'manage.core.permissions'],

            ['name' => 'Manage Master',                             'slug' => 'manage.master'],
            ['name' => 'Manage Master Branch',                      'slug' => 'manage.master.branches'],
            ['name' => 'Manage Master Group',                       'slug' => 'manage.master.groups'],
            ['name' => 'Manage Master Category',                    'slug' => 'manage.master.categories'],
            ['name' => 'Manage Master Field Component',             'slug' => 'manage.master.fields-components'],
            ['name' => 'Manage Master Participant',                 'slug' => 'manage.master.participants'],

            ['name' => 'Manage Event',                              'slug' => 'manage.event'],
            ['name' => 'Manage Event Event',                        'slug' => 'manage.event.events'],
            ['name' => 'Manage Event Stage',                        'slug' => 'manage.event.stages'],
            ['name' => 'Manage Event Branch',                       'slug' => 'manage.event.branches'],
            ['name' => 'Manage Event Group',                        'slug' => 'manage.event.groups'],
            ['name' => 'Manage Event Category',                     'slug' => 'manage.event.categories'],
            ['name' => 'Manage Event Field Components',             'slug' => 'manage.event.fields-components'],
            ['name' => 'Manage Event User',                         'slug' => 'manage.event.user'],

            ['name' => 'Manage Event Participant Bank Data',        'slug' => 'manage.event.participant.bank-data'],
            ['name' => 'Manage Event Participant Registration',     'slug' => 'manage.event.participant.registration'],
            ['name' => 'Manage Event Participant Reregistration',   'slug' => 'manage.event.participant.reregistration'],
            ['name' => 'Manage Event Participant Final',            'slug' => 'manage.event.participant.final'],

            ['name' => 'Manage Event Judges',                       'slug' => 'manage.event.judges'],
            ['name' => 'Manage Event Judges User',                  'slug' => 'manage.event.judges.user'],
            ['name' => 'Manage Event Judges Panel',                 'slug' => 'manage.event.judges-panel'],

            ['name' => 'Manage Event Scoring Competition',          'slug' => 'manage.event.scoring.competitions'],
            ['name' => 'Manage Event Scoring Input Default',        'slug' => 'manage.event.scoring.input-default'],
            ['name' => 'Manage Event Scoring Input Specific',       'slug' => 'manage.event.scoring.input-specific'],
        ];

        // Simpan / ambil permissions, index by slug
        $permissions = [];
        foreach ($perms as $p) {
            $permissions[$p['slug']] = Permission::firstOrCreate(
                ['slug' => $p['slug']],
                ['name' => $p['name']]
            );
        }

        // 2) Definisi ROLES
        $roles = [
            'superadmin'  => ['name' => 'SUPERADMIN'],
            'admin_event' => ['name' => 'ADMIN_EVENT'],
            'pendaftaran' => ['name' => 'PENDAFTARAN'],
            'verifikator' => ['name' => 'VERIFIKATOR'],
            'dewan_hakim' => ['name' => 'DEWAN_HAKIM'],
            'panitera'    => ['name' => 'PANITERA'],
        ];

        $roleModels = [];
        foreach ($roles as $slug => $data) {
            $roleModels[$slug] = Role::firstOrCreate(
                ['slug' => $slug],
                ['name' => $data['name']]
            );
        }

        // helper
        $allPermissionIds = collect($permissions)->pluck('id');

        // 3) MAPPING PERMISSION PER ROLE

        // SUPERADMIN: semua permission
        $roleModels['superadmin']->permissions()->sync($allPermissionIds->all());

        // ADMIN_EVENT: semua permission KECUALI master.core*
        $adminEventIds = collect($permissions)
            ->reject(function ($perm) {
                // exclude: master.core, master.core.*
                return $perm->slug === 'master.core' || str_starts_with($perm->slug, 'master.core.');
            })
            ->pluck('id')
            ->all();

        $roleModels['admin_event']->permissions()->sync($adminEventIds);

        // PENDAFTARAN: hanya bank-data + registration
        $pendaftaranSlugs = [
            'manage.event.participant.bank-data',
            'manage.event.participant.registration',
            'manage.event.participant.final',
        ];

        $roleModels['pendaftaran']->permissions()->sync(
            collect($permissions)
                ->whereIn('slug', $pendaftaranSlugs)
                ->pluck('id')
                ->all()
        );

        // VERIFIKATOR: hanya registration + reregistration
        $verifikatorSlugs = [
            'manage.event.participant.registration',
            'manage.event.participant.reregistration',
            'manage.event.participant.final',
        ];

        $roleModels['verifikator']->permissions()->sync(
            collect($permissions)
                ->whereIn('slug', $verifikatorSlugs)
                ->pluck('id')
                ->all()
        );

        // PANITERA:
        $paniteraSlugs = [
            'manage.event.judges',
            'manage.event.judges.user',
            'manage.event.judges-panel',
            'manage.event.scoring.competitions',
            'manage.event.scoring.default',
            'manage.event.scoring.specific',
        ];

        $roleModels['panitera']->permissions()->sync(
                collect($permissions)
                    ->whereIn('slug', $paniteraSlugs)
                    ->pluck('id')
                    ->all()
            );

        // Lainnya kosong
        $roleModels['dewan_hakim']->permissions()->sync([]);
    }
}
