<?php

namespace eprocess360\v3core\Keydict\Entry;


use eprocess360\v3core\DB;

class FixedString64 extends String
{

    public static function getData()
    {
        return [
            'type'=>DB::CHAR,
            'length'=>64
        ];
    }
}