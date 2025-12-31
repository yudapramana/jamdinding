<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use App\Models\User;
use App\Models\Event;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed minimal role/permission jika perlu
        // $this->seed();

        RateLimiter::clear('test|127.0.0.1');
    }

    /**
     * Helper: set captcha ke session
     */
    protected function withCaptcha(string $code = 'ABCD')
    {
        Session::put('captcha_code', $code);
        return $this;
    }

    /**
     * Helper: create user
     */
    protected function createUser()
    {
        return User::factory()->create([
            'username' => 'testuser',
            'password' => Hash::make('password123'),
        ]);
    }

    /**
     * Helper: create event
     */
    protected function createEvent(array $attrs = [])
    {
        return Event::factory()->create(array_merge([
            'event_key' => 'mtq-test',
            'is_active' => true,
        ], $attrs));
    }

    /** @test */
    public function user_can_login_successfully_with_valid_event_and_captcha()
    {
        $user  = $this->createUser();
        $event = $this->createEvent();

        $oldSessionId = session()->getId();

        $this->withCaptcha()
            ->post('/login', [
                'username'  => 'testuser',
                'password'  => 'password123',
                'captcha'   => 'ABCD',
                'event_key' => $event->event_key,
            ])
            ->assertStatus(302); // redirect setelah login

        $this->assertAuthenticatedAs($user);

        // Session regenerate
        $this->assertNotEquals($oldSessionId, session()->getId());
    }

    /** @test */
    public function login_fails_if_event_is_inactive()
    {
        $this->createUser();
        $event = $this->createEvent(['is_active' => false]);

        $this->withCaptcha()
            ->post('/login', [
                'username'  => 'testuser',
                'password'  => 'password123',
                'captcha'   => 'ABCD',
                'event_key' => $event->event_key,
            ])
            ->assertRedirect() // ⬅️ FIX
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }


    /** @test */
    public function login_fails_with_wrong_password()
    {
        $this->createUser();
        $event = $this->createEvent();

        $this->withCaptcha()
            ->post('/login', [
                'username'  => 'testuser',
                'password'  => 'wrong-password',
                'captcha'   => 'ABCD',
                'event_key' => $event->event_key,
            ])
            ->assertRedirect() // ⬅️ FIX
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }


    /** @test */
    public function login_fails_with_invalid_captcha()
    {
        $this->createUser();
        $event = $this->createEvent();

        Session::put('captcha_code', 'RIGHT');

        $this->post('/login', [
            'username'  => 'testuser',
            'password'  => 'password123',
            'captcha'   => 'WRONG',
            'event_key' => $event->event_key,
        ])
        ->assertRedirect() // ⬅️ FIX
        ->assertSessionHasErrors('captcha');

        $this->assertGuest();
    }


    /** @test */
    public function login_is_rate_limited_after_too_many_attempts()
    {
        $this->createUser();
        $event = $this->createEvent();

        Session::put('captcha_code', 'ABCD');

        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', [
                'username'  => 'testuser',
                'password'  => 'wrong-password',
                'captcha'   => 'ABCD',
                'event_key' => $event->event_key,
            ]);
        }

        $this->post('/login', [
            'username'  => 'testuser',
            'password'  => 'wrong-password',
            'captcha'   => 'ABCD',
            'event_key' => $event->event_key,
        ])
        ->assertStatus(429); // ⬅️ FIX (BUKAN 422)
    }

}
