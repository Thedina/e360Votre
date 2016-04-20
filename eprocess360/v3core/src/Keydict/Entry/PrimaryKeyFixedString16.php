<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 1/11/16
 * Time: 8:48 AM
 */

namespace eprocess360\v3core\Keydict\Entry;


use eprocess360\v3core\DB;

class PrimaryKeyFixedString16 extends String
{
    public function __construct($name, $label, $default = null)
    {
        parent::__construct($name, $label, $default);
        $this->specification = [
            'type'=>DB::CHAR,
            'length'=>16,
            'primary_key'=>true
        ];
    }

    public static function validate($value)
    {
        return parent::validate($value);
    }
}