<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\Stage;
use App\Models\EventStage;
use Carbon\Carbon;

class __EventStageSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil 1 event saja
        $event = Event::first();

        if (!$event) {
            $this->command->warn("⚠️  Tidak ada event ditemukan. Seeder dihentikan.");
            return;
        }

        // Ambil master stage
        $masterStages = Stage::orderBy('order_number')->get();

        if ($masterStages->isEmpty()) {
            $this->command->warn("⚠️  Tidak ada data stages ditemukan.");
            return;
        }

        // Mulai tanggal berdasarkan start_date event
        $currentStart = Carbon::parse($event->start_date)->startOfDay();

        foreach ($masterStages as $stage) {

            $days = $stage->days ?? 1;

            // Tanggal akhir
            $currentEnd = (clone $currentStart)->addDays($days - 1)->endOfDay();

            // Create event stage
            EventStage::create([
                'event_id'     => $event->id,
                'stage_id'     => $stage->id,
                'order_number' => $stage->order_number,
                'name'         => $stage->name,

                'start_date'   => $currentStart->format('Y-m-d'),
                'end_date'     => $currentEnd->format('Y-m-d'),

                'notes'        => null,
                'is_active'    => true,
            ]);

            // Tanggal mulai tahap berikutnya
            $currentStart = (clone $currentEnd)->addDay()->startOfDay();
        }

        $this->command->info("✔ EventStageSeeder selesai untuk event: {$event->event_name}");
    }
}
