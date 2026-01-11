<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\EventGroup;
use App\Models\MasterGroup;
use App\Models\EventJudgePanel;

class _EventGroupSeeder extends Seeder
{
    public function run(): void
    {
        $event = Event::first();

        if (! $event) {
            $this->command?->error("Tidak ada data event.");
            return;
        }

        $masterGroups = MasterGroup::orderBy('branch_id')
            ->orderBy('order_number')
            ->get();

        if ($masterGroups->isEmpty()) {
            $this->command?->error("Tidak ada data master_groups.");
            return;
        }

        $panels = EventJudgePanel::where('event_id', $event->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($panels->isEmpty()) {
            $this->command?->error("Tidak ada event_judge_panels aktif.");
            return;
        }

        $this->command?->info(
            "Mengisi event_groups untuk event {$event->event_name}"
        );

        $panelCount = $panels->count();
        $order      = 1;

        foreach ($masterGroups as $index => $mg) {

            // ROUND ROBIN panel assignment
            $panel = $panels[$index % $panelCount];

            EventGroup::updateOrCreate(
                [
                    'event_id'  => $event->id,
                    'branch_id' => $mg->branch_id,
                    'group_id'  => $mg->group_id,
                ],
                [
                    'event_judge_panel_id' => $panel->id,

                    'branch_name' => $mg->branch_name,
                    'group_name'  => $mg->group_name,
                    'full_name'   => $mg->full_name,

                    'max_age'     => $mg->max_age ?? 0,
                    'is_team'     => (bool) $mg->is_team,

                    'status'      => 'active',
                    'use_custom_judges' => false,
                    'judge_assignment_mode' => 'BY_PANEL',

                    'order_number' => $order++,
                ]
            );
        }

        $this->command?->info(
            "✔ Seeder selesai: {$masterGroups->count()} event_groups → {$panelCount} panel (round-robin)"
        );
    }
}
