<?php

namespace eprocess360\v3core\Keydict\Entry;
use eprocess360\v3core\Keydict\Entry;
use eprocess360\v3core\DB;

class String256 extends Entry
{
    /**
     * @param string $name
     * @param string $label
     * @param null $default
     */
    public function __construct($name, $label, $default = null)
    {
        parent::__construct($name, $label, $default);
        $this->specification = [
            'type'=>DB::VARCHAR,
            'length'=>256
        ];
    }
}