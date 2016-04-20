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
 * Class String64
 * @package eprocess360\v3core\Keydict\Entry
 */
class String64 extends Entry
{
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
            'type'=>DB::VARCHAR,
            'length'=>64
        ];
    }
}