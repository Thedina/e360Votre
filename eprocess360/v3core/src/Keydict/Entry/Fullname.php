<?php
/**
 * Created by PhpStorm.
 * User: kira
 * Date: 8/5/2015
 * Time: 11:30 AM
 */

namespace eprocess360\v3core\Keydict\Entry;


use eprocess360\v3core\Keydict\Exception\InvalidValueException;
use eprocess360\v3core\Keydict\InterfaceEntry;
use eprocess360\v3core\DB;

class Fullname extends String
{
    public function __construct($name, $label, $default)
    {
        parent::__construct($name, $label, $default);
        $this->specification = [
            'type'=>DB::VARCHAR,
            'length'=>64
        ];
    }

    public static function validate($value)
    {
        $value = parent::validate($value);
        if (!strpos($value,' ')) throw new InvalidValueException("Full Name must contain a space.");
        return $value;
    }
}