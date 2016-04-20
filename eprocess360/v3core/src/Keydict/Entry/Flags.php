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
use eprocess360\v3core\Keydict\Field;
use eprocess360\v3core\DB;

class Flags extends Composite
{
    const TYPE = Field::TYPE_CONTAINER + Field::TYPE_ENTRY + Field::TYPE_FLAGS;

    /**
     * @param string $name
     * @param string $label
     * @param Keydict $default
     * @throws Keydict\Exception\KeydictException
     */
    public function __construct($name, $label, $default = null)
    {
        parent::__construct($name, $label);
        if (!$default instanceof Keydict) {
            throw new Keydict\Exception\KeydictException("When creating Flags, default must be an instance of Keydict.");
        }
        $this->keydict = $default;
        $this->keydict->setParent($this);
    }

    public static function make($name, Flag ...$flags)
    {
        $keydict = Keydict::build(...$flags)->setStorageStrategy(new Keydict\StorageStrategy\MultiColumn(true));
        $keydict->setIsCompositeContainer();
        $keydict->setSpecification([
            'type'=>sizeof($flags) > 32 ? DB::BIGINT : DB::INT,
            'length'=>sizeof($flags) > 32 ? 20 : 10,
            'unsigned'=>true
        ]);
        $flags = new static($name, null, $keydict);
        $flags->setSpecification([
            'type'=>sizeof($flags) > 32 ? DB::BIGINT : DB::INT,
            'length'=>sizeof($flags) > 32 ? 20 : 10,
            'unsigned'=>true
        ]);
        return $flags;
    }

    public static function build($name, $label, $default = null)
    {
        return null;
    }

    public function sleep($depth = 0)
    {
        $value = 0;
        $i = 1;
        /** @var Entry $field */
        foreach ($this->getKeydict()->getFields() as $field) {
            if ((bool)$field->get()) {
                $value += $i;
            }
            $i = $i << 1;
        }
        // must return in MultiColumn format
        return [
            $this->getName($depth) => $value
        ];
    }

    /**
     * @param string $raw
     * @param int $depth
     * @return Flags
     * @throws Keydict\Exception\InvalidValueException
     */
    public function wakeup($raw, $depth = 0)
    {
        if (is_array($raw)) {
            $raw = array_shift($raw);
        }
        $raw = intval($raw);
        $i = 1;
        /** @var Entry $field */
        foreach ($this->getKeydict()->getFields() as $field) {
            $field->set($raw & $i);
            $i = $i << 1;
        }
        return $this;
    }

    public function getSpecification()
    {
        return $this->specification;
    }

    public static function components()
    {
        throw new \Exception("Cannot statically get Components of Flags since they are dynamically declared.");
    }

    public static function hasValidate()
    {
        return false;
    }


}