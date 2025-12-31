<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name'               => $this->faker->name,
            'email'              => $this->faker->unique()->safeEmail,
            'username'           => $this->faker->unique()->userName,
            'email_verified_at'  => now(),
            'password'           => Hash::make('password123'),

            'avatar'             => null,
            'id_employee'        => null,
            'docs_update_state'  => false,
            'can_multiple_role'  => true,

            // lokasi nullable
            'province_id'        => null,
            'regency_id'         => null,
            'district_id'        => null,
            'village_id'         => null,

            'remember_token'     => null,
            'is_active'          => true,
            'created_at'         => now(),
            'updated_at'         => now(),
        ];
    }

    /**
     * Default: user dengan role ADMIN_EVENT
     */
    public function configure()
    {
        return $this->afterCreating(function (User $user) {
            $roles = Role::whereIn('name', [
                'ADMIN_EVENT',
            ])->pluck('id');

            $user->roles()->syncWithoutDetaching($roles);
        });
    }

    /**
     * State: SUPERADMIN + ADMIN_EVENT + PENDAFTARAN
     */
    public function superAdmin(): static
    {
        return $this->afterCreating(function (User $user) {
            $roles = Role::whereIn('name', [
                'SUPERADMIN',
                'ADMIN_EVENT',
                'PENDAFTARAN',
            ])->pluck('id');

            $user->roles()->syncWithoutDetaching($roles);
        });
    }

    /**
     * State: ADMIN_EVENT only
     */
    public function adminEvent(): static
    {
        return $this->afterCreating(function (User $user) {
            $roles = Role::where('name', 'ADMIN_EVENT')->pluck('id');
            $user->roles()->syncWithoutDetaching($roles);
        });
    }

    /**
     * State: PENDAFTARAN
     */
    public function pendaftaran(): static
    {
        return $this->afterCreating(function (User $user) {
            $roles = Role::where('name', 'PENDAFTARAN')->pluck('id');
            $user->roles()->syncWithoutDetaching($roles);
        });
    }

    /**
     * State: inactive user
     */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
