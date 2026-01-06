<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EventLocation;

class __EventLocationSeeder extends Seeder
{
    public function run()
    {
        // 1. Masjid Raya Painan
        EventLocation::create([
            'event_id'  => 1, // sesuaikan dengan event MTQ yang relevan
            'code'      => 'MRY-PAINAN',
            'name'      => 'Masjid Raya Painan',
            'address'   => 'Jl. Perintis Kemerdekaan, Painan, IV Jurai, Pesisir Selatan, Sumatera Barat',
            'latitude'  => -0.9846709,      // estimasi koordinat
            'longitude' => 100.3841667,    // estimasi koordinat
            'notes'     => 'Masjid utama di Painan yang sering menjadi pusat kegiatan keagamaan.',
            'is_active' => true,
        ]);

        // 2. Masjid Terapung Samudera Ilahi (Pantai Carocok)
        EventLocation::create([
            'event_id'  => 1,
            'code'      => 'STM-CAROCOK',
            'name'      => 'Masjid Terapung Samudera Ilahi',
            'address'   => 'Pantai Carocok, Painan, Kecamatan IV Jurai, Pesisir Selatan, Sumatera Barat',
            'latitude'  => -1.3524963,     // estimasi koordinat
            'longitude' => 100.5653168,    // estimasi koordinat
            'notes'     => 'Masjid ikon wisata religi di kawasan wisata Pantai Carocok.', 
            'is_active' => true,
        ]);

        // 3. Masjid Islamic Centre Sago Painan
        EventLocation::create([
            'event_id'  => 1,
            'code'      => 'MIC-SAGO',
            'name'      => 'Masjid Islamic Centre Sago Painan',
            'address'   => 'Jl. Raya Padang - Painan, Sago Salido, IV Jurai, Pesisir Selatan, Sumatera Barat',
            'latitude'  => -1.322083,     // estimasi koordinat
            'longitude' => 100.5593423,    // estimasi koordinat
            'notes'     => 'Masjid besar di Sago Salido, salah satu pusat kegiatan Muslim setempat.',
            'is_active' => true,
        ]);
    }
}
