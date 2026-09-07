<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'name' => 'Pramana Yuda Sayeti, S.Kom',
                'username' => '199407292022031002',
                'email' => '199407292022031002@kemenag.go.id',
                'password' => Hash::make('congobrazzaville772'),
                'updated_at' => \Carbon\Carbon::now(),
                'role_id' => \App\Enums\RoleType::SUPERADMIN->value,
                'can_multiple_role' => true,
            ],
        ];


        // DB::table('users')->insert($data);

        foreach ($data as $key => $item) {
            \App\Models\User::firstOrCreate(
                ['username' => $item['username']],
                $item
            );
        }
    }
}
