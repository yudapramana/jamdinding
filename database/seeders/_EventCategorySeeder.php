<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\MasterCategory;

class _EventCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Ambil event pertama
        $event = Event::first();

        if (! $event) {
            $this->command?->error("  Tidak ada data event. Seeder event_categories dibatalkan.");
            return;
        }

        // Ambil semua master_categories (urutkan biar rapi)
        $masterCategories = MasterCategory::orderBy('branch_id')
            ->orderBy('group_id')
            ->orderBy('order_number')
            ->get();

        if ($masterCategories->isEmpty()) {
            $this->command?->error("  Tidak ada data master_categories. Seeder event_categories dibatalkan.");
            return;
        }

        $this->command?->info("  Mengisi event_categories untuk event: {$event->event_name}");

        $order = 1;

        foreach ($masterCategories as $mc) {
            EventCategory::updateOrCreate(
                [
                    'event_id'    => $event->id,
                    'branch_id'   => $mc->branch_id,
                    'group_id'    => $mc->group_id,
                    'category_id' => $mc->category_id,
                ],
                [
                    'branch_name'  => $mc->branch_name,
                    'group_name'   => $mc->group_name,
                    'category_name'=> $mc->category_name,
                    'full_name'    => $mc->full_name,
                    'status'       => 'active',
                    'order_number' => $order++,
                ]
            );
        }

        $this->command?->info("✔ Seeder event_categories selesai. Total: {$masterCategories->count()} kategori berhasil disalin.");
    }
}
