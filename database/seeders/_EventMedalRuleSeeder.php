<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MedalRule;
use App\Models\EventMedalRule;

class _EventMedalRuleSeeder extends Seeder
{
    public function run(): void
    {
        $eventId = 1;

        $medalRules = MedalRule::where('is_active', true)
            ->orderBy('order_number')
            ->get();

        foreach ($medalRules as $rule) {
            EventMedalRule::updateOrCreate(
                [
                    'event_id'     => $eventId,
                    'order_number' => $rule->order_number,
                ],
                [
                    'medal_code' => $rule->medal_code,
                    'medal_name' => $rule->medal_name,
                    'point'      => $rule->point,
                    'is_active'  => true,
                ]
            );
        }
    }
}
