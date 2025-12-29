<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MedalRule;

class __MedalRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'order_number' => 1,
                'medal_code'   => 'champion_1',
                'medal_name'   => 'Juara 1',
                'point'        => 9,
            ],
            [
                'order_number' => 2,
                'medal_code'   => 'champion_2',
                'medal_name'   => 'Juara 2',
                'point'        => 7,
            ],
            [
                'order_number' => 3,
                'medal_code'   => 'champion_3',
                'medal_name'   => 'Juara 3',
                'point'        => 5,
            ],
            [
                'order_number' => 4,
                'medal_code'   => 'runner_up_1',
                'medal_name'   => 'Harapan 1',
                'point'        => 3,
            ],
            [
                'order_number' => 5,
                'medal_code'   => 'runner_up_2',
                'medal_name'   => 'Harapan 2',
                'point'        => 2,
            ],
            [
                'order_number' => 6,
                'medal_code'   => 'runner_up_3',
                'medal_name'   => 'Harapan 3',
                'point'        => 1,
            ],
        ];

        foreach ($rules as $rule) {
            MedalRule::updateOrCreate(
                ['order_number' => $rule['order_number']],
                $rule
            );
        }
    }
}
