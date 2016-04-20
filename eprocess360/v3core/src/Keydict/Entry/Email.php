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

class Email extends String
{
    public function __construct($name, $label, $default)
    {
        parent::__construct($name, $label, $default);
        $this->specification = [
            'type'=>DB::VARCHAR,
            'length'=>128
        ];
    }

    public static function validate($value)
    {
        if (!$value) return null;
        $cleanvalue = parent::validate($value);
        if (!filter_var($cleanvalue, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidValueException("E-mail address '{$cleanvalue}' is invalid.", 301);
        }
        return (string)$cleanvalue;
    }

    public static function hasValidate()
    {
        return true;
    }
}