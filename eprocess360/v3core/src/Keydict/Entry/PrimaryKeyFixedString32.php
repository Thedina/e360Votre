<?php

namespace eprocess360\v3core\Keydict\Entry;
use eprocess360\v3core\DB;


class PrimaryKeyFixedString32 extends String
{
    public function __construct($name, $label, $default = null)
    {
        parent::__construct($name, $label, $default);
        $this->specification = [
            'type'=>DB::CHAR,
            'length'=>32,
            'primary_key'=>true
        ];
    }

    public static function validate($value)
    {
        return parent::validate($value);
    }
}