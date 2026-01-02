<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class __EventSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('events')->insert([
            [
                'event_key'         => 'mtq-41-pessel',
                'app_name'          => 'e-MTQ Kabupaten Pesisir Selatan',
                'event_name'        => 'MTQ XLI KABUPATEN PESISIR SELATAN',
                'event_year'        => '2026',
                'event_location'    => 'PAINAN',
                'event_tagline'     => 'Menuju MTQ yang Bermartabat',
                'assessment_token'  => 'pessel2026',

                'start_date'        => '2026-01-01',
                'end_date'          => '2026-06-27',
                'age_limit_date'    => '2026-07-01',

                'logo_event'        => 'http://res.cloudinary.com/dezj1x6xp/image/upload/v1763621790/PandanViewMandeh/qpiwf4a8dubcgok9nnzr.png',
                'logo_sponsor_1'    => 'https://upload.wikimedia.org/wikipedia/commons/9/9a/Kementerian_Agama_new_logo.png',
                'logo_sponsor_2'    => 'http://res.cloudinary.com/dezj1x6xp/image/upload/v1763619918/PandanViewMandeh/mjvmbh7mrbpqgq2204qx.svg',
                'logo_sponsor_3'    => 'https://imgv2-1-f.scribdassets.com/img/document/343844056/original/0cb3f1c963/1?v=1',

                'event_level'       => 'regency', // karena MTQ Kabupaten

                'province_id'       => 13,
                'regency_id'        => 1301,
                'district_id'       => null,

                'is_active'         => true,
                'created_at'        => now(),
                'updated_at'        => now(),
            ],
        ]);
    }
}
