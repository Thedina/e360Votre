<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 7/8/2015
 * Time: 11:08 AM
 */

namespace eprocess360\v3core;


/**
 * Utility functions go here.  They should all be static methods.
 * Class Toolbox
 * @package eprocess360\v3core
 */
class Toolbox
{

    /**
     * Returns a randomized alphanumeric string with the specified length
     * @param int $length
     * @return string
     */
    public static function generateSalt($length = 16)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * For going from base256 (in the case of Entry\Bits characters) to base10 (Decimal)
     * @param $string
     * @return string
     */
    public static function base256ToBase10($string) {
        $result = "0";
        for ($i = strlen($string)-1; $i >= 0; $i--) {
            $result = bcadd($result,
                bcmul(ord($string[$i]), bcpow(256, $i)));
        }
        return $result;
    }

    /**
     * For the calculation of processing times throughout the software
     * @return float
     */
    public static function microtimeFloat()
    {
        list($uSec, $sec) = explode(" ", microtime());
        return ((float)$uSec + (float)$sec);
    }

    /**
     * @param $size
     * @return string
     */
    public static function convertBytes($size)
    {
        $unit=array('b','kb','mb','gb','tb','pb');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }
}