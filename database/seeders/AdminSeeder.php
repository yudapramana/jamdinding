<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Enums\RoleType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $nipadmins = [
            '199407292022031002', // Yud
        ];

        $users = User::whereIn('username', $nipadmins)->get();

        foreach ($users as $key => $user) {
            if($user->username == '199407292022031002') {
                $user->update([
                    'role_id' => RoleType::SUPERADMIN->value,
                    'can_multiple_role' => true
                ]);
        
                $user->roles()->syncWithoutDetaching([
                    Role::where('name', 'SUPERADMIN')->first()->id,
                    Role::where('name', 'ADMIN_EVENT')->first()->id,
                    Role::where('name', 'PENDAFTARAN')->first()->id,
                ]);
            } else {
                $user->update([
                    'role_id' => RoleType::ADMIN_EVENT->value,
                    'can_multiple_role' => true
                ]);
        
                $user->roles()->syncWithoutDetaching([
                    Role::where('name', 'ADMIN_EVENT')->first()->id,
                ]);
            }
            
            
        }

        
    }
}
