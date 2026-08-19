<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * php artisan test:log
     */
    protected $signature = 'test:log';

    /**
     * The console command description.
     */
    protected $description = 'Tes apakah Laravel bisa menulis log ke storage/logs/laravel.log';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $channel = config('logging.default');
        $path = storage_path('logs/laravel.log');

        $this->info("Channel aktif (LOG_CHANNEL): {$channel}");
        $this->info("Path target: {$path}");
        $this->info("Folder storage/logs writable? " . (is_writable(storage_path('logs')) ? 'YA' : 'TIDAK'));

        $message = "Tes log Laravel pada " . now();

        try {
            Log::info($message);
            $this->info("Log::info() dipanggil tanpa exception.");
        } catch (\Throwable $e) {
            $this->error("GAGAL menulis log!");
            $this->error("Exception: " . get_class($e));
            $this->error("Pesan: " . $e->getMessage());
            $this->error("File: " . $e->getFile() . ':' . $e->getLine());
            return Command::FAILURE;
        }

        // Verifikasi nyata: apakah file benar-benar bertambah isinya
        clearstatcache(true, $path);

        if (!file_exists($path)) {
            $this->error("❌ File TIDAK ditemukan di: {$path}");
            $this->error("Kemungkinan LOG_CHANNEL diarahkan ke driver yang tidak menulis file (mis. 'null'),");
            $this->error("atau ada custom channel/listener lain yang menangkap log ini.");
            return Command::FAILURE;
        }

        $content = file_get_contents($path);
        if (str_contains($content, $message)) {
            $this->info("✅ Sukses. Pesan ditemukan di dalam file log.");
        } else {
            $this->warn("⚠️ File ada, tapi pesan tidak ditemukan di dalamnya.");
            $this->warn("Kemungkinan file ini bukan file yang benar-benar dipakai runtime (symlink/path lain).");
        }

        $this->info("Cek manual: tail -n 5 {$path}");

        return Command::SUCCESS;
    }
}