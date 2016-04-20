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

class JSONArray256 extends Entry
{
    public function __construct($name, $label, $default = null)
    {
        parent::__construct($name, $label, $default);
        $this->specification = [
            'type'=>DB::VARCHAR,
            'length'=>256
        ];
    }

    public function wakeup($value)
    {
        $this->value = json_decode($value, true);
        //var_dump($this->value, json_decode($value, true));
        return $this;
    }

    public function sleep($depth = 0)
    {
        return json_encode($this->value);
    }

}