<?php

namespace OutlawzTeam\Radicle\Providers;

use Illuminate\Support\ServiceProvider;
use OutlawzTeam\Radicle\Acf;
use OutlawzTeam\Radicle\Console\MakeAcfCommand;
use OutlawzTeam\Radicle\Facades\Acf as FacadesAcf;

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

        $this->mergeConfigFrom(
            __DIR__.'/../../config/acf.php',
            'acf'
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
            __DIR__.'/../../config/acf.php' => $this->app->configPath('acf.php'),
        ], 'acf');

        if(function_exists('acf_add_local_field_group')){
            $this->commands([
                MakeAcfCommand::class,
            ]);
        }

        $this->app->make('Acf');

        FacadesAcf::boot();
    }
}
