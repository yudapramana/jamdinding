<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Laravel\Socialite\Facades\Socialite;
use Exception;
use App\Models\User;

class OauthController extends Controller
{
    
    public function redirectToProvider()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleProviderCallback()
    {
        try {

                $userLoggedin = Auth::user();

                if($userLoggedin) {

                // Google user object dari google
                $userFromGoogle = Socialite::driver('google')->user();

                // Ambil user dari database berdasarkan google user id
                $userFromDatabase = User::where('google_id', $userFromGoogle->getId())->first();

                
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

            } else {
                return 'no user logged in';
            }

        } catch (Exception $e) {
            dd($e->getMessage());
        }
    }


}
