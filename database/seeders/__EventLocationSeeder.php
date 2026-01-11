<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EventLocation;

class __EventLocationSeeder extends Seeder
{
    public function run(): void
    {
        $eventId = 1; // ⚠️ sesuaikan dengan event MTQ aktif

        $locations = [

            // 1
            [
                'code' => 'MRY-PAINAN',
                'name' => 'Masjid Raya Painan',
                'address' => 'Jl. Perintis Kemerdekaan, Painan, Kecamatan IV Jurai, Pesisir Selatan',
                'latitude' => -0.9846709,
                'longitude' => 100.3841667,
                'notes' => 'Masjid utama Kecamatan IV Jurai dan pusat kegiatan keagamaan.',
            ],

            // 2
            [
                'code' => 'MSJ-AKBAR',
                'name' => 'Masjid Akbar Painan',
                'address' => 'Painan, Kecamatan IV Jurai, Pesisir Selatan',
                'latitude' => -0.9985200,
                'longitude' => 100.3902100,
                'notes' => 'Masjid besar dan representatif di kawasan Painan.',
            ],

            // 3
            [
                'code' => 'MSJ-AMILIN',
                'name' => 'Masjid Amilin Painan',
                'address' => 'Painan, Kecamatan IV Jurai, Pesisir Selatan',
                'latitude' => -1.0063400,
                'longitude' => 100.3879600,
                'notes' => 'Masjid lingkungan Amilin, aktif kegiatan keagamaan.',
            ],

            // 4
            [
                'code' => 'MTS-CAROCOK',
                'name' => 'Masjid Terapung Samudera Ilahi',
                'address' => 'Pantai Carocok Painan, Kecamatan IV Jurai, Pesisir Selatan',
                'latitude' => -1.3524963,
                'longitude' => 100.5653168,
                'notes' => 'Masjid ikon wisata religi Pantai Carocok.',
            ],

            // 5
            [
                'code' => 'MIC-SAGO',
                'name' => 'Masjid Islamic Centre Sago',
                'address' => 'Nagari Sago Salido, Kecamatan IV Jurai, Pesisir Selatan',
                'latitude' => -1.3220830,
                'longitude' => 100.5593423,
                'notes' => 'Masjid besar dan pusat kegiatan umat di Sago.',
            ],

            // 6
            [
                'code' => 'MSJ-SALIDO',
                'name' => 'Masjid Raya Salido',
                'address' => 'Salido, Kecamatan IV Jurai, Pesisir Selatan',
                'latitude' => -1.3067320,
                'longitude' => 100.5461800,
                'notes' => 'Masjid nagari Salido.',
            ],

            // 7
            [
                'code' => 'MSJ-KAMPUNGJUA',
                'name' => 'Masjid Kampung Jua',
                'address' => 'Kampung Jua, Kecamatan IV Jurai, Pesisir Selatan',
                'latitude' => -1.0012450,
                'longitude' => 100.3894510,
                'notes' => 'Masjid lingkungan Kampung Jua.',
            ],

            // 8
            [
                'code' => 'MSJ-AMPANGPULAI',
                'name' => 'Masjid Ampang Pulai',
                'address' => 'Ampang Pulai, Kecamatan IV Jurai, Pesisir Selatan',
                'latitude' => -1.0156020,
                'longitude' => 100.3927640,
                'notes' => 'Masjid masyarakat Ampang Pulai.',
            ],

            // 9
            [
                'code' => 'MSJ-BUKITPUTUS',
                'name' => 'Masjid Bukit Putus',
                'address' => 'Bukit Putus, Kecamatan IV Jurai, Pesisir Selatan',
                'latitude' => -1.0289140,
                'longitude' => 100.4018200,
                'notes' => 'Masjid perkampungan Bukit Putus.',
            ],

            // 10
            [
                'code' => 'MSJ-KOTO',
                'name' => 'Masjid Koto Painan',
                'address' => 'Koto Painan, Kecamatan IV Jurai, Pesisir Selatan',
                'latitude' => -1.0203500,
                'longitude' => 100.3987400,
                'notes' => 'Masjid wilayah Koto Painan.',
            ],

            // 11
            [
                'code' => 'MSJ-ALIKHLAS',
                'name' => 'Masjid Al-Ikhlas Painan',
                'address' => 'Painan, Kecamatan IV Jurai, Pesisir Selatan',
                'latitude' => -1.0108400,
                'longitude' => 100.3869200,
                'notes' => 'Masjid lingkungan Al-Ikhlas Painan.',
            ],

            // 12
            [
                'code' => 'MSJ-NURULHUDA',
                'name' => 'Masjid Nurul Huda Painan',
                'address' => 'Painan, Kecamatan IV Jurai, Pesisir Selatan',
                'latitude' => -1.0173200,
                'longitude' => 100.3948800,
                'notes' => 'Masjid lingkungan Nurul Huda.',
            ],

            // 13
            [
                'code' => 'MSJ-ALHIDAYAH',
                'name' => 'Masjid Al-Hidayah Painan',
                'address' => 'Painan, Kecamatan IV Jurai, Pesisir Selatan',
                'latitude' => -1.0235400,
                'longitude' => 100.4021100,
                'notes' => 'Masjid lingkungan Al-Hidayah.',
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
                    'is_active' => true,
                ]
            );
        }
    }
}
