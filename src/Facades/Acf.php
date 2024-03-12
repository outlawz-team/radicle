<?php

namespace OutlawzTeam\Radicle\Facades;

use Illuminate\Support\Facades\Facade;

class Acf extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'Acf';
    }
}
