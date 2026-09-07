<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Fortify\UpdateUserPassword;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $user->load('role');
        $user->load('province', 'regency', 'district', 'village');
        return $user;
    }

    public function update(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update($validated);  
        return response()->json(['message' => 'Profile updated successfully!']);
    }

    public function changePassword(Request $request, UpdateUserPassword $updater)
    {
        // 1. Ambil user dari request (Sanctum/Auth)
        $user = $request->user();
        
        $updater->update(
            $user,
            [
                'current_password' => $request->currentPassword,
                'password' => $request->password,
                'password_confirmation' => $request->passwordConfirmation,
            ]
        );

        // 👇 SOLUSI TERBARU: Update hash password di session langsung
        // Ini mencegah fitur AuthenticateSession melogout user, 
        // TANPA meregenerate CSRF Token yang bikin Vue error.
        $request->session()->put([
            'password_hash_web' => $user->fresh()->getAuthPassword(),
            'password_hash_sanctum' => $user->fresh()->getAuthPassword(),
        ]);

        // Load ulang relasi agar data di frontend tetap lengkap
        $user->load('role', 'province', 'regency', 'district', 'village');

        return response()->json([
            'message' => 'Password berhasil diubah!',
            'user' => $user // Kirim balik data user terbaru
        ]);
    }
}