<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class __ListFieldSeeder extends Seeder
{
    public function run(): void
    {
        $fields = [
            // --- DAFTAR AWAL (DARI KAMU) ---
            "Tajwid",
            "Fashahah",
            "Suara",
            "Lagu",
            "Adab Tilawah",
            "Kefasihan Bacaan",
            "Penampilan dan Keberanian",
            "Irama dan Suara",
            "Tahfizh",
            "Terjemah dan Materi",
            "Penghayatan dan Retorika",
            "Tilawah",
            "Fashahah dan Adab",
            "Terjemah dan Maksud",
            "Qiraat",
            "Kaidah",
            "Keindahan",
            "Keindahan Hiasan",
            "Muradu Ayat dan Ta'bir",
            "Mufradat dan Munasabah Ayat",
            "Wawasan dan Durus",
            "Materi atau Isi",
            "Bahasa",
            "Adzan",
            "Teknik",
            "Vokal",
            "Penampilan",
            "Fahmil",
            "Kaidah dan Gaya Bahasa",
            "Logika dan Organisasi Pesan",
            "Prestasi",
            "Nilai Akhir",
            "Unsur Kaligrafi",
            "Unsur Seni Rupa",
            "Sentuhan Akhir",

            // --- TAMBAHAN: TILAWAH / MHQ / LPTQ ---
            "Makharijul Huruf",
            "Sifat-sifat Huruf",
            "Mad dan Panjang Pendek",
            "Waqaf dan Ibtida",
            "Kelancaran Bacaan",
            "Ketepatan Bacaan",
            "Kelancaran Hafalan",
            "Ketepatan Hafalan",
            "Kelengkapan Hafalan",
            "Ketepatan Tajwid",
            "Kejelasan Lafaz",
            "Penguasaan Lagu",
            "Kesesuaian Irama",
            "Kesesuaian Tempo",
            "Keindahan Suara",
            "Ketepatan Nada (Maqra’ / Maqam)",
            "Kekuatan dan Kontrol Suara",
            "Stabilitas Nafas",
            "Etika dan Kesopanan",
            "Kesopanan dan Keserasian",
            "Sikap di Mimbar",

            // --- TAMBAHAN: SYARHIL / MSQ / PIDATO QUR'ANI ---
            "Vokal dan Artikulasi",
            "Intonasi dan Aksentuasi",
            "Gaya dan Mimik",
            "Ekspresi dan Penghayatan",
            "Keharmonisan Penampilan Tim",
            "Penampilan dan Keharmonisan",
            "Kejelasan Struktur Pidato",
            "Keterpaduan Isi dan Dalil",
            "Ketepatan Terjemah",
            "Sistematika dan Isi",
            "Ketepatan Tafsir dan Penafsiran",
            "Penguasaan Materi",
            "Relevansi Materi dengan Tema",
            "Kaidah Bahasa",
            "Kejelasan Bahasa dan Diksi",
            "Kesesuaian Bahasa dengan Audiens",

            // --- TAMBAHAN: FAHMIL / QUIZ / WAWASAN QUR'ANI ---
            "Kecepatan dan Ketepatan Jawaban",
            "Penguasaan Wawasan Al-Qur'an",
            "Penguasaan Hadits dan Dalil Pendukung",
            "Strategi Tim dan Kerja Sama",
            "Ketepatan Pengutipan Ayat",
            "Kecermatan Membaca Soal",

            // --- TAMBAHAN: KALIGRAFI / MSQ TULIS ---
            "Komposisi Huruf dan Tata Letak",
            "Proporsi Huruf dan Keseimbangan",
            "Kesesuaian Khath (Gaya Tulisan)",
            "Keseimbangan Warna",
            "Kebersihan dan Kerapian Karya",
            "Kreativitas dan Orisinalitas",
            "Pengolahan Ornamen",
            "Harmoni Unsur Kaligrafi dan Ornamen",

            // --- TAMBAHAN: UMUM (BISA DIPAKAI BANYAK CABANG) ---
            "Kedisiplinan Waktu Tampil",
            "Kesiapan dan Kerapian Peserta",
            "Konsistensi Penampilan",
            "Penguasaan Panggung",
            "Kepatuhan terhadap Ketentuan Lomba",
        ];

        $data = [];
        $order = 1;

        foreach ($fields as $name) {
            $code = strtoupper(substr(preg_replace('/\s+/', '', $name), 0, 2));
            $data[] = [
                'code' => $code,
                'name' => $name,
                'description' => null,
                'order_number' => $order++,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('list_fields')->insert($data);
    }
}
