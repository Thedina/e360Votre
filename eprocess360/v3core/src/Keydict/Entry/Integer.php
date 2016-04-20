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

class Integer extends Int
{
    protected $fieldType = 'text';

    public function __construct($name, $label, $default = null)
    {
        parent::__construct($name, $label, $default);
        $this->specification = [
            'type'=>DB::INT,
            'length'=>10
        ];
    }

    public static function validate($value)
    {
        if ($value===null) $value = 0;
        if (gettype($value) == 'string') {
            $value = preg_replace('/[^0-9]/', '', (int)$value);
        } elseif (gettype($value) != 'integer') {
            throw new \Exception("Invalid value for Integer.");
        }
        //This is a hack for testing on 32-bit systems. Can be improved later.
        if (PHP_INT_SIZE > 4 && $value > 1 << 31) throw new \Exception("32 bit integer out of bounds.");
        return (int)$value;
    }

    public static function hasValidate()
    {
        return true;
    }

    public static function validateSleep($value)
    {
        if ($value===null) $value = 0;
        if (gettype($value) == 'string') {
            $value = preg_replace('/[^0-9]/', '', (int)$value);
        }
        return $value;
    }

    public function sleep($depth = 0)
    {
        return (int)$this->value;
    }

    public function cleanSleep($depth = 0)
    {
        return (int)$this->value;
    }
}