<?php

namespace Plisio\PlisioSdkLaravel\Providers;

use Illuminate\Support\ServiceProvider;

class PlisioProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/plisio.php' => config_path('plisio.php'),
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/plisio.php', 'plisio'
        );
    }
}
