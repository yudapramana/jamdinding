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
         * MAPPING GOLONGAN -> KODE MAJELIS HAKIM
         * Sesuai penetapan pada halaman "Golongan per Event (Event Groups)".
         * Key harus persis sama dengan full_name di master_groups.
         */
        $groupToPanelCode = [
            "Fahm Al Qur'an Beregu" => 'MJL-09',

            "Hafalan Al Qur'an 1 Juz + Tilawah" => 'MJL-04',
            "Hafalan Al Qur'an 5 Juz + Tilawah" => 'MJL-04',

            "Hafalan Al Qur'an 1 Juz Non Tilawah" => 'MJL-03',
            "Hafalan Al Qur'an 5 Juz Non Tilawah" => 'MJL-03',
            "Hafalan Al Qur'an 10 Juz"            => 'MJL-03',

            "Karya Tulis Ilmiah Al Qur'an (KTIQ) Umum" => 'MJL-11',
            "Karya Tulis Ilmiah Hadits (KTIH) Umum"    => 'MJL-11',

            "Khutbah Jum'at & Adzan Khatib + Mu'adzin" => 'MJL-08',

            "Kitab Standar Umum" => 'MJL-05',

            "Seni Baca Al Qur'an (Tilawah) Anak-anak"       => 'MJL-02',
            "Seni Baca Al Qur'an (Tilawah) Cacat Netra"     => 'MJL-02',
            "Seni Baca Al Qur'an (Tilawah) Taman Kanak-Kanak" => 'MJL-02',

            "Seni Baca Al Qur'an (Tilawah) Dewasa" => 'MJL-01',
            "Seni Baca Al Qur'an (Tilawah) Remaja" => 'MJL-01',

            "Seni Kaligrafi Al Qur'an Dekorasi"    => 'MJL-12',
            "Seni Kaligrafi Al Qur'an Hiasan Mushaf" => 'MJL-12',
            "Seni Kaligrafi Al Qur'an Kontemporer" => 'MJL-12',
            "Seni Kaligrafi Al Qur'an Naskah"      => 'MJL-12',

            "Syarhil Qur'an Beregu" => 'MJL-10',

            "Tafsir Al Qur'an Bahasa Arab"      => 'MJL-06',
            "Tafsir Al Qur'an Bahasa Indonesia" => 'MJL-06',
            "Tafsir Al Qur'an Bahasa Inggris"   => 'MJL-06',

            "Tartil Al Qur'an Umum"     => 'MJL-07',
            "Tartil Al Qur'an Dasar"    => 'MJL-07',
            "Tartil Al Qur'an Menengah" => 'MJL-07',
        ];

        /**
         * PANEL HAKIM AKTIF (diindex berdasarkan code, mis. MJL-01)
         */
        $panels = EventJudgePanel::where('event_id', $event->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $panelsByCode = $panels->keyBy('code');

        $isJudgePanelSettled = true;
        if ($panels->isEmpty()) {
            $this->command?->error("Tidak ada event_judge_panels aktif.");
            $this->command?->error("Melanjutkan Seeder tanpa assign event_judge_panels.");
            $isJudgePanelSettled = false;
        }

        $this->command?->info(
            "Mengisi event_groups untuk event {$event->event_name}"
        );

        $order = 1;

        foreach ($masterGroups as $mg) {

            /**
             * CEK STATUS AKTIF
             */
            $isActive = in_array($mg->full_name, $activeGroups, true);

            /**
             * PANEL HANYA UNTUK GROUP AKTIF
             * Diambil dari mapping eksplisit ($groupToPanelCode), bukan round-robin.
             */
            $panelId = null;
            if ($isActive && $isJudgePanelSettled) {
                $panelCode = $groupToPanelCode[$mg->full_name] ?? null;

                if ($panelCode && $panelsByCode->has($panelCode)) {
                    $panelId = $panelsByCode->get($panelCode)->id;
                } else {
                    $this->command?->warn(
                        "⚠️ Golongan '{$mg->full_name}' aktif tapi tidak ada mapping majelis (kode: " . ($panelCode ?? '-') . ")"
                    );
                }
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
            "✔ Seeder selesai: {$masterGroups->count()} event_groups, majelis di-assign sesuai mapping golongan"
        );
    }
}