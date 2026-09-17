<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use App\Models\Participant;
use App\Policies\ParticipantPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Participant::class => ParticipantPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::define('viewLogViewer', function (?User $user) {
            // Jika tidak ada user yang login, otomatis tolak
            if (!$user) {
                return false;
            }

            return optional($user->role)->slug === 'superadmin';
        });
    }
}
