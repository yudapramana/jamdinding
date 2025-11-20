<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        if (! $settings) {
            return config('settings.default');
        }

        return $settings;
    }

    // public function update()
    // {
    //     $settings = request()->validate([
    //         'app_name' => ['required', 'string'],
    //         'date_format' => ['required', 'string'],
    //         'pagination_limit' => ['required', 'int', 'min:1', 'max:100'],
    //     ]);

    //     foreach ($settings as $key => $value) {
    //         Setting::updateOrCreate(
    //             ['key' => $key],
    //             ['value' => $value],
    //         );
    //     }

    //     Cache::flush('settings');

    //     return response()->json(['success' => true]);
    // }

    public function update()
    {
        // Ambil nilai maintenance sebelumnya
        $prevMaintenance = optional(
            Setting::where('key', 'maintenance')->first()
        )->value;

        $prevOn = in_array(strtolower((string) $prevMaintenance), ['1','true','on','yes'], true);

        // Validasi (pakai key 'maintenance')
        $settings = request()->validate([
            'app_name'         => ['required', 'string'],
            'date_format'      => ['required', 'string'],
            'pagination_limit' => ['required', 'int', 'min:1', 'max:100'],
            'maintenance'      => ['required', 'boolean'],
        ]);

        // Simpan semua key-value (boolean -> "1"/"0" agar konsisten)
        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => is_bool($value) ? (string) (int) $value : $value],
            );
        }

        // Bersihkan cache settings (pakai forget utk single key)
        Cache::forget('settings');

        $nowOn = (bool) $settings['maintenance'];

        // Logout massal SETIAP kali toggle ON ↔ OFF (hanya saat berubah)
        if ($prevOn !== $nowOn) {
            $this->logoutAllUsersSimple();
            \App\Models\User::each(function ($u) {
                Auth::login($u);
                Auth::logout();
            });
        }

        Cache::flush('settings');
        Cache::forget('settings');

        return response()->json(['success' => true]);
    }

    /**
     * Cara paling simple & cepat untuk logout semua user:
     * - Hapus semua Sanctum tokens (jika dipakai)
     * - Bersihkan semua sesi tergantung session driver
     * - (Opsional) reset remember_token agar "remember me" ikut invalid
     */
    protected function logoutAllUsersSimple(): void
    {
        // 1) Revoke semua token Sanctum (abaikan jika tidak pakai Sanctum)
        if (class_exists(\Laravel\Sanctum\PersonalAccessToken::class)) {
            \Laravel\Sanctum\PersonalAccessToken::query()->delete();
        }

        // 2) Bersihkan sesi sesuai driver
        $driver = config('session.driver');

        if ($driver === 'database') {
            // Paling simple untuk database sessions:
            DB::table(config('session.table', 'sessions'))->truncate();
        } elseif ($driver === 'file') {
            // Hapus semua file session (kecuali .gitignore)
            $dir = storage_path('framework/sessions');
            foreach (glob($dir.'/*') as $file) {
                if (is_file($file) && basename($file) !== '.gitignore') {
                    @unlink($file);
                }
            }
        } elseif ($driver === 'redis') {
            // Simpel, tapi hati-hati: jangan flush seluruh Redis!
            // Hapus hanya keys dengan prefix session.
            $connection = app('redis')->connection(config('session.connection'));
            $prefix = config('session.prefix', 'laravel_database_');
            $cursor = null;
            do {
                $scan = $connection->scan($cursor, $prefix.'*', 1000);
                $keys = $scan[1] ?? [];
                if (!empty($keys)) {
                    $connection->del($keys);
                }
                $cursor = $scan[0] ?? 0;
            } while ($cursor != 0);
        }

        // 3) (Opsional) invalidasi remember_token
        // DB::table('users')->update(['remember_token' => null]);
    }
}