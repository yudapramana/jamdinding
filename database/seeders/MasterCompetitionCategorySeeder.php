<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterCompetitionCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $competitionCategories = [
            'Beregu',
            '1 Juz + Tilawah',
            '1 Juz Non Tilawah',
            '10 Juz',
            '20 Juz',
            '30 Juz',
            '5 Juz + Tilawah',
            '5 Juz Non Tilawah',
            'Umum',
            'Khatib + Mu\'adzin',
            '100 Hadits dengan Sanad',
            '500 Hadits tanpa Sanad',
            'Mujawwad Dewasa',
            'Murattal Dewasa',
            'Murattal Remaja',
            'Anak-anak',
            'Cacat Netra',
            'Dewasa',
            'Remaja',
            'Taman Kanak-Kanak',
            'Dekorasi',
            'Digital',
            'Hiasan Mushaf',
            'Kontemporer',
            'Naskah',
            'Bahasa Arab',
            'Bahasa Indonesia',
            'Bahasa Inggris',
            'Dasar',
            'Menengah',
            'Eksekutif Eselon II',
        ];

        $data = [];
        $order = 1;

        foreach ($competitionCategories as $name) {
            // Logika untuk membuat kode unik (uppercase, snake_case)

            $code = strtoupper(
                preg_replace(
                    '/[^a-zA-Z0-9]+/', 
                    '_', 
                    str_replace(
                        '\'', 
                        '', // Hapus apostrof
                        trim(
                            strtolower($name)
                        )
                    )
                )
            );
            
            // Hapus underscore ganda dan di awal/akhir
            $code = trim(preg_replace('/__+/', '_', $code), '_');

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

        // Opsional: Hapus data yang sudah ada
        // DB::table('master_competition_categories')->truncate();

        DB::table('master_competition_categories')->insert($data);
    }
}