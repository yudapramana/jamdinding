<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $datetime = $this->faker->unique->dateTimeBetween('-1 month', '+2 month');
        $date = $datetime->format('Y-m-d');
        $year = $datetime->format('Y');
        $month = $datetime->format('m');
        $day = $datetime->format('d');


        return [
            'user_id' => 1,
            'date' => $date,
            'year' => $year,
            'month' => $month,
            'day' => $day,
        ];
    }
}
