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


        // DB::statement('SET FOREIGN_KEY_CHECKS=1');
        // $this->call(ReportsTableSeeder::class);
        // $this->call(WorksTableSeeder::class);

        // $this->call(WorkUnitSeeder::class);
        // $this->call(EmployeeTableSeeder::class);
        $this->call(SettingsTableSeeder::class);
        $this->call(DocTypesTableSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(AdminSeeder::class);
        $this->call(IndonesiaLocationSeeder::class);
        $this->call(__BranchSeeder::class);
        $this->call(__GroupSeeder::class);
        $this->call(__CategorySeeder::class);
        $this->call(__ListFieldSeeder::class);
        $this->call(__EventSeeder::class);
        $this->call(__StageSeeder::class);
        $this->call(__EventLocationSeeder::class);
        $this->call(__EventStageSeeder::class);
        $this->call(__MasterBranchSeeder::class);
        $this->call(__MasterGroupSeeder::class);
        $this->call(__MasterCategorySeeder::class);
        $this->call(__MasterFieldComponentsSeeder::class);
        $this->call(__RoundSeeder::class);
        $this->call(__MedalRuleSeeder::class);



        $this->call(_EventBranchSeeder::class);
        $this->call(_EventGroupSeeder::class);
        $this->call(_EventCategorySeeder::class);
        $this->call(_EventFieldComponentsSeeder::class);
        $this->call(_EventParticipantsSeeder::class);
        $this->call(_RolePermissionSeeder::class);
        $this->call(_UsersbyEventSeeder::class);
        // $this->call(_JudgeUsersFromEventBranchesSeeder::class);
        $this->call(_EventMedalRuleSeeder::class);
        $this->call(_EventJudgeSeeder::class);
        $this->call(_EventJudgePanelSeeder::class);
        // $this->call(_BootstrapEventJudgesSeeder::class);
        // $this->call(_BootstrapEventJudgePanelsSeeder::class);
        






        // $this->call(AssessmentFieldSeeder::class);
        // $this->call(MasterCompetitionGroupSeeder::class);
        // $this->call(MasterCompetitionCategorySeeder::class);
        // $this->call(MasterCompetitionBranchSeeder::class);
        // $this->call(ParticipantsFromExcelSeeder::class);
    }
}
