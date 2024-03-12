<?php

namespace OutlawzTeam\Radicle\Providers;

use Illuminate\Support\ServiceProvider;
use OutlawzTeam\Radicle\Console\RadicleCommand;
use OutlawzTeam\Radicle\Radicle;

class RadicleServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Example', function () {
            return new Radicle($this->app);
        });

        $this->mergeConfigFrom(
            __DIR__.'/../../config/radicle.php',
            'radicle'
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
            __DIR__.'/../../config/example.php' => $this->app->configPath('radicle.php'),
        ], 'config');

        $this->loadViewsFrom(
            __DIR__.'/../../resources/views',
            'Radicle',
        );

        $this->commands([
            RadicleCommand::class,
        ]);

        $this->app->make('Radicle');
    }
}
