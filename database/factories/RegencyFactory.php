<?php

namespace Database\Factories;

use App\Models\Regency;
use App\Models\Province;
use Illuminate\Database\Eloquent\Factories\Factory;

class RegencyFactory extends Factory
{
    protected $model = Regency::class;

    public function definition(): array
    {
        return [
            'province_id' => Province::factory(),
            'name' => 'Kabupaten ' . $this->faker->city . ' ' . $this->faker->randomNumber(3),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
