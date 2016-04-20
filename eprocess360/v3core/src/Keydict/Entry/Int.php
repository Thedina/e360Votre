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
//TODO Change the name of Int, not allowed in php 7
class Int extends Entry
{
    /**
     * WHERE operator for the keydict query builder
     * @param $operator
     * @param $value
     * @return string
     * @throws InvalidValueException
     */
    public function where($operator, $value)
    {
        $oldValue = $this->get();
        $this->set($value);
        $value = $this->cleanSleep();
        $this->set($oldValue);
        return "{$this->getParent()->getName()}.{$this->getName()} {$operator} {$value}";
    }

    public function sleep($depth = 0)
    {
        return (int)$this->value;
    }

    public function cleanSleep($depth = 0)
    {
        return (int)$this->value;
    }
}