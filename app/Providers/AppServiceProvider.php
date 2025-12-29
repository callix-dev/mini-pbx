<?php

namespace App\Providers;

use App\Models\Extension;
use App\Models\ExtensionGroup;
use App\Observers\ExtensionObserver;
use App\Observers\ExtensionGroupObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind PjsipRealtimeService as a singleton
        $this->app->singleton(\App\Services\Asterisk\PjsipRealtimeService::class);
        
        // Bind AsteriskQueueSyncService as a singleton
        $this->app->singleton(\App\Services\Asterisk\AsteriskQueueSyncService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Extension observer for PJSIP sync
        Extension::observe(ExtensionObserver::class);
        
        // Register ExtensionGroup observer for Asterisk queue sync
        ExtensionGroup::observe(ExtensionGroupObserver::class);
    }
}
