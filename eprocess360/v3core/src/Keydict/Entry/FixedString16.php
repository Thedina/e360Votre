<?php

namespace eprocess360\v3core\Keydict\Entry;


use eprocess360\v3core\DB;

/**
 * Class FixedString32
 * @package eprocess360\v3core\Keydict\Entry
 */
class FixedString16 extends String
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
            'type'=>DB::CHAR,
            'length'=>16
        ];
    }
}