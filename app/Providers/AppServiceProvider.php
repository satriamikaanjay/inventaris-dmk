<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // (Asumsi Anda sudah punya ini untuk URL::forceScheme)
use Illuminate\Pagination\Paginator; // <--- TAMBAHKAN BARIS INI DI SINI

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Kode yang kamu tambahkan sebelumnya
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            URL::forceScheme('https');
        }

        // Paginator sekarang bisa dibaca oleh sistem
        Paginator::useTailwind(); //[cite: 18]
    }
}