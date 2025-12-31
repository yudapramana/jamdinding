<?php

namespace Database\Factories;

use App\Models\EventBranch;
use App\Models\Event;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventBranchFactory extends Factory
{
    protected $model = EventBranch::class;

    public function definition(): array
    {
        $branch = Branch::factory()->create();

        return [
            'event_id'     => Event::factory(),
            'branch_id'    => $branch->id,

            'branch_name'  => $branch->name ?? $this->faker->word,
            'full_name'    => $branch->full_name ?? $this->faker->sentence(3),

            'status'       => 'active',
            'order_number' => $this->faker->numberBetween(1, 20),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => 'inactive',
        ]);
    }
}
