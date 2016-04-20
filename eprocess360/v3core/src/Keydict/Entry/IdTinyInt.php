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
 * Class IdInteger
 * For use for specifying a remote row id.
 * @package eprocess360\v3core\Keydict\Entry
 */
class IdTinyInt extends Integer
{
    public function __construct($name, $label, $default = null)
    {
        parent::__construct($name, $label, $default);
        $this->specification = [
            'type'=>DB::TINYINT,
            'length'=>3,
            'unsigned'=>true
        ];
    }

    public static function validate($value)
    {
        return parent::validate($value);
    }

}