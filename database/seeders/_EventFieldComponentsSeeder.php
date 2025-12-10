<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\EventGroup;
use App\Models\EventFieldComponent;
use App\Models\MasterGroup;
use App\Models\MasterFieldComponent;

class _EventFieldComponentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ambil event pertama
        $event = Event::first();

        if (! $event) {
            $this->command?->error('  Tidak ada data event. Seeder event_field_components dibatalkan.');
            return;
        }

        // 2. Ambil semua event_groups milik event ini
        $eventGroups = EventGroup::where('event_id', $event->id)
            ->orderBy('branch_id')
            ->orderBy('group_id')
            ->orderBy('order_number')
            ->get();

        if ($eventGroups->isEmpty()) {
            $this->command?->error("  Tidak ada data event_groups untuk event: {$event->id}. Seeder event_field_components dibatalkan.");
            return;
        }

        $this->command?->info("  Mengisi event_field_components untuk event: {$event->event_name}");

        foreach ($eventGroups as $eg) {
            // 3. Cari master_group berdasarkan branch_id + group_id
            $masterGroup = MasterGroup::where('branch_id', $eg->branch_id)
                ->where('group_id', $eg->group_id)
                ->first();

            if (! $masterGroup) {
                $this->command?->warn(
                    "MasterGroup tidak ditemukan untuk branch_id={$eg->branch_id}, ".
                    "group_id={$eg->group_id} (event_group_id={$eg->id}). Dilewati."
                );
                continue;
            }

            // 4. Ambil semua master_field_components milik master_group ini
            $masterFieldComponents = MasterFieldComponent::where('master_group_id', $masterGroup->id)
                ->orderBy('default_order')
                ->get();

            if ($masterFieldComponents->isEmpty()) {
                $this->command?->warn(
                    "Tidak ada master_field_components untuk master_group_id={$masterGroup->id} ".
                    "(branch={$masterGroup->branch_name}, group={$masterGroup->group_name})."
                );
                continue;
            }

            $this->command?->info(
                "- Mengisi komponen nilai untuk event_group_id={$eg->id} ({$eg->full_name}) ".
                "dari master_group_id={$masterGroup->id}."
            );

            $order = 1;

            foreach ($masterFieldComponents as $mfc) {
                EventFieldComponent::updateOrCreate(
                    [
                        'event_group_id' => $eg->id,
                        'field_id'       => $mfc->field_id,
                    ],
                    [
                        'event_group_name' => $eg->full_name,
                        'field_name'       => $mfc->field_name,

                        'weight'           => $mfc->default_weight ?? 0,
                        'max_score'        => $mfc->default_max_score ?? 100,
                        'order_number'     => $mfc->default_order ?? $order++,
                    ]
                );
            }
        }

        $this->command?->info('✔ Seeder event_field_components selesai dijalankan.');
    }
}
