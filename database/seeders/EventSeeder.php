<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('events')->insert([
            [
                'event_key'             => 'mtq-41-pessel',
                'nama_event'            => 'MTQ KABUPATEN PESISIR SELATAN 2026',
                'nama_aplikasi'         => 'e-MTQ Kabupaten Pesisir Selatan',
                'lokasi_event'          => 'PAINAN',
                'tagline'               => 'Menuju MTQ yang Bermartabat',
                'token_hasil_penilaian' => 'pessel2026',
                'tanggal_mulai'         => '2026-04-10',
                'tanggal_selesai'       => '2026-06-17',
                'tanggal_batas_umur'    => '2026-07-01',
                'logo_event'            => 'http://res.cloudinary.com/dezj1x6xp/image/upload/v1763621790/PandanViewMandeh/qpiwf4a8dubcgok9nnzr.png',
                'logo_sponsor_1'        => 'https://upload.wikimedia.org/wikipedia/commons/9/9a/Kementerian_Agama_new_logo.png',
                'logo_sponsor_2'        => 'http://res.cloudinary.com/dezj1x6xp/image/upload/v1763619918/PandanViewMandeh/mjvmbh7mrbpqgq2204qx.svg',
                'logo_sponsor_3'        => 'https://imgv2-1-f.scribdassets.com/img/document/343844056/original/0cb3f1c963/1?v=1',
                'is_active'             => true,
                'created_at'            => now(),
                'updated_at'            => now(),
                'tingkat_event'         => 'kabupaten_kota',
                'province_id'           => 13,
                'regency_id'            => 1301,
                'district_id'           => null,
            ],
        ]);

        // "http://res.cloudinary.com/dezj1x6xp/image/upload/v1763621723/PandanViewMandeh/a6calhg2rukmwmrl5rpx.png"
    }
}
