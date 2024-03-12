<?php

namespace OutlawzTeam\Radicle;

use Illuminate\Support\Arr;
use Roots\Acorn\Application;

class Acf
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
}
