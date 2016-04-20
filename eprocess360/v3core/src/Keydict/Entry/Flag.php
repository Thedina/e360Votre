<?php
/**
 * Created by PhpStorm.
 * User: kira
 * Date: 8/5/2015
 * Time: 11:30 AM
 */

namespace eprocess360\v3core\Keydict\Entry;


use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Composite;
use eprocess360\v3core\Keydict\Entry;
use eprocess360\v3core\Keydict\Entry\Bits;
use eprocess360\v3core\Keydict\Field;
use eprocess360\v3core\Keydict\InterfaceEntry;
use eprocess360\v3core\DB;

/**
 * Class Flag
 * @package eprocess360\v3core\Keydict\Entry
 */
class Flag extends Entry
{

    /** @var  BitString */
    protected $bitString = false;
    protected $bitPosition;
    protected $composite = null;
    const TYPE = Field::TYPE_ENTRY + Field::TYPE_FLAG;

    public function __construct($name, $label, $default)
    {
        parent::__construct($name, $label, $default);
        $this->value = $default;
        $this->specification = [
            'type'=>DB::TINYINT,
            'length'=>1,
            'unsigned'=>true
        ];
    }

    /**
     * @param $value
     * @return Flag
     * @throws Keydict\Exception\InvalidValueException
     * @internal param bool|false $set_changed
     */
    public function set($value)
    {
        $value = $this->value = (bool)$value;
        parent::set($value);
        if ($this->bitString) {
                $this->bitString->setBit($this->bitPosition, $this->value);
        }
        else if ($this->hasComposite() && $this->getComposite()->getClass() == 'DynamicFlags') {
            $this->getComposite()->setBit($this->bitPosition, $value);
        }
        return $this;
    }

    public function get()
    {
        if ($this->bitString) {
            return  $this->bitString->getBit($this->bitPosition);
        }

        return (bool)$this->value;

    }

    public function sleep($depth = 0)
    {
        return (int)$this->get();
    }

    public function getCleanValue()
    {
        return (int)$this->get();
    }

    public static function validate($value)
    {
        return (bool)$value;
    }

    public static function hasValidate()
    {
        return true;
    }

    /**
     * For binding to an external BitString
     * @param BitString $bitString
     * @param $position
     */
    public function bindBitString(BitString $bitString, $position) {
        $this->bitString = $bitString;
        $this->bitPosition = $position;
        $this->value = $this->bitString->getBit($this->bitPosition);
    }

    /**
     * Mostly for use with DynamicFlags
     * @param mixed $bitPosition
     * @return Flag
     */
    public function setBitPosition($bitPosition)
    {
        $this->bitPosition = $bitPosition;
        /** @var Bits $parent */
        $parent = $this->getParent();
        if ($this->hasComposite() && $this->getComposite()->getClass() == 'DynamicFlags') {
            $this->value = $this->getComposite()->getBit($this->bitPosition);
        }
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBitPosition()
    {
        return $this->bitPosition;
    }

    /**
     * @return Composite
     */
    public function getComposite()
    {
        return $this->composite;
    }

    /**
     * @param Composite $composite
     * @return Flag
     */
    public function setComposite(Composite $composite)
    {
        $this->composite = $composite;
        return $this;
    }

    private function hasComposite()
    {
        return is_object($this->composite);
    }
}