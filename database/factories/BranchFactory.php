<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'code'         => strtoupper($this->faker->bothify('BR-###')),
            'name'         => $this->faker->unique()->words(3, true),
            'is_team'      => false,
            'order_number' => $this->faker->numberBetween(1, 50),
            'is_active'    => true,
        ];
    }

    /**
     * State: cabang tim
     */
    public function team(): static
    {
        return $this->state(fn () => [
            'is_team' => true,
        ]);
    }

    /**
     * State: inactive
     */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
