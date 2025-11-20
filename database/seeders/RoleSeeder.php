<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'SUPERADMIN',     'slug' => 'superadmin'],
            ['name' => 'ADMIN_EVENT',    'slug' => 'admin_event'],
            ['name' => 'PENDAFTARAN',    'slug' => 'pendaftaran'],
            ['name' => 'VERIFIKATOR',    'slug' => 'verifikator'],
            ['name' => 'DEWAN_HAKIM',    'slug' => 'dewan_hakim'],
            ['name' => 'PANITERA',       'slug' => 'panitera'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['slug' => $role['slug']],
                $role
            );
        }
    }
}
