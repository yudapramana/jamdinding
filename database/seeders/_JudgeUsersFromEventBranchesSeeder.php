<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use App\Models\User;
use App\Models\Role;
use App\Models\EventBranch;

class _JudgeUsersFromEventBranchesSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $eventId = 1;                 // EVENT AKTIF
        $defaultPassword = 'hakim123';

        $judgeRole = Role::where('name', 'DEWAN_HAKIM')->first();
        if (!$judgeRole) {
            $this->command->error('Role DEWAN_HAKIM tidak ditemukan.');
            return;
        }

        $eventBranches = EventBranch::query()
            ->where('event_id', $eventId)
            ->select('id', 'branch_name')
            ->orderBy('branch_name')
            ->get();

        if ($eventBranches->isEmpty()) {
            $this->command->warn("Tidak ada event_branches untuk event_id = {$eventId}");
            return;
        }

        DB::transaction(function () use (
            $eventBranches,
            $judgeRole,
            $eventId,
            $defaultPassword,
            $now
        ) {
            foreach ($eventBranches as $eventBranch) {
                $branchName = trim($eventBranch->branch_name);
                $slug = Str::slug($branchName, '_');

                for ($i = 1; $i <= 3; $i++) {
                    $username = "hakim_{$slug}_{$i}";
                    $email    = "{$username}@example.local";

                    // 1️⃣ Buat / ambil user hakim
                    $user = User::firstOrCreate(
                        ['username' => $username],
                        [
                            'name' => "Hakim {$branchName} {$i}",
                            'email' => $email,
                            'password' => Hash::make($defaultPassword),
                            'event_id' => $eventId,
                            'role_id' => null,
                            'can_multiple_role' => true,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );

                    // pastikan event_id konsisten
                    if ((int) $user->event_id !== $eventId) {
                        $user->event_id = $eventId;
                        $user->save();
                    }

                    // 2️⃣ Attach role DEWAN_HAKIM
                    $user->roles()->syncWithoutDetaching([
                        $judgeRole->id,
                    ]);

                    // 3️⃣ Masukkan ke event_branch_judges
                    DB::table('event_branch_judges')->updateOrInsert(
                        [
                            'event_branch_id' => $eventBranch->id,
                            'user_id' => $user->id,
                        ],
                        [
                            'is_chief' => ($i === 1), // hakim pertama = ketua
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );

                    $this->command->info(
                        "Hakim {$branchName} #{$i} → {$user->name}" .
                        ($i === 1 ? ' (Ketua)' : '')
                    );
                }
            }
        });

        $this->command->info('Seeder hakim + majelis cabang (event_id=1) selesai.');
    }
}
