<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\Event;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use Illuminate\Support\Facades\Http;
use Hash;
use NjoguAmos\Turnstile\Rules\TurnstileRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Session;


class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::loginView(function () {
            // return view('auth.login');
            return view('admin.layouts.app');

        });
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::authenticateUsing(function (Request $request) {

            /*
            |--------------------------------------------------------------------------
            | 0 CHECK EVENT AKTIF
            |--------------------------------------------------------------------------
            */
            $event = \App\Models\Event::where('event_key', $request->event_key)
                ->where('is_active', true)
                ->first();

            if (!$event) {
                throw ValidationException::withMessages([
                    'username' => [
                        'Login tidak dapat diproses. Pastikan event dan akun Anda valid.'
                    ],
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | 1️⃣ VALIDASI INPUT DASAR
            |--------------------------------------------------------------------------
            */
            $request->validate([
                'username' => ['required', 'string'],
                'password' => ['required', 'string'],
                'captcha'  => ['required', 'string'],
            ]);

            /*
            |--------------------------------------------------------------------------
            | 2️⃣ CEK ENVIRONMENT (HANYA DARI .env)
            |--------------------------------------------------------------------------
            */
            $appEnv = strtolower(config('app.env', 'production'));
            $isDev  = in_array($appEnv, ['local', 'development']);

            /*
            |--------------------------------------------------------------------------
            | 3️⃣ RATE LIMIT (AKTIF HANYA DI NON-DEV)
            |--------------------------------------------------------------------------
            */
            $throttleKey = strtolower($request->username) . '|' . $request->ip();

            if (! $isDev && RateLimiter::tooManyAttempts($throttleKey, 5)) {
                throw ValidationException::withMessages([
                    'username' => ['Terlalu banyak percobaan login. Silakan tunggu 1 menit.'],
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 4️⃣ VALIDASI CAPTCHA (SESSION)
            |--------------------------------------------------------------------------
            */
            $captchaSession = Session::get('captcha_code');

            if (! $captchaSession || strtoupper($request->captcha) !== strtoupper($captchaSession)) {

                if (! $isDev) {
                    RateLimiter::hit($throttleKey, 60);
                }

                throw ValidationException::withMessages([
                    'captcha' => ['Kode captcha tidak sesuai.'],
                ]);
            }

            // captcha dihapus hanya jika benar
            Session::forget('captcha_code');

            /*
            |--------------------------------------------------------------------------
            | 5️⃣ AUTHENTICATE USER
            |--------------------------------------------------------------------------
            */
            $user = User::where('username', $request->username)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {

                if (! $isDev) {
                    RateLimiter::hit($throttleKey, 60);
                }

                throw ValidationException::withMessages([
                    'username' => ['Username atau password salah.'],
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 6️⃣ LOGIN SUKSES → RESET RATE LIMIT
            |--------------------------------------------------------------------------
            */
            if (! $isDev) {
                RateLimiter::clear($throttleKey);
            }

            /*
            |--------------------------------------------------------------------------
            | 🔐 REGENERATE SESSION (ANTI SESSION FIXATION)
            |--------------------------------------------------------------------------
            */
            $request->session()->regenerate();

            return $user;
        });


        // Fortify::authenticateUsing(function (Request $request) {
        //     // ✅ Validasi Turnstile token
        //     // $validator = Validator::make($request->all(), [
        //     //     'cf-turnstile-response' => ['required', new TurnstileRule()],
        //     // ]);

        //     // if ($validator->fails()) {
        //     //     throw ValidationException::withMessages([
        //     //         'cf-turnstile-response' => ['Verifikasi captcha gagal. Silakan coba lagi.'],
        //     //     ]);
        //     // }


        //     $request->validate([
        //         'username' => 'required',
        //         'password' => 'required',
        //         'captcha'  => 'required',
        //     ]);

        //     if (strtoupper($request->captcha) !== Session::get('captcha_code')) {
        //         throw ValidationException::withMessages([
        //             'captcha' => ['Kode captcha tidak sesuai.'],
        //         ]);
        //         // return response()->json([
        //         //     'message' => 'Kode captcha tidak sesuai.'
        //         // ], 422);
        //     }

        //     // hapus captcha setelah dipakai
        //     Session::forget('captcha_code');


        //     // ✅ Autentikasi user
        //     $user = \App\Models\User::where('username', $request->username)->first();

        //     if (! $user || ! Hash::check($request->password, $user->password)) {
        //         throw ValidationException::withMessages([
        //             'username' => ['NIP atau password salah.'],
        //         ]);
        //     }

        //     return $user;
        // });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        // RateLimiter::for('two-factor', function (Request $request) {
        //     return Limit::perMinute(5)->by($request->session()->get('login.id'));
        // });
    }
}
