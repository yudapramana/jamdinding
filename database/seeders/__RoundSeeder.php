<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Round;

class __RoundSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Hapus dulu biar tidak dobel jika seeder dijalankan ulang
        // Round::truncate();

        $rounds = [
            [
                'name' => 'Penyisihan',
                'order_number' => 1,
            ],
            [
                'name' => 'Semifinal',
                'order_number' => 2,
            ],
            [
                'name' => 'Final',
                'order_number' => 3,
            ],
        ];

        foreach ($rounds as $round) {
            Round::updateOrCreate(
                ['name' => $round['name']],
                [
                    'order_number' => $round['order_number'],
                ]
            );
        }
    }
}
