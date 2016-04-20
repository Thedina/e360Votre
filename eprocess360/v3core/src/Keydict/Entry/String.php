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

class String extends Entry
{
    public function __construct($name, $label, $default = null)
    {
        parent::__construct($name, $label, $default);
        $this->specification = [
            'type'=>DB::VARCHAR,
            'length'=>128
        ];
    }

    public static function validate($value)
    {
        $cleanvalue = filter_var($value, FILTER_UNSAFE_RAW);
        if (strlen($value)==strlen($cleanvalue)) {
            return (string)trim($value);
        }
        throw new InvalidValueException("Invalid string.");
    }

    public static function hasValidate()
    {
        return true;
    }

    /**
     * @param int $depth
     * @return string|void
     */
    public function cleanSleep($depth = 0)
    {
        $value = DB::cleanse($this->sleep($depth));
        return "'{$value}'";
    }

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
}