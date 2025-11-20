<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterCompetitionGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $competitionGroups = [
            'Fahm Al Qur\'an',
            'Hafalan Al Qur\'an',
            'Karya Tulis Ilmiah Al Qur\'an (KTIQ)',
            'Karya Tulis Ilmiah Hadits (KTIH)',
            'Khutbah Jum\'at & Adzan',
            'Kitab Standar',
            'Musabaqah Hafalan Hadits Nabi',
            'Qiraat Al Qur\'an',
            'Seni Baca Al Qur\'an (Tilawah)',
            'Seni Kaligrafi Al Qur\'an',
            'Syarhil Qur\'an',
            'Tafsir Al Qur\'an',
            'Tartil Al Qur\'an',
            'Tartil Al Qur\'an Eksekutif (Eselon II)',
        ];

        $data = [];
        $order = 1;

        foreach ($competitionGroups as $name) {
            // Logika untuk membuat kode unik
            // 1. Ganti karakter non-alphanumeric dengan spasi
            // 2. Konversi ke huruf kecil
            // 3. Ganti spasi dengan underscore
            // 4. Konversi ke huruf besar

            $code = strtoupper(
                preg_replace(
                    '/[^a-zA-Z0-9]+/', 
                    '_', 
                    str_replace(
                        ['(', ')', '\''], 
                        '', // Hapus kurung dan apostrof
                        trim(
                            strtolower($name)
                        )
                    )
                )
            );
            
            // Hapus underscore ganda jika ada yang tersisa
            $code = preg_replace('/__+/', '_', $code);
            // Hapus underscore di awal/akhir jika ada
            $code = trim($code, '_');

            $data[] = [
                'code' => $code,
                'name' => $name,
                'order_number' => $order++,
                'description' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Hapus data yang sudah ada sebelum memasukkan data baru (opsional, tergantung kebutuhan)
        // DB::table('master_competition_groups')->truncate();

        DB::table('master_competition_groups')->insert($data);
    }
}