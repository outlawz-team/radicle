<?php

namespace OutlawzTeam\Radicle\Providers;

use Illuminate\Support\ServiceProvider;
use OutlawzTeam\Radicle\Facades\Acf;

class AcfServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Acf', function () {
            return new Acf($this->app);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // $this->commands([
        //     AcfCommand::class,
        // ]);

        $this->app->make('Acf');
    }
}
