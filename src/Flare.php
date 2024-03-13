<?php

namespace OutlawzTeam\Radicle;

use Roots\Acorn\Application;
use Spatie\FlareClient\Flare as FlareClientFlare;
use Throwable;

class Flare
{
    /**
     * The application instance.
     *
     * @var \Roots\Acorn\Application
     */
    protected $app;

    /**
     * Create a new radicle instance.
     *
     * @param  \Roots\Acorn\Application  $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Boot the Flare client.
     *
     * @return void
     */
    public function boot()
    {
        $flare = FlareClientFlare::make(config('flare.key'))->registerFlareHandlers();

        $flare->reportErrorLevels(config('flare.level', E_ALL));

        $flare->filterExceptionsUsing(function (Throwable $throwable) {
            foreach (config('flare.exceptions', []) as $exception) {
                if ($throwable instanceof $exception) {
                    return false;
                }
            }

            return true;
        });
    }
}
