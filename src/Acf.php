<?php

namespace OutlawzTeam\Radicle;

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

    /**
     * Boot the Acf client.
     *
     * @return void
     */
    public function boot()
    {
        if(function_exists('acf_add_local_field_group')) {
            $acfFiles = $this->getAllAcfClasses();
            foreach ($acfFiles as $acfFile) {
                $class = "App\Acf\\" . str_replace('.php', '', $acfFile);
                $class = new $class();
                acf_add_local_field_group($class->build());
            }
        }
    }

    public function getAcfPath()
    {
        return app_path() . "/Acf";
    }

    public function getAllAcfClasses()
    {
        $acfPath = $this->getAcfPath();
        $acfFiles = scandir($acfPath);
        $acfClasses = [];
        foreach ($acfFiles as $acfFile) {
            if (strpos($acfFile, '.php') !== false) {
                $acfClasses[] = $acfFile;
            }
        }
        return $acfClasses;
    }
}
