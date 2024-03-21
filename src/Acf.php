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
        if(function_exists('acf_add_options_page') ) {
            foreach (config('acf.options_pages', []) as $options_page) {
                $parent_slug = $options_page['menu_slug'];
                acf_add_options_page($options_page);

                if (function_exists('acf_add_options_sub_page')) {
                    if (isset($options_page['sub_pages'])) {
                        foreach ($options_page['sub_pages'] as $sub_page) {
                            acf_add_options_sub_page(['parent_slug' => $parent_slug, ...$sub_page]);
                        }
                    }
                }
            }
        }

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
        if (!file_exists($acfPath)) {
            return [];
        }
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
