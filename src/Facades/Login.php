<?php

namespace OutlawzTeam\Radicle\Facades;

use Illuminate\Support\Facades\Facade;

class Login extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'Login';
    }
}
