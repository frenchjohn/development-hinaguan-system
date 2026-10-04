<?php

namespace App\Providers;

use App\Mail\Transports\GmailWebhookTransport;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        Blade::anonymousComponentPath(resource_path('components'));

        if (env('APP_ENV') !== 'local') {
            URL::forceScheme('https');
        }

        Mail::extend('gmail_api', function (array $config = []) {
            $url = $config['endpoint'] ?? $config['url'] ?? env('GMAIL_WEBHOOK_URL') ?? '';
            return new GmailWebhookTransport($url);
        });

        Mail::extend('brevo_api', function (array $config = []) {
            $key = $config['key'] ?? env('BREVO_API_KEY') ?? env('MAIL_PASSWORD') ?? '';
            return new \App\Mail\Transports\BrevoApiTransport($key);
        });
    }
}
