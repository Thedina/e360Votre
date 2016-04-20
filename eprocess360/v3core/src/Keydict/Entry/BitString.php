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
 * Class BitString
 * For holding lots of undefined bits.  These bits are not readily searchable via SQL but they can be if you do it
 * one byte at a time.
 * @package eprocess360\v3core\Keydict\Entry
 */
class BitString extends Entry
{
    protected $value = '';

    public function __construct($name, $label, $default = null)
    {
        parent::__construct($name, $label, $default);
        $this->specification = [
            'type'=>DB::VARCHAR,
            'length'=>128
        ];
    }

    /**
     * @param string $value
     * @return BitString
     */
    public function wakeup($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Sets the specified bit
     * @param $position
     * @param $value
     */
    public function setBit($position, $value)
    {
        if ((bool)$this->getBit($position) === (bool) $value) return;
        $pointer = 0;
        while ($position > 7) {
            ++$pointer;
            $position -= 8;
        }
        if (strlen($this->value) >= $pointer) {
            $current_value = ord(substr($this->value, $pointer, 1));
        } else {
            $current_value = 0;
        }
        if ($value && $current_value ^ (1 << $position)) {
            $current_value += 1 << $position;
        } elseif (($current_value & (1 << $position))) {
            $current_value -= 1 << $position;
        }
        while ($pointer > strlen($this->value)-1) {
            $this->value .= chr(0);
        }
        $this->value = substr_replace($this->value, chr($current_value), $pointer, 1);
    }

    /**
     * Returns the value of the specified bit
     * @param $position
     * @return bool
     */
    public function getBit($position)
    {
        $pointer = 0;
        while ($position > 7) {
            ++$pointer;
            $position -= 8;
        }
        if ($pointer < strlen($this->value)) {
            $current_value = ord(substr($this->value, $pointer, 1));
        } else {
            $current_value = 0;
        }

        return (bool)($current_value & (1 << $position));
    }

    /**
     * Checks to see if the BitString has any options set
     * @return bool
     */
    public function isEmpty()
    {
        $value = $this->value;
        while(strlen($value)) {
            if (ord(substr($value,0,1))>0) {
                return false;
            }
            $value = substr($value,1);
        }
        return true;
    }

    /**
     * Returns an array with the position numbers of all the bits set to true|false
     * @param bool $test
     * @return array
     */
    public function getPositions($test = true)
    {
        $value = $this->value;
        $block = 0;
        $positions = [];
        while(strlen($value)) {
            $byte = ord(substr($value,0,1));
            for ($i=0;$i<8;$i++) {
                switch ($test) {
                    case true:
                        if ($byte & 1 << $i) {
                            $positions[] = $block * 8 + $i;
                        }
                        break;
                    default:
                        if ($byte ^ 1 << $i) {
                            $positions[] = $block * 8 + $i;
                        }
                }
            }
            ++$block;
            $value = substr($value,1);
        }
        return $positions;
    }

    /**
     * Special sleep function
     * @param int $depth
     * @return string
     */
    public function sleep($depth = 0)
    {
       return $this->value;
    }

    /**
     * Special sleep function
     * @param int $depth
     * @return string
     */
    public function cleanSleep($depth = 0)
    {
        $val = DB::cleanse($this->value);
        return "'{$val}'";
    }

}