<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'app_name'        => 'e-MTQ Platform',
            'event_key'       => $this->faker->unique()->slug(3),
            'event_name'      => 'MTQ ' . $this->faker->city,
            'event_year'      => now()->year,
            'event_location'  => $this->faker->city,
            'event_tagline'   => $this->faker->sentence(),
            'start_date'      => now()->addDays(10)->toDateString(),
            'end_date'        => now()->addDays(15)->toDateString(),
            'event_level'     => 'regency',
            'is_active'       => true,
        ];
    }
}
