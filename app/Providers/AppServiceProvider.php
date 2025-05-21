<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\MidtransService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        $this->app->singleton('midtrans', function () {
            return new MidtransService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
