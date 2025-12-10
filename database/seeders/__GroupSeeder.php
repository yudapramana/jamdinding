<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class __GroupSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
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

        foreach ($categories as $index => $name) {

            // Hilangkan teks dalam kurung, misalnya: "(Eselon II)"
            $clean = preg_replace('/\s*\([^)]*\)/', '', $name);

            // Pecah berdasarkan spasi, plus, atau tanda hubung
            $words = preg_split('/[\s+\-]+/', $clean);

            // Hilangkan kata kosong
            $words = array_values(array_filter($words));

            // Ambil kode berdasarkan algoritma
            if (count($words) > 2) {
                $code = strtoupper(substr($words[0], 0, 1) . substr(end($words), 0, 1));
            } elseif (count($words) == 2) {
                $code = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
            } else {
                $word = $words[0];

                // Bersihkan dari karakter non-huruf seperti `'` atau `-`
                $word = preg_replace('/[^A-Za-z]/u', '', $word);

                $length = mb_strlen($word);
                $midIndex = intdiv($length, 2);

                $firstChar = mb_substr($word, 0, 1);
                $middleChar = mb_substr($word, $midIndex, 1);

                $code = strtoupper($firstChar . $middleChar);

            }

            // Ranking number: 2-digit
            $orderNumber = $index + 1;
            $orderFormatted = str_pad($orderNumber, 2, '0', STR_PAD_LEFT);

            DB::table('groups')->insert([
                'code'         => $code . '.' . $orderFormatted,
                'name'         => $name,
                'order_number' => $orderNumber,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        }
    }
}
