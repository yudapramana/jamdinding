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
            // Menu Core
            ['name' => 'Manage Menu Core',                              'slug' => 'manage.core'],
            // SubMenu Core
            ['name' => 'Manage Core Branch Branch Group Categories',    'slug' => 'manage.core.branches-groups-categories'],
            ['name' => 'Manage Core Fields',                            'slug' => 'manage.core.fields'],
            ['name' => 'Manage Core Permission',                        'slug' => 'manage.core.permissions'],
            ['name' => 'Manage Core Medal Rules',                       'slug' => 'manage.core.medal-rules'],

            // Menu Master
            ['name' => 'Manage Menu Master',                            'slug' => 'manage.master'],
            // SubMenu Master
            ['name' => 'Manage Master Branch',                          'slug' => 'manage.master.branches'],
            ['name' => 'Manage Master Group',                           'slug' => 'manage.master.groups'],
            ['name' => 'Manage Master Category',                        'slug' => 'manage.master.categories'],
            ['name' => 'Manage Master Field Component',                 'slug' => 'manage.master.field-components'],

            // Menu Event
            ['name' => 'Manage Menu Event',                             'slug' => 'manage.event'],
            // SubMenu Event
            ['name' => 'Manage Event Index',                            'slug' => 'manage.event.index'],
            ['name' => 'Manage Event Stage',                            'slug' => 'manage.event.stages'],
            ['name' => 'Manage Event Branch',                           'slug' => 'manage.event.branches'],
            ['name' => 'Manage Event Group',                            'slug' => 'manage.event.groups'],
            ['name' => 'Manage Event Category',                         'slug' => 'manage.event.categories'],
            ['name' => 'Manage Event User',                             'slug' => 'manage.event.user'],
            ['name' => 'Manage Event Medal Rules',                      'slug' => 'manage.event.medal-rules'],

            // Menu Participant
            ['name' => 'Manage Event Participant Menu',                 'slug' => 'manage.event.participant'],
            // SubMenu Participant
            ['name' => 'Manage Event Participant Bank Data',            'slug' => 'manage.event.participant.bank-data'],
            ['name' => 'Manage Event Participant Registration',         'slug' => 'manage.event.participant.registration'],
            ['name' => 'Manage Event Participant Reregistration',       'slug' => 'manage.event.participant.reregistration'],
            ['name' => 'Manage Event Participant Final',                'slug' => 'manage.event.participant.final'],

            // Menu Judges
            ['name' => 'Manage Event Judges',                           'slug' => 'manage.event.judges'],
            // SubMenu Judges
            ['name' => 'Manage Event Judges User',                      'slug' => 'manage.event.judges.users'],
            ['name' => 'Manage Event Judges Panel',                     'slug' => 'manage.event.judges.panels'],

            // Menu Scoring
            ['name' => 'Manage Event Scoring',                          'slug' => 'manage.event.scoring'],
            // SubMenu Scoring
            ['name' => 'Manage Event Scoring Field Components',         'slug' => 'manage.event.scoring.field-components'],
            ['name' => 'Manage Event Scoring Index',                    'slug' => 'manage.event.scoring.index'],
            ['name' => 'Manage Event Scoring Input Default',            'slug' => 'manage.event.scoring.input-default'],
            ['name' => 'Manage Event Scoring Input Specific',           'slug' => 'manage.event.scoring.input-specific'],

            // Menu Score
            ['name' => 'Manage Event Score',                            'slug' => 'manage.event.scores'],
            // SubMenu Score
            ['name' => 'Manage Event Score Selector',                   'slug' => 'manage.event.scores.select'],
            ['name' => 'Manage Event Score',                            'slug' => 'manage.event.scores.index'],
            ['name' => 'Manage Event Score Detail Selector',            'slug' => 'manage.event.scores.detail.select'],
            ['name' => 'Manage Event Score Detail',                     'slug' => 'manage.event.scores.detail.index'],

            // Menu Ranking
            ['name' => 'Manage Event Ranking',                          'slug' => 'manage.event.ranking'],
            // SubMenu Ranking
            ['name' => 'Manage Event Ranking Select',                   'slug' => 'manage.event.ranking.select'],
            ['name' => 'Manage Event Ranking Index',                    'slug' => 'manage.event.ranking.index'],

            // Menu Result
            ['name' => 'Manage Event Contingent',                       'slug' => 'manage.event.contingent'],
            // SubMenu Result 
            ['name' => 'Manage Event Contingent Standings',             'slug' => 'manage.event.contingent.standings'],
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
                return $perm->slug === 'manage.core' || str_starts_with($perm->slug, 'manage.core.') || $perm->slug === 'manage.master' || str_starts_with($perm->slug, 'manage.master.');
            })
            ->pluck('id')
            ->all();

        $roleModels['admin_event']->permissions()->sync($adminEventIds);

        // PENDAFTARAN: hanya bank-data + registration
        $pendaftaranSlugs = [
            'manage.event.participant',
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
            'manage.event.participant',
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
            'manage.event',
            'manage.event.judges',
            'manage.event.judges.users',
            'manage.event.judges.panels',
            'manage.event.field-components',
            'manage.event.scoring',
            'manage.event.scoring.index',
            'manage.event.scoring.input-default',
            'manage.event.scoring.input-specific',
            'manage.event.scores',
            'manage.event.scores.select',
            'manage.event.scores.index',
            'manage.event.scores.detail.select',
            'manage.event.scores.detail.index',
            'manage.event.ranking',
            'manage.event.ranking.select',
            'manage.event.ranking.index',
            'manage.event.results.index',
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
