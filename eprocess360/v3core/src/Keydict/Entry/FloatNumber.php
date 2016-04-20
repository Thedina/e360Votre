<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 2/2/16
 * Time: 9:15 AM
 */

namespace eprocess360\v3core\Keydict\Entry;


use eprocess360\v3core\DB;
use eprocess360\v3core\Keydict\Entry;

/**
 * Class FloatNumber
 * @package eprocess360\v3core\Keydict\Entry
 */
class FloatNumber extends Entry
{
    //TODO Improve on FloatNumber Entry (not called Float because of php 7 conflict)
    /**
     * Create a new Entry with the following storage name and display label, along with a potential default value
     * @param string $name Keydict key name
     * @param string $label User friendly label
     * @param $default
     */
    public function __construct($name, $label, $default = null)
    {
        parent::__construct($name, $label, $default);
        $this->specification = [
            'type'=>DB::FLOAT,
            'length'=>10
        ];
    }

    /**
     * Validate the supplied data against the Entry.  Always a static method because the validate function must be
     * usable even when not in the context of a Project/Controller.
     * @param $value
     * @return mixed
     */
    public static function validate($value)
    {
        if ($value===null) $value = (float)0;
        else
            self::toFloat($value);
        return $value;
    }

    /**
     * Whether or not the Entry has a validate function
     * @return bool
     */
    public static function hasValidate()
    {
        return true;
    }

    /**
     * @param $value
     * @return float
     */
    public static function validateSleep($value)
    {
        if ($value===null) $value = (float)0;
        else
            self::toFloat($value);
        return $value;
    }

    /**
     * Return the Entry value in a form that can be inserted into the database.  Should always return a string.
     * @param int $depth
     * @return string
     */
    public function sleep($depth = 0)
    {
        return self::toFloat($this->value);
    }

    /**
     * Return the Entry value in a form that can be inserted into the database.  Should always return a string.
     * @param int $depth
     * @return string
     */
    public function cleanSleep($depth = 0)
    {
        return self::toFloat($this->value);
    }

    /**
     * @param $num
     * @return float
     */
    private static function toFloat($num) {
//        $dotPos = strrpos($num, '.');
//        $commaPos = strrpos($num, ',');
//        $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos :
//            ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);
//
//        if (!$sep) {
//            return floatval(preg_replace("/[^0-9]/", "", $num));
//        }
//
//        return floatval(
//            preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' .
//            preg_replace("/[^0-9]/", "", substr($num, $sep+1, strlen($num)))
//        );
        //TODO Function doesn't work for negative, so currently using cast that does
        return (float)$num;
    }
}