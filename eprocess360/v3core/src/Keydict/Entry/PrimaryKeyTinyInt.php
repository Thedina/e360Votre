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
 * Class PrimaryKeyTinyInt
 * @package eprocess360\v3core\Keydict\Entry
 */
class PrimaryKeyTinyInt extends Integer
{
    /**
     * @param string $name
     * @param string $label
     * @param null $default
     */
    public function __construct($name, $label, $default = null)
    {
        parent::__construct($name, $label, $default);
        $this->specification = [
            'type'=>DB::TINYINT,
            'length'=>3,
            'primary_key'=>true,
            'unsigned'=>true,
            'auto_increment'=>true
        ];
    }

    /**
     * @param $value
     * @return int|mixed
     * @throws \Exception
     */
    public static function validate($value)
    {
        return parent::validate($value);
    }

}