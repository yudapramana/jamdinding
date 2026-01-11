<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\EventJudgePanel;
use App\Models\EventLocation;
use App\Models\EventJudge;
use App\Models\EventJudgePanelMember;
use Illuminate\Support\Collection;

class _EventJudgePanelSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil event spesifik
        $events = Event::where('id', 1)->get();

        foreach ($events as $event) {

            // ===============================
            // Ambil lokasi event
            // ===============================
            $locations = EventLocation::where('event_id', $event->id)
                ->orderBy('id')
                ->get();

            if ($locations->isEmpty()) {
                $this->command->warn("⚠️ Event {$event->id} tidak punya lokasi");
                continue;
            }

            // ===============================
            // Ambil hakim event
            // ===============================
            $eventJudges = EventJudge::where('event_id', $event->id)
                ->where('is_active', true)
                ->pluck('id');

            if ($eventJudges->count() < 3) {
                $this->command->warn("⚠️ Event {$event->id} hakim kurang dari 3");
                continue;
            }

            // Shuffle sekali agar distribusi adil
            $eventJudges = $eventJudges->shuffle()->values();

            $locationCount = $locations->count();
            $existingCount = EventJudgePanel::where('event_id', $event->id)->count();

            // ===============================
            // Buat majelis (total 15)
            // ===============================
            for ($i = $existingCount + 1; $i <= 15; $i++) {

                $number = str_pad($i, 2, '0', STR_PAD_LEFT);
                $locationIndex = ($i - 1) % $locationCount;
                $eventLocationId = $locations[$locationIndex]->id;

                $panel = EventJudgePanel::firstOrCreate(
                    [
                        'event_id' => $event->id,
                        'name'     => "Majelis {$number}",
                    ],
                    [
                        'code'              => strtoupper($event->event_key) . "-MJ-{$number}",
                        'event_location_id' => $eventLocationId,
                        'notes'             => null,
                        'is_active'         => true,
                    ]
                );

                // ===============================
                // Tentukan jumlah hakim (3–6)
                // ===============================
                $memberCount = rand(3, min(6, $eventJudges->count()));

                // Ambil slice hakim (round-robin)
                $offset = ($i - 1) * 3 % $eventJudges->count();
                $judgesForPanel = $eventJudges
                    ->slice($offset, $memberCount)
                    ->values();

                // ===============================
                // Assign ke panel members
                // ===============================
                foreach ($judgesForPanel as $index => $eventJudgeId) {
                    EventJudgePanelMember::firstOrCreate(
                        [
                            'event_judge_panel_id' => $panel->id,
                            'event_judge_id'       => $eventJudgeId,
                        ],
                        [
                            'is_chief'    => $index === 0, // ketua majelis
                            'order_number'=> $index + 1,
                        ]
                    );
                }
            }

            $this->command->info("✅ Event {$event->id}: Majelis & anggota berhasil dibuat");
        }

        $this->command->info('🎉 EventJudgePanelSeeder selesai');
    }
}
