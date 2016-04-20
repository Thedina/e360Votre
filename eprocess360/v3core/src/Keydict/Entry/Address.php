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
use eprocess360\v3core\Keydict\Exception\InvalidValueException;
use eprocess360\v3core\Keydict\Field;
use eprocess360\v3core\Keydict\StorageStrategy\MultiColumn;
use eprocess360\v3core\DB;

class Address extends Composite
{
    const TYPE = Field::TYPE_CONTAINER + Field::TYPE_ENTRY;

    public function __construct($name, $label, $default)
    {
        parent::__construct($name, $label, $default);
        $this->keydict = static::components();
        $this->specification = [
            'type'=>DB::VARCHAR,
            'length'=>680
        ];

    }

    public static function components()
    {
        $keydict = new Keydict(new MultiColumn());
        $keydict->setIsCompositeContainer();
        $keydict->add(String::build('line1', 'Address'));
        $keydict->add(String::build('line2', 'Address Line 2'));
        $keydict->add(String::build('city', 'City'));
        $keydict->add(String::build('state', 'State'));
        $keydict->add(String::build('zip', 'Zip'));
        return $keydict;
    }

    /**
     * Overrides inherited function, because composite function needs to be required.
     * @param bool $isRequired
     * @return Keydict\Entry|Field|void
     */
    public function setRequired($isRequired = true)
    {
        // sets Address as required

        $this->keydict->getField('line1')->setRequired();
        $this->keydict->getField('city')->setRequired();
        $this->keydict->getField('state')->setRequired();
        $this->keydict->getField('zip')->setRequired();
        return parent::setRequired();
    }

    public static function validate($value)
    {
        if ($value instanceof static) {
            $value = $value->sleep(0);
        }
        $clean_value = parent::validate($value);
        if (!filter_var($clean_value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidValueException("E-mail address '{$clean_value}' is invalid.", 301);
        }
        return (string)$value;
    }

    public static function hasValidate()
    {
        return true;
    }

    public static function hasLateValidate()
    {
        return true;
    }
}