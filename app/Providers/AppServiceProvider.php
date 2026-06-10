<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\Transport\BrevoTransport;
use Illuminate\Support\Facades\Broadcast;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Integration provider bindings (mock vs real decided by env flags)
        $this->registerIntegrationProviders();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Mail::extend('brevo', function (array $config) {
            return new BrevoTransport($config['key']);
        });

        RateLimiter::for('forgot-password', function (Request $request) {
            return Limit::perMinute(3)->by($request->input('email') . '|' . $request->ip());
        });

        Broadcast::routes(['middleware' => ['web', 'auth']]);
    }

    protected function registerIntegrationProviders(): void
    {
        // Each provider auto-detects its API key from .env.
        // No key set → mock data. Key set → real API. Nothing else to change.

        $this->app->bind(
            \App\Services\Integrations\Contracts\EldProviderInterface::class,
            \App\Services\Integrations\Providers\EldProvider::class
        );

        $this->app->bind(
            \App\Services\Integrations\Contracts\LoadBoardProviderInterface::class,
            \App\Services\Integrations\Providers\LoadBoardProvider::class
        );

        $this->app->bind(
            \App\Services\Integrations\Contracts\DocumentAiProviderInterface::class,
            \App\Services\Integrations\Providers\DocumentAiProvider::class
        );

        $this->app->bind(
            \App\Services\Integrations\Contracts\MapsProviderInterface::class,
            \App\Services\Integrations\Providers\MapsProvider::class
        );

        $this->app->bind(
            \App\Services\Integrations\Contracts\WeatherProviderInterface::class,
            \App\Services\Integrations\Providers\WeatherProvider::class
        );

        $this->app->bind(
            \App\Services\Integrations\Contracts\FuelProviderInterface::class,
            \App\Services\Integrations\Providers\FuelProvider::class
        );

        $this->app->bind(
            \App\Services\Integrations\Contracts\NotificationProviderInterface::class,
            \App\Services\Integrations\Providers\NotificationProvider::class
        );

        $this->app->bind(
            \App\Services\Integrations\Contracts\ComplianceProviderInterface::class,
            \App\Services\Integrations\Providers\ComplianceProvider::class
        );

        $this->app->bind(
            \App\Services\Integrations\Contracts\PaymentProviderInterface::class,
            \App\Services\Integrations\Providers\PaymentProvider::class
        );

        $this->app->bind(
            \App\Services\Integrations\Contracts\IftaProviderInterface::class,
            \App\Services\Integrations\Providers\IftaProvider::class
        );

        $this->app->bind(
            \App\Services\Ai\GeminiClient::class,
            fn () => new \App\Services\Ai\GeminiClient()
        );
    }
}