<?php

namespace OutlawzTeam\Radicle\Providers;

use Illuminate\Support\ServiceProvider;
use OutlawzTeam\Radicle\Console\BoostInstallCommand;

class BoostServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                BoostInstallCommand::class,
            ]);
        }
    }
}
