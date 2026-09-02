<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EventLocation;

class __EventLocationSeeder extends Seeder
{
    public function run(): void
    {
        $eventId = 1; // ⚠️ sesuaikan dengan event MTQN ke-XLII Tingkat Kabupaten Pesisir Selatan Tahun 2026

        // Sumber: Berita Acara Penetapan Venue Lomba MTQN ke-XLII
        // Tingkat Kabupaten Pesisir Selatan Tahun 2026, Painan, 30 Juni 2026 (LPTQ Kab. Pesisir Selatan)
        $locations = [

            // 1
            [
                'code' => 'MIMBAR-UTAMA-CAROCOK',
                'name' => 'Mimbar Utama Pantai Carocok Painan',
                'address' => 'Pantai Carocok Painan, Kecamatan IV Jurai, Pesisir Selatan',
                'latitude' => -1.3514872810518677,
                'longitude' => 100.56661024540071,
                'notes' => 'Cabang Lomba: Tilawah Dewasa dan Remaja.',
                'photo_url' => 'https://res.cloudinary.com/dezj1x6xp/image/upload/v1787388649/PandanViewMandeh/n66aojjsrl20gsfofthk.png'
            ],

            // 2
            [
                'code' => 'MTS-CAROCOK',
                'name' => 'Masjid Terapung Samudera Ilahi',
                'address' => 'Pantai Carocok Painan, Kecamatan IV Jurai, Pesisir Selatan',
                'latitude' => -1.3527541069429705, 
                'longitude' => 100.56566775946011,
                'notes' => 'Cabang Lomba: Tilawah Anak-anak, TK dan Canet.',
                'photo_url' => 'https://res.cloudinary.com/dezj1x6xp/image/upload/v1787388773/PandanViewMandeh/ml3aksxiow6qsruviraa.jpg'
            ],

            // 3
            [
                'code' => 'MSJ-AKBAR-BAITURRAHMAN',
                'name' => 'Masjid Akbar Baiturrahman',
                'address' => 'Taman Spora Painan, Kecamatan IV Jurai, Pesisir Selatan',
                'latitude' => -1.3454518594292997, 
                'longitude' => 100.57901697200764,
                'notes' => 'Cabang Lomba: Hafiz 1 dan 5 Juz Non Tilawah serta 10 Juz.',
                'photo_url' => 'https://res.cloudinary.com/dezj1x6xp/image/upload/v1787388994/PandanViewMandeh/hyuzqgljqiascybnnarj.png'
            ],

            // 4
            [
                'code' => 'MSJ-AGUNG-ALAMILIN',
                'name' => 'Masjid Agung Al-Amilin',
                'address' => 'Painan Utara, Kecamatan IV Jurai, Pesisir Selatan',
                'latitude' => -1.3465709458440205, 
                'longitude' => 100.58353941019764,
                'notes' => 'Cabang Lomba: Hafiz 1 dan 5 Juz Tilawah.',
                'photo_url' => 'https://res.cloudinary.com/dezj1x6xp/image/upload/v1787389120/PandanViewMandeh/rqmgo48mvcipdd7cfpic.jpg'
            ],

            // 5
            [
                'code' => 'MRY-PAINAN',
                'name' => 'Masjid Raya Painan',
                'address' => 'Painan Selatan, Kecamatan IV Jurai, Pesisir Selatan',
                'latitude' => -1.3507497080726723, 
                'longitude' => 100.5792839285531,
                'notes' => 'Cabang Lomba: Kitab Standar.',
                'photo_url' => 'https://res.cloudinary.com/dezj1x6xp/image/upload/v1787389259/PandanViewMandeh/pqv0gsnobw0bs9bnwee3.png'
            ],

            // 6
            [
                'code' => 'MSJ-DARULFALAH-SALIDO',
                'name' => 'Masjid Darul Falah Salido',
                'address' => 'Salido, Kecamatan IV Jurai, Pesisir Selatan',
                'latitude' => -1.3338987914362384, 
                'longitude' => 100.57059000845514,
                'notes' => 'Cabang Lomba: Tafsir.',
                'photo_url' => 'https://res.cloudinary.com/dezj1x6xp/image/upload/v1787389361/PandanViewMandeh/ci4zv1aq5xhsdpljjms7.png'
            ],

            // 7
            [
                'code' => 'MSJ-MUJAHIDIN-KPBARUSAGO',
                'name' => 'Masjid Mujahidin Sago',
                'address' => 'Sago Salido, Kecamatan IV Jurai, Pesisir Selatan',
                'latitude' => -1.3062407,
                'longitude' => 100.5453838, 
                'notes' => 'Cabang Lomba: Tartil Dasar Menengah dan Umum.',
                'photo_url' => 'http://res.cloudinary.com/dezj1x6xp/image/upload/v1788274912/PandanViewMandeh/zmbwes8uzkogxuszgxzd.png'
            ],
            // [
            //     'code' => 'MSJ-DARULMUNIR-KINCIR',
            //     'name' => 'Masjid Darul Munir',
            //     'address' => 'Kincir Salido, Kecamatan IV Jurai, Pesisir Selatan',
            //     'latitude' => -1.325333739938216, 
            //     'longitude' => 100.56328915096573,
            //     'notes' => 'Cabang Lomba: Tartil Dasar Menengah dan Umum.',
            //     'photo_url' => 'https://res.cloudinary.com/dezj1x6xp/image/upload/v1787389497/PandanViewMandeh/mk81rs0hfiqqc6qrq92b.png'
            // ],

            // 8
            [
                'code' => 'MSJ-PAHLAWAN-LUMPO',
                'name' => 'Masjid Pahlawan',
                'address' => 'Lumpo, Kecamatan IV Jurai, Pesisir Selatan',
                'latitude' => -1.2730324464378593, 
                'longitude' => 100.58298239707491,
                'notes' => 'Cabang Lomba: Khutbah Jum\'at dan Azan.',
                'photo_url' => 'https://res.cloudinary.com/dezj1x6xp/image/upload/v1787389682/PandanViewMandeh/pndzbpkigwkesp386rds.png'
            ],

            // 9
            [
                'code' => 'PCC-PAINAN',
                'name' => 'PCC (Painan Convention Center)',
                'address' => 'Painan, Kecamatan IV Jurai, Pesisir Selatan',
                'latitude' => -1.3485335947363621, 
                'longitude' => 100.57809772204112,
                'notes' => 'Cabang Lomba: Fahmil Qur\'an.',
                'photo_url' => 'http://res.cloudinary.com/dezj1x6xp/image/upload/v1787389822/PandanViewMandeh/jjlgwromiqkvqw6ssnxu.jpg'
            ],

            // 10
            [
                'code' => 'AULA-DISDIK-PAINAN',
                'name' => 'Aula Dinas Pendidikan',
                'address' => 'Painan, Kecamatan IV Jurai, Pesisir Selatan',
                'latitude' => -1.349331726336792, 
                'longitude' => 100.57945737757775,
                'notes' => 'Cabang Lomba: Syarhil Qur\'an.',
                'photo_url' => 'https://res.cloudinary.com/dezj1x6xp/image/upload/v1787389948/PandanViewMandeh/u5ofpx1vt4q2f2xzqffr.jpg'
            ],

            // 11
            [
                'code' => 'MAN2-PESSEL-SAGO',
                'name' => 'MAN 2 Pesisir Selatan',
                'address' => 'Sago, Kecamatan IV Jurai, Pesisir Selatan',
                'latitude' => -1.3066069309718031,
                'longitude' => 100.54825496320008,
                'notes' => 'Cabang Lomba: KTIQ dan KTIH.',
                'photo_url' => 'https://res.cloudinary.com/dezj1x6xp/image/upload/v1787390122/PandanViewMandeh/xcdwklb8ozrwjjrwuyrv.png'
            ],

            // 12
            [
                'code' => 'MTSN1-PESSEL-SALIDO',
                'name' => 'MTsN 1 Pesisir Selatan',
                'address' => 'Salido, Kecamatan IV Jurai, Pesisir Selatan',
                'latitude' => -1.3311661642103212,
                'longitude' => 100.56740243681398,
                'notes' => 'Cabang Lomba: Kaligrafi.',
                'photo_url' => 'https://res.cloudinary.com/dezj1x6xp/image/upload/v1787390246/PandanViewMandeh/nmi3t6ehrwy78nvtdilq.png'
            ],
        ];

        foreach ($locations as $loc) {
            EventLocation::firstOrCreate(
                [
                    'event_id' => $eventId,
                    'code'     => $loc['code'],
                ],
                [
                    'name'      => $loc['name'],
                    'address'   => $loc['address'],
                    'latitude'  => $loc['latitude'],
                    'longitude' => $loc['longitude'],
                    'notes'     => $loc['notes'],
                    'photo_url' => $loc['photo_url'],
                    'is_active' => true,
                ]
            );
        }
    }
}