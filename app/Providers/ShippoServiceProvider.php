<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Shippo;
class ShippoServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        Shippo::setApiKey(config('services.shippo.key'));
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
