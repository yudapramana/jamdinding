<?php

namespace Database\Factories;

use App\Models\EventGroup;
use App\Models\Event;
use App\Models\Branch;
use App\Models\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventGroupFactory extends Factory
{
    protected $model = EventGroup::class;

    public function definition(): array
    {
        $branch = Branch::factory()->create();
        $group  = Group::factory()->create();

        return [
            'event_id'     => Event::factory(),
            'branch_id'    => $branch->id,
            'group_id'     => $group->id,

            'branch_name'  => $branch->name ?? $this->faker->word,
            'group_name'   => $group->name ?? $this->faker->word,
            'full_name'    => $this->faker->sentence(3),

            'max_age'      => $this->faker->numberBetween(7, 25),
            'status'       => 'active',

            'is_team'      => false,
            'use_custom_judges' => false,

            'order_number' => $this->faker->numberBetween(1, 20),
        ];
    }

    public function team(): static
    {
        return $this->state(fn () => [
            'is_team' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => 'inactive',
        ]);
    }
}
