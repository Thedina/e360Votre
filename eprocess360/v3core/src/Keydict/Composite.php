<?php
/**
 * Created by PhpStorm.
 * User: kira
 * Date: 8/5/2015
 * Time: 10:58 AM
 */

namespace eprocess360\v3core\Keydict;



use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Exception\KeydictException;
use eprocess360\v3core\DB;

class Composite extends Entry
{
    /** @var Keydict */
    protected $keydict;

    /**
     * Composite getFields must pass-through to its Keydict
     * @return Field[]
     */
    public function getFields()
    {
        return $this->keydict->getFields();
    }

    /**
     * Composite __get must pass-through to its Keydict
     * @param $key
     * @return Field
     */
    public function __get($key)
    {
        return $this->keydict->__get($key);
    }

    /**
     * Composite getField must pass-through to its Keydict
     * @param $key
     * @return Field
     */
    public function getField($key)
    {
        return $this->keydict->getField($key);
    }

    /**
     * Get the Composite Keydict
     * @return Keydict
     */
    public function getKeydict()
    {
        return $this->keydict;
    }

    /**
     * Composite set will take an array and try to set the values on each Field matching the keys in the array.  If an
     * array is passed to the set that is missing one of the key names in the local Keydict then an Exception will be
     * thrown.
     * @param $value
     * @return StorageStrategy\InterfaceStorageStrategy
     * @throws Exception\InvalidValueException
     * @throws KeydictException
     * @internal param bool $set_changed
     */
    public function set($value)
    {
        /** @var Entry $field */
        foreach ($this->getFields() as $field) {
            if (array_key_exists($field->getRawName(), $value)) {
                $field->set($value[$field->getRawName()]);
            } else {
                throw new KeydictException("When specifying set() on a Composite Entry, all local keys must be present in their raw form.");
            }
        }
    }

    /**
     * Composite getStorageStrategy must pass-through to its Keydict
     * @return StorageStrategy\InterfaceStorageStrategy
     */
    public function getStorageStrategy()
    {
        return $this->keydict->getStorageStrategy();
    }

    /**
     * Composite sleep must pass-through to its Keydict
     * @param int $depth
     * @return string|void
     */
    public function sleep($depth = 0)
    {
        return $this->keydict->setParent($this->getParent())->setName($this->name)->sleep($depth);
    }

    /**
     * Composite wakeup must pass-through to its Keydict
     * @param string $raw
     * @return Entry|Field|Composite
     * @throws KeydictException
     */
    public function wakeup($raw)
    {
        /** TODO this is questionable */
        if($this->keydict === NULL) {
            $this->value = $raw;
        }
        else {
            $this->value = $this->keydict->setName($this->name)->wakeup($raw);
        }
        return $this;
    }

    public function getSpecification($depth = 0)
    {
        return $this->keydict->setParent($this->getParent())->setName($this->name)->getSpecification($depth);
    }
}