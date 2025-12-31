<?php

namespace Database\Factories;

use App\Models\Province;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProvinceFactory extends Factory
{
    protected $model = Province::class;

    public function definition(): array
    {
        return [
            'name' => 'Provinsi '. $this->faker->city . ' ' . $this->faker->randomNumber(3),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
