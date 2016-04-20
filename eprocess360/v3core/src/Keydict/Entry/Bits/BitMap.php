<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 8/22/2015
 * Time: 5:45 PM
 */

namespace eprocess360\v3core\Keydict\Entry\Bits;


use eprocess360\v3core\Keydict\Entry\Bit;
use eprocess360\v3core\Keydict\Entry\Bits;
use eprocess360\v3core\Keydict\Exception\KeydictException;

/**
 * Class BitMap
 * @package eprocess360\v3core\Keydict\Entry\Bits
 */
class BitMap
{
    private $bitLength = 0;
    private $bitMap = [];
    /** @var Bits */
    private $bits;

    /**
     * Accepts Bits
     * @param Bits $bits
     * @return BitMap
     */
    public static function build(Bits $bits)
    {
        $bitMap = new BitMap();
        $bitMap->setBits($bits);
        return $bitMap;
    }

    /**
     * Register a Bit in the Map
     * @param Bit $bit
     * @return $this
     * @throws KeydictException
     */
    public function register(Bit $bit)
    {
        if (!array_key_exists($bit->getPosition(), $this->bitMap)) {
            $this->bitMap[$bit->getPosition()] = $bit;
            if ($bit->getPosition() > $this->bitLength) {
                $this->bitLength = $bit->getPosition();
            }
            return $this;
        }
        throw new KeydictException("Bit position conflict on position {$bit->getPosition()}: {$bit->getName()}, owned by {$this->bitMap[$bit->getPosition()]->getName()}");
    }

    /**
     * Returns a serialization of the BitMap that includes position, name and label.  This doesn't store any User data,
     * just how to restore the configuration later.  Meta data is not saved.
     * @return array
     */
    public function sleep()
    {
        $out = [];
        /**
         * @var int $position
         * @var Bit $bit
         */
        foreach ($this->bitMap as $position=>$bit) {
            $out["{$position} {$bit->getRawName()}"] = $bit->getLabel();
        }
        return json_encode($out);
    }

    /**
     * Accepts a JSON string from a previous sleep and rebuilds the objects
     * @param $json
     * @return BitMap
     */
    public function wakeup($json)
    {
        $bit_array = json_decode($json, true);
        $bits = [];
        foreach ($bit_array as $key=>$label) {
            $delimiter = strpos($key,' ');
            $position = substr($key,0,$delimiter);
            $name = substr($key,$delimiter+1);
            $bits[] = Bit::build($position, $name, $label);
        }
        if (sizeof($bits)) {
            $this->bits->addBit(...$bits);
        }
        return $this;
    }

    /**
     * @param mixed $bits
     * @return BitMap
     */
    public function setBits($bits)
    {
        $this->bits = $bits;
        return $this;
    }

    /**
     * @return int
     */
    public function getBitLength()
    {
        return $this->bitLength;
    }
}