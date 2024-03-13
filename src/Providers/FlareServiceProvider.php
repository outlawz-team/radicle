<?php

namespace OutlawzTeam\Radicle\Providers;

use Illuminate\Support\ServiceProvider;
use OutlawzTeam\Radicle\Facades\Flare as FacadesFlare;
use OutlawzTeam\Radicle\Flare;

class FlareServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Flare', function () {
            return new Flare($this->app);
        });

        $this->mergeConfigFrom(
            __DIR__.'/../../config/flare.php',
            'flare'
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/flare.php' => $this->app->configPath('flare.php'),
        ], 'config');

        $this->app->make('Flare');

        if (config('flare.key')) {
            FacadesFlare::boot();
        }
    }
}
