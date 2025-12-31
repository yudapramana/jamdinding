<?php

namespace Database\Factories;

use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'code'         => strtoupper($this->faker->bothify('GR-###')),
            'name'         => $this->faker->unique()->words(2, true),
            'order_number' => $this->faker->numberBetween(1, 50),
            'is_active'    => true,
        ];
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
