<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\EventGroup;
use App\Models\MasterGroup;

class _EventGroupSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil event pertama
        $event = Event::first();

        if (! $event) {
            $this->command?->error("  Tidak ada data event. Seeder event_groups dibatalkan.");
            return;
        }

        // Ambil semua master_groups
        $masterGroups = MasterGroup::orderBy('branch_id')->orderBy('order_number')->get();

        if ($masterGroups->isEmpty()) {
            $this->command?->error("  Tidak ada data master_groups. Seeder event_groups dibatalkan.");
            return;
        }

        $this->command?->info("  Mengisi event_groups untuk event: {$event->event_name}");

        $order = 1;

        foreach ($masterGroups as $mg) {
            EventGroup::updateOrCreate(
                [
                    'event_id'  => $event->id,
                    'branch_id' => $mg->branch_id,
                    'group_id'  => $mg->group_id,
                ],
                [
                    'branch_name'  => $mg->branch_name,
                    'group_name'   => $mg->group_name,
                    'full_name'    => $mg->full_name,
                    'max_age'      => $mg->max_age,              // default (bisa disesuaikan nanti)
                    'status'       => 'active',
                    'is_team'      => (bool)$mg->is_team,
                    'order_number' => $order++,
                ]
            );
        }

        $this->command?->info("✔ Seeder event_groups selesai. Total: {$masterGroups->count()} golongan berhasil disalin.");
    }
}
