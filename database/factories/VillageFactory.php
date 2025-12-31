<?php

namespace Database\Factories;

use App\Models\Village;
use App\Models\District;
use Illuminate\Database\Eloquent\Factories\Factory;

class VillageFactory extends Factory
{
    protected $model = Village::class;

    public function definition(): array
    {
        return [
            'district_id' => District::factory(),
            'name' => 'Desa ' . $this->faker->unique()->streetName . ' ' . $this->faker->randomNumber(3),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
