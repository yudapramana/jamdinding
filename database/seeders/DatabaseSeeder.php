<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);


        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $this->call(UsersTableSeeder::class);
        $this->call(EventSeeder::class);


        // DB::statement('SET FOREIGN_KEY_CHECKS=1');
        // $this->call(ReportsTableSeeder::class);
        // $this->call(WorksTableSeeder::class);

        // $this->call(WorkUnitSeeder::class);
        // $this->call(EmployeeTableSeeder::class);
        $this->call(SettingsTableSeeder::class);
        $this->call(DocTypesTableSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(AdminSeeder::class);
        $this->call(StageSeeder::class);
        $this->call(MasterCompetitionGroupSeeder::class);
        $this->call(MasterCompetitionCategorySeeder::class);
        $this->call(MasterCompetitionBranchSeeder::class);
        $this->call(RolePermissionSeeder::class);
        $this->call(IndonesiaLocationSeeder::class);
        $this->call(ParticipantsFromExcelSeeder::class);
    }
}
