<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema; //this
use Opcodes\LogViewer\Facades\LogViewer;

class AppServiceProvider extends ServiceProvider
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
        Schema::defaultStringLength(191); //this

        // Otorisasi khusus untuk opcodesio/log-viewer di Production
        LogViewer::auth(function ($request) {
            // 1. Cek apakah ada user yang sedang login
            // 2. Cek apakah relasi role->slug miliknya adalah 'superadmin'
            return $request->user() && optional($request->user()->role)->slug === 'superadmin';
        });
    }
}
