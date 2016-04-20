<?php

namespace eprocess360\v3core\Keydict\Entry;


use eprocess360\v3core\DB;
use eprocess360\v3core\Keydict\Entry;

/**
 * Class FixedNumber
 * @package eprocess360\v3core\Keydict\Entry
 * Base class for fixed-point/decimal values. THIS IS NOT REAL, GOOD FIXED-
 * POINT MATH RIGHT NOW!
 * TODO: MAKE THIS REAL, GOOD FIXED_POINT MATH!!! (bcmath?)
 */
class FixedNumber extends Entry
{
    const TOTAL_LENGTH = 16;
    const DEC_LENGTH = 4;

    /**
     * @param $value
     * @return float
     */
    private static function toFixed($value) {
        return round(floatval($value), static::DEC_LENGTH);
    }

    /**
     * @param string $name
     * @param string $label
     * @param null $default
     */
    public function __construct($name, $label, $default) {
        parent::__construct($name, $label, $default);
        $this->specification = [
            'type'=>DB::DECIMAL,
            'length'=>static::TOTAL_LENGTH.','.static::DEC_LENGTH
        ];
    }

    /**
     * @param int $depth
     * @return float
     */
    public function sleep($depth = 0) {
        return static::toFixed($this->value);
    }

    /**
     * @param int $depth
     * @return float
     */
    public function cleanSleep($depth = 0) {
        return static::toFixed($this->value);
    }

    /**
     * @param $value
     * @return int|void
     */
    public static function validate($value) {
        if ($value === null) {
            $value = 0;
        }
        else {
            $value = static::toFixed($value);
        }

        /*if(!preg_match('/[0-9]{0,'.static::INT_LENGTH.'}\.[0-9]{0,'.static::DEC_LENGTH.'}/', strval($value))) {
            throw new \Exception("Invalid value for fixed(".static::INT_LENGTH.",".static::DEC_LENGTH.").");
        }*/

        return $value;
    }

    /**
     * @param $value
     * @return int
     */
    public static function validateSleep($value) {
        return static::validate($value);
    }

    /**
     * Whether or not the Entry has a validate function
     * @return bool
     */
    public static function hasValidate()
    {
        return true;
    }
}