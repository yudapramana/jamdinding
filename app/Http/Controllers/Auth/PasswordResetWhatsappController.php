<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PhoneResetToken;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetWhatsappController extends Controller
{
    protected Client $http;

    public function __construct()
    {
        $this->http = new Client([
            'base_uri'    => rtrim(config('services.kirimi.base_uri', 'https://api.kirimi.id'), '/') . '/',
            'timeout'     => (int) config('services.kirimi.timeout', 15),
            'http_errors' => false,
        ]);
    }

    /** ====== VIEW FORMS (WEB) ====== */
    public function showRequestForm()
    {
        return view('auth.passwords.wa-request');
    }

    public function showResetForm(Request $request)
    {
        // Bisa prefill phone dari query (?phone=628xxxx) kalau mau
        return view('auth.passwords.wa-reset', ['phone' => $request->query('phone')]);
    }

    /** ====== STEP 1: Kirim OTP via WhatsApp (API) ====== */
    public function requestReset(Request $request)
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
        ]);

        // Normalisasi 08xxxx -> 628xxxx
        $phone = $data['phone'];

        // Cek user (jangan bocorkan status ke klien)
        $user = User::whereHas('employee', function ($q) use ($phone) {
            $q->where('phone_number', $phone);
        })->first();
        // $user = User::where('phone', $phone)->first();
        
        if (preg_match('/^0\d+$/', $phone)) {
            $phone = '62' . substr($phone, 1);
        }

        
        if (!$user) {
            return response()->json([
                'ok' => true,
                'message' => 'Jika nomor terdaftar, OTP telah dikirim via WhatsApp.',
            ]);
        }

        // Generate OTP
        $otp = (string) random_int(100000, 999999);

        // Simpan hash + expiry 15 menit
        PhoneResetToken::create([
            'phone'      => $phone,
            'token_hash' => Hash::make($otp),
            'expires_at' => now()->addMinutes(15),
            'ip'         => $request->ip(),
        ]);

        // Kirim WA (Send Message Fast)
        $payload = [
            'user_code' => config('services.kirimi.user_code'),
            'device_id' => config('services.kirimi.device_id'),
            'receiver'  => $phone,
            'message'   => "KemenagPessel - SIGARDA\nKode reset password Anda: {$otp}\nBerlaku 15 menit. Jangan berikan kode ini kepada siapa pun.",
            'secret'    => config('services.kirimi.secret'),
        ];

        try {
            $this->http->post('v1/send-message-fast', [
                'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
                'json'    => $payload,
            ]);
        } catch (\Throwable $e) {
            // optional: log error provider
        }

        return response()->json([
            'ok' => true,
            'message' => 'Jika nomor terdaftar, OTP telah dikirim via WhatsApp.',
        ]);
    }

    /** ====== STEP 2: Input OTP & Reset Password (WEB) ====== */
    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'phone'                  => ['required', 'string', 'max:30'],
            'otp'                    => ['required', 'string', 'size:6'],
            'password'               => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $phone = $data['phone'];

         $user = User::whereHas('employee', function ($q) use ($phone) {
            $q->where('phone_number', $phone);
        })->first();

        // Normalisasi
        if (preg_match('/^0\d+$/', $phone)) {
            $phone = '62' . substr($phone, 1);
        }

       
        if (!$user) {
            return back()->withErrors(['phone' => 'Data tidak valid.'])->withInput();
        }

        $token = PhoneResetToken::where('phone', $phone)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (!$token || !Hash::check($data['otp'], $token->token_hash)) {
            return back()->withErrors(['otp' => 'OTP tidak valid atau telah kedaluwarsa.'])->withInput();
        }

        // Update password
        $user->password = Hash::make($data['password']);
        $user->setRememberToken(Str::random(60));
        $user->save();

        // Tandai token terpakai & invalidasi sisa token aktif
        $token->update(['used_at' => now()]);
        PhoneResetToken::where('phone', $phone)->whereNull('used_at')->update(['expires_at' => now()]);

        return redirect()->route('login')->with('status', 'Password berhasil direset. Silakan login.');
    }
}
