<?php

namespace Database\Factories;

use App\Models\EventCategory;
use App\Models\Event;
use App\Models\Branch;
use App\Models\Group;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventCategoryFactory extends Factory
{
    protected $model = EventCategory::class;

    public function definition(): array
    {
        $branch   = Branch::factory()->create();
        $group    = Group::factory()->create();
        $category = Category::factory()->create();

        return [
            'event_id'       => Event::factory(),
            'branch_id'      => $branch->id,
            'group_id'       => $group->id,
            'category_id'    => $category->id,

            'branch_name'    => $branch->name ?? $this->faker->word,
            'group_name'     => $group->name ?? $this->faker->word,
            'category_name'  => $category->name ?? $this->faker->word,
            'full_name'      => $this->faker->sentence(4),

            'status'         => 'active',
            'order_number'   => $this->faker->numberBetween(1, 30),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => 'inactive',
        ]);
    }
}
