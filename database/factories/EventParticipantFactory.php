<?php

namespace Database\Factories;

use App\Models\EventParticipant;
use App\Models\Participant;
use App\Models\Event;
use App\Models\EventBranch;
use App\Models\EventGroup;
use App\Models\EventCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventParticipantFactory extends Factory
{
    protected $model = EventParticipant::class;

    public function definition(): array
    {
        // Buat hirarki event → branch → group → category
        $event = Event::factory()->create();

        $branch = EventBranch::factory()->create([
            'event_id' => $event->id,
        ]);

        $group = EventGroup::factory()->create([
            'event_id'        => $event->id,
            'branch_id'       => $branch->branch_id,
        ]);

        $category = EventCategory::factory()->create([
            'event_id'       => $event->id,
            'group_id'       => $group->group_id,
        ]);

        return [
            'event_id'          => $event->id,
            'participant_id'    => Participant::factory(),

            // RELASI WAJIB → VALID
            'event_branch_id'   => $branch->id,
            'event_group_id'    => $group->id,
            'event_category_id' => $category->id,

            'event_team_id'     => null,

            // Umur snapshot
            'age_year'          => $this->faker->numberBetween(7, 20),
            'age_month'         => $this->faker->numberBetween(0, 11),
            'age_day'           => $this->faker->numberBetween(0, 30),

            'contingent'        => $this->faker->city,

            // Status pendaftaran
            'registration_status' => 'bank_data',
            'registration_notes'  => null,
            'register_at'         => null,

            'moved_by'          => null,
            'verified_by'       => null,
            'verified_at'       => null,

            // Daftar ulang
            'reregistration_status' => 'not_yet',
            'reregistered_at'       => null,
            'reregistered_by'       => null,
            'reregistration_notes'  => null,

            // Nomor peserta
            'branch_code'       => null,
            'branch_sequence'   => null,
            'participant_number'=> null,
        ];
    }

    /* =======================
     |  STATES
     =======================*/

    public function verified(): static
    {
        return $this->state(fn () => [
            'registration_status' => 'verified',
            'verified_at'         => now(),
        ]);
    }

    public function reregistered(): static
    {
        return $this->state(fn () => [
            'reregistration_status' => 'verified',
            'reregistered_at'       => now(),
        ]);
    }

    public function team(int $teamId): static
    {
        return $this->state(fn () => [
            'event_team_id' => $teamId,
        ]);
    }
}
