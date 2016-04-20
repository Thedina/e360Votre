<?php
/**
 * Created by PhpStorm.
 * User: kira
 * Date: 8/5/2015
 * Time: 11:30 AM
 */

namespace eprocess360\v3core\Keydict\Entry;


use eprocess360\v3core\Keydict\Entry;
use eprocess360\v3core\Keydict\Exception\InvalidValueException;
use eprocess360\v3core\Keydict\InterfaceEntry;
use eprocess360\v3core\DB;

/**
 * Class PhoneNumber
 * @package eprocess360\v3core\Keydict\Entry
 */
class PhoneNumber extends String
{

    public function __construct($name, $label, $default = null)
    {
        parent::__construct($name, $label, $default);
        $this->specification = [
            'type'=>DB::VARCHAR,
            'length'=>16
        ];
    }

    public function sleep($depth = 0)
    {
        return preg_replace('/[^0-9]/', '', $this->value);
    }

    public static function format($value)
    {
        if($value)
            return sprintf("(%s) %s-%s",substr($value,0,3),substr($value,3,3),substr($value,6,4));
        else
            return '';
    }

    public function wakeup($value)
    {
        $this->value = static::format($value);
    }

    public static function validate($value)
    {
        $value = preg_replace('/[^0-9]/', '', $value);
        if (substr($value,0,1)==1) $value = substr($value,1);
        if (strlen($value)==10) {
            return static::format($value);
        }
        throw new InvalidValueException("Invalid phone number.  Phone numbers must be specified in XXX XXX-XXXX or similar formats.");
    }

    public static function validateSleep($value)
    {
        $value = preg_replace('/[^0-9]/', '', $value);
        //if (substr($value,0,1)==1) $value = substr($value,1);
        if (strlen($value)==10) {
            return $value;
        }
        throw new InvalidValueException("Invalid phone number.  Phone numbers must be specified in XXX XXX-XXXX or similar formats.");
    }

    public static function hasValidate()
    {
        return true;
    }
}