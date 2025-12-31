<?php

namespace Database\Factories;

use App\Models\District;
use App\Models\Regency;
use Illuminate\Database\Eloquent\Factories\Factory;

class DistrictFactory extends Factory
{
    protected $model = District::class;

    public function definition(): array
    {
        return [
            'regency_id' => Regency::factory(),
            'name' => 'Kecamatan ' . $this->faker->city . ' ' . $this->faker->randomNumber(3),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
