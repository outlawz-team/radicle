<?php

namespace OutlawzTeam\Radicle\Support;

abstract class Acf
{
    /**
     * ACF key
     */
    protected $key;

    /**
     * ACF title
     */
    protected $title;

    /**
     * Get the ACF key
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Get the ACF title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * ACF fields
     */
    public function fields()
    {
        return [];
    }

    /**
     * ACF Location
     */
    public function location()
    {
        return [];
    }

    /**
     * ACF Options
     */
    public function options()
    {
        return [];
    }

    /**
     * Build the ACF
     */
    public function build()
    {
        return [
            'key' => $this->getKey(),
            'title' => $this->getTitle(),
            'fields' => $this->fields(),
            'location' => $this->location(),
            ...$this->options(),
        ];
    }
}