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

/**
 * Class Bit
 * Hopefully the final implementation of a Flag that works in all cases.  The Bit is intrinsically linked with a a Bits
 * Composite Field and the Bit never stores or updates its own value.  All operations are forwarded to the parent Bits.
 * @package eprocess360\v3core\Keydict\Entry
 */
class Bit extends Composite
{

    /** @var  BitString */
    protected $bitString = false;
    protected $bitPosition;
    protected $composite = null;
    protected $bits;
    const TYPE = Field::TYPE_ENTRY + Field::TYPE_FLAG + Field::MUST_VALIDATE;
    protected $position;

    /**
     * Bits are not allowed to have a specification
     * @param int $position
     * @param string $name
     * @param string $label
     */
    public function __construct($position, $name, $label)
    {
        parent::__construct($name, $label, false);
        $this->position = $position;
        $this->specification = [];
    }

    /**
     * Standard build function
     * @param int $position
     * @param string$name
     * @param string|null $label
     * @return Bit
     */
    public static function build($position, $name, $label = null)
    {
        return new static($position, $name, $label);
    }
    /**
     * Set the value of the Bit
     * @param $value
     * @return Bit
     * @throws Keydict\Exception\KeydictException
     */
    public function set($value)
    {
        $this->getBits()->setBit($this->position, $value);
        return $this;
    }

    /**
     * Get the value of the Bit
     */
    public function get()
    {
        return $this->getBits()->getBit($this->position);
    }

    /**
     * You cannot sleep a Bit
     * @param int $depth
     * @return int|string|void
     * @throws Keydict\Exception\KeydictException
     */
    public function sleep($depth = 0)
    {
        throw new Keydict\Exception\KeydictException("You cannot sleep() an individual bit.");
    }

    /**
     * WHERE operator for the keydict query builder
     * @param $operator
     * @param $value
     * @return string
     * @throws \Exception
     */
    public function where($operator, $value)
    {
        $value = (bool)$value;
        $name = $this->getBits()->getName();
        $length = $this->getBits()->specification()['length'];
        $pos = $this->getPosition();
        $iter = 0;
        while ($pos>$length) {
            $pos -= $length;
            $iter += $length;
        }
        $name .= '_'.$iter;
        switch ($operator) {
            case '=':
                $openingBracket = '(';
                $operator = '&';
                $endingBracket = $value?')':') = 0';
                $value = $pos+1;
                break;
            default:
                throw new \Exception("Bits doesn't support the operator for '{$operator}'.");
        }
        return "{$openingBracket}{$this->getBits()->getParent()->getName()}.{$name} {$operator} {$value}{$endingBracket}";
    }

    /**
     * This will convert potential values into a boolean
     * @param $value
     * @return bool
     */
    public static function validate($value)
    {
        return (bool)$value;
    }

    /**
     * Bits is the Bit controller for this object
     * @param Bits $bits
     * @return Bit
     */
    public function setBits($bits)
    {
        $this->bits = $bits;
        $bits->getBitMap()->register($this);
        return $this;
    }

    /**
     * @return Bits
     */
    public function getBits()
    {
        return $this->bits;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return Composite
     */
    public function getComposite()
    {
        return $this->bits;
    }

    /**
     * @return int
     */
    public function getInteger()
    {
        return 1 << $this->position;
    }

}