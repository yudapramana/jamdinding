<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\EventBranch;
use App\Models\MasterBranch;

class _EventBranchSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil event pertama
        $event = Event::first();

        if (! $event) {
            $this->command?->error("  Tidak ada data event. Seeder event_branches dibatalkan.");
            return;
        }

        // Ambil semua master_branches
        $masterBranches = MasterBranch::all();

        if ($masterBranches->isEmpty()) {
            $this->command?->error("  Tidak ada data master_branches. Seeder event_branches dibatalkan.");
            return;
        }

        $this->command?->info("  Mengisi event_branches untuk event: {$event->event_name}");

        foreach ($masterBranches as $index => $master) {
            EventBranch::updateOrCreate(
                [
                    'event_id'  => $event->id,
                    'branch_id' => $master->branch_id,
                ],
                [
                    'branch_name'  => $master->branch_name,
                    'full_name'    => $master->full_name,
                    'status'       => 'active',
                    'order_number' => $index + 1,
                ]
            );
        }

        $this->command?->info("✔ Seeder event_branches selesai. Total: {$masterBranches->count()} cabang disalin.");
    }
}
