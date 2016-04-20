<?php
/**
 * Created by PhpStorm.
 * User: kira
 * Date: 8/5/2015
 * Time: 11:30 AM
 */

namespace eprocess360\v3core\Keydict\Entry;


use eprocess360\v3core\DB;

/**
 * Class FixedString256
 * @package eprocess360\v3core\Keydict\Entry
 */
class FixedString256 extends String
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
            'type'=>DB::CHAR,
            'length'=>255
        ];
    }
}