<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\EventJudgePanel;
use App\Models\EventLocation;
use App\Models\EventJudge;
use App\Models\EventJudgePanelMember;

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
            // Ambil hakim event (beserta nama cabang lomba dari MasterJudge)
            // Diurutkan berdasarkan judge_code (JDG-01 s.d. JDG-12)
            // supaya urutan majelis konsisten dengan urutan cabang lomba.
            // ===============================
            $eventJudges = EventJudge::with('masterJudge')
                ->where('event_id', $event->id)
                ->where('is_active', true)
                ->orderBy('judge_code')
                ->get();

            if ($eventJudges->isEmpty()) {
                $this->command->warn("⚠️ Event {$event->id} belum punya dewan hakim");
                continue;
            }

            $locationCount = $locations->count();

            // ===============================
            // Buat 1 majelis per dewan hakim (1 hakim = 1 cabang lomba = 1 majelis = 1 ketua)
            // ===============================
            foreach ($eventJudges as $index => $eventJudge) {

                $i = $index + 1;
                $number = str_pad($i, 2, '0', STR_PAD_LEFT);

                // Nama cabang lomba diambil dari full_name master hakim
                // (sesuai seeder dewan hakim, full_name diisi nama cabang lomba)
                $branchName = $eventJudge->masterJudge->full_name ?? "Cabang {$number}";

                $locationIndex = ($i - 1) % $locationCount;
                $eventLocationId = $locations[$locationIndex]->id;

                $panel = EventJudgePanel::updateOrCreate(
                    [
                        'event_id' => $event->id,
                        'code'     => "MJL-{$number}",
                    ],
                    [
                        'name'              => "Majelis {$number} - {$branchName}",
                        'event_location_id' => $eventLocationId,
                        'notes'             => null,
                        'is_active'         => true,
                    ]
                );

                // ===============================
                // Assign 1 dewan hakim sebagai ketua majelis
                // ===============================
                EventJudgePanelMember::updateOrCreate(
                    [
                        'event_judge_panel_id' => $panel->id,
                        'event_judge_id'       => $eventJudge->id,
                    ],
                    [
                        'is_chief'     => true,
                        'order_number' => 1,
                    ]
                );
            }

            $this->command->info("✅ Event {$event->id}: 12 Majelis & ketua majelis berhasil dibuat");
        }

        $this->command->info('🎉 EventJudgePanelSeeder selesai');
    }
}