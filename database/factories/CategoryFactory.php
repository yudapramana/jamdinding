<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'code'         => strtoupper($this->faker->bothify('CT-###')),
            'name'         => $this->faker->unique()->words(1, true),
            'order_number' => $this->faker->numberBetween(1, 10),
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
