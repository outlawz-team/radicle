<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Flare
    |--------------------------------------------------------------------------
    |
    | Flare is a great service for tracking errors and exceptions in your
    | application. You can find out more about Flare at the following URL:
    | https://flareapp.io
    |
    */

    /**
     * The key to authenticate with the Flare API.
     */
    'key' => env('FLARE_KEY', false),

    /**
     * The level of errors to report.
     */
    'level' => E_ALL,

    /**
     * The exceptions that should not be reported.
     */
    'exceptions' => [
        //
    ],
];
