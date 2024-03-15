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
     * Generate keys
     */
    protected $generateKeys = true;

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
     * ACF field keys
     */
    protected $field_keys = [];

    /**
     * Build the ACF
     */
    public function build()
    {
        $fields = $this->fields();
        
        $fields = $this->autoKeyGenerate($fields);

        return [
            'key' => $this->getKey(),
            'title' => $this->getTitle(),
            'fields' => $fields,
            'location' => $this->location(),
            ...$this->options(),
        ];
    }

    /**
     * Fix fields for auto key generate
     */
    public function autoKeyGenerate($fields)
    {
        if (!$this->generateKeys) {
            return $fields;
        }
        
        $fields = $this->generateKeys($fields, $this->getKey() . '_');

        $fields = $this->generateKeysFixConditions($fields);

        return $fields;
    }

    /**
     * Generate key
     */
    public function generateKeys(array $fields, $prefix)
    {
        foreach ($fields as $key => $field) {
            $fields[$key] = $this->generateKey($field, $prefix);
        }

        return $fields;
    }

    /**
     * Generate key
     */
    public function generateKey(array $field, $prefix)
    {
        if (!isset($field['key'])) {
            $field['key'] = $prefix . $field['name'];
        }

        $this->field_keys[$field['name']] = $field['key'];

        if (isset($field['sub_fields'])) {
            $field['sub_fields'] = $this->generateKeys($field['sub_fields'], $field['key'] . '_');
        }
        
        return $field;
    }

    /**
     * Generate keys condition
     */
    public function generateKeysFixConditions(array $fields)
    {
        foreach ($fields as $key => $field) {
            $fields[$key] = $this->generateKeyFixConditions($field);
        }

        return $fields;
    }

    /**
     * Generate key condition
     */
    public function generateKeyFixConditions(array $field)
    {
        if (isset($field['conditional_logic'])) {
            foreach ($field['conditional_logic'] as $key => $logic) {
                foreach ($logic as $k => $condition) {
                    if(isset($this->field_keys[$condition['field']])){
                        $field['conditional_logic'][$key][$k]['field'] = $this->field_keys[$condition['field']];
                    }
                }
            }
        }

        if (isset($field['sub_fields'])) {
            $field['sub_fields'] = $this->generateKeysFixConditions($field['sub_fields']);
        }

        return $field;
    }

}