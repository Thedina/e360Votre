<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 8/19/2015
 * Time: 1:43 PM
 */

namespace eprocess360\v3core\Keydict\Entry;


use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Composite;
use eprocess360\v3core\Keydict\Entry\Bits\BitMap;
use eprocess360\v3core\Keydict\Exception\KeydictException;
use eprocess360\v3core\Keydict\Field;
use eprocess360\v3core\DB;
use eprocess360\v3core\Toolbox;

/**
 * Class Bits
 * A Keydict Composite Container object that stores Bit multiples of 64 into MySQL BIT format.  Already third generation
 * binary Dynamic Flag storage and retrieval Class and the product of many tears.  Still needs more work since the
 * BitMap integration is a little funny - but it works and the resultant data is how it should be in the long run.
 * @package eprocess360\v3core\Keydict\Entry
 */
class Bits extends Composite
{
    const TYPE = Field::TYPE_CONTAINER + Field::TYPE_ENTRY + Field::TYPE_FLAGS;
    protected $value = [];
    protected $bitMap;

    /**
     * @param string $name
     * @param string $label
     * @param Keydict $default
     * @throws KeydictException
     */
    public function __construct($name, $label, $default = null)
    {
        parent::__construct($name, $label);
        $this->keydict = $default;
        $this->value = chr(0);
        $this->bitMap = BitMap::build($this);
    }

    /**
     * This is the basic specification - In actuality it will be duplicates of this
     * @return array
     */
    public static function specification()
    {
        return [
            'type'=>DB::BIT,
            'length'=>64 // 64 flags
        ];
    }

    /**
     * @param $name
     * @param Bit $bit
     * @return Bits
     */
    public static function make($name, Bit ...$bit)
    {
        $keydict = Keydict::build(...$bit)->setStorageStrategy(new Keydict\StorageStrategy\MultiColumn(true));
        $keydict->setSpecification([]);

        $bits = new static($name, null, $keydict);
        /** @var Bit $b */
        foreach ($bit as $b) {
            $b->setBits($bits);
        }
        $keydict->setParent($bits);
        return $bits;
    }

    public static function column($name, $label, $default = null)
    {
        return static::make($name)->setLabel($label);
    }

    /**
     * @param int $depth
     * @return string
     */
    public function getName($depth = 0)
    {
        return parent::getName($depth - 1 > 0 ? : 0);
    }

    /**
     * Add a Bit
     * @param Bit $bit
     * @return Bits
     */
    public function addBit(Bit ...$bit)
    {
        foreach ($bit as $b) {
            /** @var Bit $b */
            $this->getKeydict()->add($b);
            $b->setBits($this);
        }
        return $this;
    }

    /**
     * Import a configuration saved in the BitMap format
     * @param $json
     * @return Bits
     */
    public function importBitMapJson($json)
    {
        $this->bitMap->wakeup($json);
        return $this;
    }

    /**
     * Special sleep function
     * @param int $depth
     * @return string
     */
    public function sleep($depth = 0)
    {
        $out = [];
        while ($this->getBitMap()->getBitLength()/8 > strlen($this->value)-1) {
            $this->value .= chr(0);
        }
        for ($i=0; $i<=$this->getBitMap()->getBitLength(); $i=$i+64) {
            $out[$this->getName($depth).'_'.$i] = substr($this->value,$i/8,8); // Toolbox::base256ToBase10(substr($this->value,$i/8,8));
        }
        return $out;
    }

    /**
     * Return the Entry value in a form that can be inserted into the database.  Should always return a string.
     * @param int $depth
     * @return string
     */
    public function cleanSleep($depth = 0)
    {
        $out = [];
        while ($this->getBitMap()->getBitLength()/8 > strlen($this->value)-1) {
            $this->value .= chr(0);
        }
        for ($i=0; $i<=$this->getBitMap()->getBitLength(); $i=$i+64) {
            $out[$this->getName($depth).'_'.$i] = (int)Toolbox::base256ToBase10(substr($this->value,$i/8,8));
        }
        return $out;
    }

    /**
     * @param int $depth
     * @return array|void
     */
    public function getSpecification($depth = 0)
    {
        $out = [];
        for ($i=0; $i<=$this->getBitMap()->getBitLength(); $i=$i+64) {
            $out[$this->getName($depth-1).'_'.$i] = $this->specification();
        }
        return $out;
    }

    /**
     * @param array $value
     * @return Bits
     */
    public function wakeup($value)
    {
        $this->value = null;
        foreach ($value as $v) {
            while (strlen($v) < 8) {
                $v .= chr(0);
            }
            $this->value .= chr((int)$v);
        }
        return $this;
    }

    /**
     * Sets the specified bit
     * @param $position
     * @param $value
     */
    public function setBit($position, $value)
    {
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

        /*if ($value && $current_value ^ (1 << $position)) {
            $current_value += 1 << $position;
        } elseif (($current_value & (1 << $position))) {
            $current_value -= 1 << $position;
        }*/

        if($value) {
            $current_value = $current_value | (1 << $position);
        }
        else {
            $current_value = $current_value & ~(1 << $position);
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
     * Removes a Field from this object's Fields.
     * @param Field $field
     * @return Bits
     */
    public function remove(Field $field)
    {
        return $this->getKeydict()->remove($field);
    }

    /**
     * @return Flag[]
     */
    public function getFields()
    {
        return $this->getKeydict()->getFields();
    }

    /**
     * @return Flag[]
     */
    public function __isset($key)
    {
        return $this->getKeydict()->__isset($key);
    }

    /**
     * @param BitMap $bitMap
     * @return Bits
     */
    public function setBitMap($bitMap)
    {
        $this->bitMap = $bitMap;
        return $this;
    }

    /**
     * @return BitMap
     */
    public function getBitMap()
    {
        return $this->bitMap;
    }

}