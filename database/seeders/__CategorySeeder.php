<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class __CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'code' => 'PU',       // Putra
                'name' => 'Putra',
                'order_number' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'PI',       // Putri
                'name' => 'Putri',
                'order_number' => 2,
                'is_active' => true,
            ],
        ];

        DB::table('categories')->insert($categories);
    }
}
