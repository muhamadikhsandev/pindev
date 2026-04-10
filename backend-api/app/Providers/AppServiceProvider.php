<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

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
        /**
         * Kustomisasi URL Verifikasi Email
         * Memastikan ID dan HASH masuk ke query string agar bisa dibaca Next.js
         */
        VerifyEmail::createUrlUsing(function ($notifiable) {
            // 1. Domain frontend Next.js kamu
            $frontendUrl = 'http://localhost:3000/verify-email';

            // 2. Buat signed URL sementara dari Laravel
            // Kita tetap butuh ini untuk mendapatkan 'signature' dan 'expires' yang valid
            $verifyUrl = URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes(60),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );

            // 3. Ambil signature dan expires dari hasil generate tadi
            $queryString = parse_url($verifyUrl, PHP_URL_QUERY);

            // 4. SUSUN ULANG URL-NYA
            // Kita paksa id dan hash masuk ke query string (?id=...&hash=...)
            // Supaya Next.js bisa baca pakai searchParams.get('id')
            return $frontendUrl . '?id=' . $notifiable->getKey() . 
                                  '&hash=' . sha1($notifiable->getEmailForVerification()) . 
                                  '&' . $queryString;
        });
    }
}