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
        $message = "Tes log Laravel pada " . now();
        Log::info($message);

        $this->info("✅ Sudah coba tulis log: '{$message}'");
        $this->info("Cek file di storage/logs/laravel.log");
    }
}
