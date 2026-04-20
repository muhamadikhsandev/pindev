<?php

namespace App\Providers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        VerifyEmail::createUrlUsing(function ($notifiable) {
            $id = $notifiable->getKey();
            $hash = sha1($notifiable->getEmailForVerification());

            // 1. Generate URL internal Laravel (berisi expires & signature)
            $temporarySignedUrl = URL::temporarySignedRoute(
                'api.verification.verify', // Harus match dengan name di Route
                Carbon::now()->addMinutes(60),
                ['id' => $id, 'hash' => $hash]
            );

            // 2. Ambil query string (expires & signature)
            $queryString = parse_url($temporarySignedUrl, PHP_URL_QUERY);

            // 3. Gabungkan ke URL Next.js. Kita pindahkan id & hash ke Query String
            $finalUrl = "http://localhost:3000/verify-email?id={$id}&hash={$hash}&{$queryString}";

            Log::info('--- EMAIL VERIFICATION LINK GENERATED ---');
            Log::info('Target Next.js: ' . $finalUrl);

            return $finalUrl;
        });
    }
}