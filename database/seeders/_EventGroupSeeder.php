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

        /**
         * DAFTAR GOLONGAN AKTIF
         */
        $activeGroups = [
            "Seni Baca Al Qur'an (Tilawah) Taman Kanak-Kanak",
            "Seni Baca Al Qur'an (Tilawah) Anak-anak",
            "Seni Baca Al Qur'an (Tilawah) Remaja",
            "Seni Baca Al Qur'an (Tilawah) Dewasa",
            "Seni Baca Al Qur'an (Tilawah) Cacat Netra",
            "Tartil Al Qur'an Dasar",
            "Tartil Al Qur'an Menengah",
            "Tartil Al Qur'an Umum",
            "Hafalan Al Qur'an 1 Juz + Tilawah",
            "Hafalan Al Qur'an 5 Juz + Tilawah",
            "Hafalan Al Qur'an 1 Juz Non Tilawah",
            "Hafalan Al Qur'an 5 Juz Non Tilawah",
            "Hafalan Al Qur'an 10 Juz",
            "Kitab Standar Umum",
            "Tafsir Al Qur'an Bahasa Arab",
            "Tafsir Al Qur'an Bahasa Indonesia",
            "Tafsir Al Qur'an Bahasa Inggris",
            "Fahm Al Qur'an Beregu",
            "Syarhil Qur'an Beregu",
            "Seni Kaligrafi Al Qur'an Naskah",
            "Seni Kaligrafi Al Qur'an Hiasan Mushaf",
            "Seni Kaligrafi Al Qur'an Dekorasi",
            "Seni Kaligrafi Al Qur'an Kontemporer",
            "Khutbah Jum'at & Adzan Khatib + Mu'adzin",
            "Karya Tulis Ilmiah Al Qur'an (KTIQ) Umum",
            "Karya Tulis Ilmiah Hadits (KTIH) Umum",
        ];

        /**
         * PANEL HAKIM AKTIF
         */
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
        $panelIndex = 0;
        $order      = 1;

        foreach ($masterGroups as $mg) {

            /**
             * CEK STATUS AKTIF
             */
            $isActive = in_array($mg->full_name, $activeGroups, true);

            /**
             * PANEL HANYA UNTUK GROUP AKTIF
             */
            $panelId = null;
            if ($isActive) {
                $panelId = $panels[$panelIndex % $panelCount]->id;
                $panelIndex++;
            }

            EventGroup::updateOrCreate(
                [
                    'event_id'  => $event->id,
                    'branch_id' => $mg->branch_id,
                    'group_id'  => $mg->group_id,
                ],
                [
                    'event_judge_panel_id' => $panelId,

                    'branch_name' => $mg->branch_name,
                    'group_name'  => $mg->group_name,
                    'full_name'   => $mg->full_name,

                    // aturan: max_age master - 1
                    'max_age'     => $mg->max_age ? ($mg->max_age - 1) : 0,
                    'is_team'     => (bool) $mg->is_team,

                    'status'      => $isActive ? 'active' : 'inactive',

                    'use_custom_judges'    => false,
                    'judge_assignment_mode'=> 'BY_PANEL',

                    'order_number' => $order++,
                ]
            );
        }

        $this->command?->info(
            "✔ Seeder selesai: {$masterGroups->count()} event_groups, panel hanya untuk group ACTIVE"
        );
    }
}
