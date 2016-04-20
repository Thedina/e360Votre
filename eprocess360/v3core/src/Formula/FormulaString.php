<?php

/**
 * Created by PhpStorm.
 * User: kiranebilak
 * Date: 2/25/16
 * Time: 6:58 PM
 */
namespace eprocess360\v3core\Formula;

class FormulaString
{
    private $value;

    public function setRawValue($value)
    {
        $value = trim($value, $delimiter = substr($value,0,1)); // trim the quotes off
        $value = str_replace('\\'.$delimiter, $delimiter, $value);
        $this->setValue($value);
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
}