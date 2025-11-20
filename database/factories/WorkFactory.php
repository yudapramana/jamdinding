<?php

namespace Database\Factories;

use App\Models\Report;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Work>
 */
class WorkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'report_id' => rand(1, 10),
            'work_name' => $this->faker->sentence(),
            'work_detail' => $this->faker->paragraph(),
            'volume' => rand(1,5),
            'unit' => rand(1, 9),
            'evidence' => $this->faker->word()
        ];
    }
}
