<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 8/24/2015
 * Time: 7:09 PM
 */

namespace eprocess360\v3core\Keydict\Entry;


use eprocess360\v3core\DB;

/**
 * Class Bits8
 * @package eprocess360\v3core\Keydict\Entry
 */
class Bits8 extends Bits
{
    /**
     * This is the basic specification - In actuality it will be duplicates of this
     * @return array
     */
    public static function specification()
    {
        return [
            'type'=>DB::BIT,
            'length'=>8 // 8 flags
        ];
    }
}