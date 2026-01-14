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
            $this->command?->error("Tidak ada data event. Seeder event_categories dibatalkan.");
            return;
        }

        /**
         * DAFTAR GOLONGAN AKTIF
         * (branch_name + ' ' + group_name)
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

        // Ambil master_categories
        $masterCategories = MasterCategory::orderBy('branch_id')
            ->orderBy('group_id')
            ->orderBy('order_number')
            ->get();

        if ($masterCategories->isEmpty()) {
            $this->command?->error("Tidak ada data master_categories.");
            return;
        }

        $this->command?->info(
            "Mengisi event_categories untuk event: {$event->event_name}"
        );

        $order = 1;

        foreach ($masterCategories as $mc) {

            /**
             * Gabungan branch + group
             */
            $groupKey = trim($mc->branch_name . ' ' . $mc->group_name);

            /**
             * Status ditentukan dari activeGroups
             */
            $isActive = in_array($groupKey, $activeGroups, true);

            EventCategory::updateOrCreate(
                [
                    'event_id'    => $event->id,
                    'branch_id'   => $mc->branch_id,
                    'group_id'    => $mc->group_id,
                    'category_id' => $mc->category_id,
                ],
                [
                    'branch_name'   => $mc->branch_name,
                    'group_name'    => $mc->group_name,
                    'category_name' => $mc->category_name,
                    'full_name'     => $mc->full_name,

                    'status'        => $isActive ? 'active' : 'inactive',
                    'order_number'  => $order++,
                ]
            );
        }

        $this->command?->info(
            "✔ Seeder event_categories selesai. Total: {$masterCategories->count()} kategori diproses."
        );
    }
}
