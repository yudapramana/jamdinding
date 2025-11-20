<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SocialiteController extends Controller
{
    /**
     * Redirect user ke Google untuk login.
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle callback dari Google.
     */
    public function callback()
    {

        // Google user object dari google
        $userFromGoogle = Socialite::driver('google')->user();

        // Ambil user dari database berdasarkan google user id
        $userFromDatabase = User::where('google_id', $userFromGoogle->getId())->first();

        $userLoggedin = Auth::user();
        $user = User::find($userLoggedin->id);

        // Jika tidak ada user google, maka update
        if (!$userFromDatabase) {   

            $user->update([
                'google_id' => $userFromGoogle->getId(),
                'email' => $userFromGoogle->getEmail(),
            ]);

            // Login user yang baru dibuat
            auth('web')->login($user);
            session()->regenerate();

            return redirect('/user/dashboard');
        }

        // Jika ada user langsung login saja
        auth('web')->login($userFromDatabase);
        session()->regenerate();

        return redirect('/user/dashboard');
    }


}
