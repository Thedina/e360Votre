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

class TinyInteger extends Integer
{
    public function __construct($name, $label, $default = null)
    {
        parent::__construct($name, $label, $default);
        $this->specification = [
            'type'=>DB::TINYINT,
            'length'=>3
        ];
    }

    /**
     * @param $value
     * @return mixed
     * @throws InvalidValueException
     */
    public static function validate($value)
    {
        $value = parent::validate($value);
        if($value < 0 || $value > 255) {
            throw new InvalidValueException("TinyInteger::validate(): value {$value} out of range for TINYINT.");
        }

        return $value;
    }

    public static function hasValidate()
    {
        return true;
    }
}