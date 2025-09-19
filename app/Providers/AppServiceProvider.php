<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Message\ConnectorInterface;
use App\Services\Message\SMTPConnector;
use App\Models\Configuration;
use Illuminate\Support\Facades\Cache;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind the mail connector interface to the SMTP implementation
        $this->app->bind(
            ConnectorInterface::class,
            SMTPConnector::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Keep dbConfig cache in sync when configurations change externally
        Configuration::saved(function (Configuration $configuration): void {
            Cache::forever('db_config:' . $configuration->key, $configuration->value);
        });

        Configuration::deleted(function (Configuration $configuration): void {
            Cache::forget('db_config:' . $configuration->key);
        });
    }
}
