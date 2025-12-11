<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class _RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Definisi PERMISSIONS
        $perms = [
            ['name' => 'Kelola Master',                 'slug' => 'master.manage'],
            ['name' => 'Kelola Master Tahapan',         'slug' => 'master.manage.stage'],
            ['name' => 'Kelola Master Cabang',          'slug' => 'master.manage.group'],
            ['name' => 'Kelola Master Kategori',        'slug' => 'master.manage.category'],
            ['name' => 'Kelola Master Golongan',        'slug' => 'master.manage.branch'],
            ['name' => 'Kelola Master Level',           'slug' => 'master.manage.level'],

            ['name' => 'Kelola Event',                  'slug' => 'event.manage'],
            ['name' => 'Kelola Event Tahapan',          'slug' => 'event.manage.stage'],
            ['name' => 'Kelola Event Golongan',         'slug' => 'event.manage.branch'],
            ['name' => 'Kelola Event',                  'slug' => 'event.manage'],
            ['name' => 'Kelola Event User',             'slug' => 'event.manage.user'],

            ['name' => 'Kelola Peserta',                'slug' => 'participant.manage'],
            ['name' => 'Kelola Repositori Peserta',     'slug' => 'participant.manage.repository'],
            ['name' => 'Kelola Pendaftaran Peserta',    'slug' => 'participant.manage.register'],
            ['name' => 'Kelola Daftar Ulang Peserta',   'slug' => 'participant.manage.reregister'],
        ];

        // Simpan / ambil permissions, index by slug
        $permissions = [];
        foreach ($perms as $p) {
            $permissions[$p['slug']] = Permission::firstOrCreate(
                ['slug' => $p['slug']],
                ['name' => $p['name']]
            );
        }

        // 2. Definisi ROLES
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

        // 3. MAPPING PERMISSION PER ROLE

        // SUPERADMIN: semua permission
        $roleModels['superadmin']->permissions()->sync(
            collect($permissions)->pluck('id')->all()
        );

        // ADMIN_EVENT: semua yang berkaitan dengan EVENT
        // (kalau mau dia juga bisa kelola master, tinggal tambahkan 'master.*' ke array)
        $adminEventSlugs = [
            'event.manage',
            'event.manage.stage',
            'event.manage.branch',
            'event.manage.user',
            'participant.manage',
            'participant.manage.repository',
            'participant.manage.register'
        ];

        $roleModels['admin_event']->permissions()->sync(
            collect($permissions)
                ->whereIn('slug', $adminEventSlugs)
                ->pluck('id')
                ->all()
        );

        // PENDAFTARAN: khusus peserta & mandat (diwakili event.manage.user)
        $pendaftaranSlugs = [
            'participant.manage',
            'participant.manage.repository',
            'participant.manage.register'
        ];

        $roleModels['pendaftaran']->permissions()->sync(
            collect($permissions)
                ->whereIn('slug', $pendaftaranSlugs)
                ->pluck('id')
                ->all()
        );

        // VERIFIKATOR, DEWAN_HAKIM, PANITERA
        // sementara tanpa permission (bisa diisi kemudian kalau sudah ada slug spesifik)
        $verifikatorSlugs = [
            'participant.manage',
            'participant.manage.register',
            'participant.manage.reregister'
        ];

        $roleModels['verifikator']->permissions()->sync(
            collect($permissions)
                ->whereIn('slug', $verifikatorSlugs)
                ->pluck('id')
                ->all()
        );


        $roleModels['dewan_hakim']->permissions()->sync([]);
        $roleModels['panitera']->permissions()->sync([]);
    }
}
