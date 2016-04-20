<?php
/**
 * Created by PhpStorm.
 * User: kira
 * Date: 8/5/2015
 * Time: 10:58 AM
 */

namespace eprocess360\v3core\Keydict;



use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Exception\InvalidValueException;

/**
 * Class Entry
 *
 * The basic functions that make Keydict Entries work.  Keydict Entries are containers for data that have their own
 * validation, sleep, wakeup and value methods.  Keydict Entries never specify SQL statements to store data in the
 * database, however they are responsible for making sure the data stored is available as a string.
 *
 * Entries can essentially be arrays.  When using an Entry to store an array, the sleep and wakeup methods must be
 * defined to handle the array serialization and unserialization.  Extend the ArrayEntry class when going this route.
 *
 * @package eprocess360\v3core\Keydict
 */
abstract class Entry extends Field
{
    const TYPE = Field::TYPE_ENTRY;
    protected $default;
    protected $specification;
    protected $value;
    protected $changed = false;
    protected $attempted_value;
    protected $form_type = 'text';
    protected $joins = [];
    protected $isRequired = false;

    /**
     * Create a new Entry with the following storage name and display label, along with a potential default value
     * @param string $name Keydict key name
     * @param string $label User friendly label
     * @param $default
     */
    public function __construct($name, $label, $default = null)
    {
        $this->setName($name);
        $this->label = $label;
        $this->default = $default;
        $this->specification = [
            'type'=>null,
            'length'=>null
        ];
        if ($default !== null) {
            $this->init($default);
        }
    }

    public function getFormType()
    {
        return $this->form_type;
    }

    /**
     * Prepare the Entry for containing data and set the default
     * @param $default
     */
    public function init($default)
    {
        $this->wakeup($default);
    }

    /**
     * Standard build function
     * @param $name
     * @param $label
     * @param null $default
     * @return Entry
     */
    public static function build($name, $label, $default = null)
    {
        return new static($name, $label, $default);
    }

    public static function column($name, $label, $default = null)
    {
        return new static($name, $label, $default);
    }

    /**
     * Whether or not the Entry has been changed relative to the default value, or successive changes that were then saved
     * @return bool
     */
    public function hasChanged()
    {
        return $this->changed;
    }

    /**
     * Set the Entry value and mark as changed. Setting the Entry will cause the new value to be validated.
     * @param $value
     * @return $this
     * @throws InvalidValueException
     */
    public function set($value)
    {
        $this->setAttemptedValue($value);
        if (static::hasValidate()) {
            $value = static::validate($value);
        }
        if ($this->hasMeta('required') && !$value) {
            throw new InvalidValueException("Value for {$this->getLabel()} is required.");
        }
        $this->value = $value;
        return $this;
    }



    /**
     * Get the automatic POST parameter name
     * @return string
     */
    public function getFormName()
    {
        return str_replace('_','-',"form-{$this->getName(-1)}");
    }

    /**
     * Get the value of the Entry.  This value would be used for display out and never for database storage.  Database
     * storage is always through sleep().
     * @return mixed
     */
    public function get()
    {
        return $this->value;
    }

    public function getValue()
    {
        return $this->get();
    }

    /**
     * Get the value of the Entry of return the default in the case it's empty
     * @return mixed
     */
    public function getOrDefault()
    {
        return $this->value ? : $this->default;
    }

    /**
     * Get the SQL specification of the Entry.  The SQL specification contains the raw parameters required when setting
     * up columns in a database.
     * @param int $depth
     * @return array
     */
    public function getSpecification($depth = 0)
    {
        return $this->specification;
    }

    /**
     * @return string
     */
    public function getFullColumnName()
    {
        return "`{$this->getParent()->getName()}`.`{$this->getName()}`";
    }

    /**
     * Validate the supplied data against the Entry.  Always a static method because the validate function must be
     * usable even when not in the context of a Project/Controller.
     * @param $value
     * @return mixed
     */
    public static function validate($value)
    {
        return $value;
    }

    public static function validateSleep($value)
    {
        return $value;
    }

    /**
     * Whether or not the Entry has a validate function
     * @return bool
     */
    public static function hasValidate()
    {
        if (static::TYPE & Field::MUST_VALIDATE) return true; // this is the preferred way to check
        return false;
    }

    /**
     * Return the Entry value in a form that can be inserted into the database.  Should always return a string.
     * @param int $depth
     * @return string
     */
    public function sleep($depth = 0)
    {
        return (string)$this->value;
    }

    /**
     * Return the Entry value in a form that can be inserted into the database.  Should always return a string.
     * @param int $depth
     * @return string
     */
    public function cleanSleep($depth = 0)
    {
        return $depth > 1 ? $this->getCleanValue() : "'{$this->getCleanValue()}'";
    }

    /**
     * Return the Entry value after accepting the raw data from the database.  Should always accept a string.
     * @param string $value
     * @return Entry
     */
    public function wakeup($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Stored the attempted set value during a POST event
     * @param string $value
     */
    public function setAttemptedValue($value)
    {
        $this->attempted_value = filter_var($value, FILTER_SANITIZE_STRING);
    }

    /**
     * Get the attempted value that was previously set during a POST event
     * @return string
     */
    public function getAttemptedValue()
    {
        return $this->attempted_value;
    }

    /**
     * @param string $form_type
     */
    public function setFormType($form_type)
    {
        $this->form_type = $form_type;
    }

    /**
     * @return boolean
     */
    public function isRequired()
    {
        return $this->getMeta('required');
    }

    /**
     * When Accepting a POST, this field will be required or it will throw an Exception
     * @param boolean $isRequired
     * @return Entry|Field
     */
    public function setRequired($isRequired = true)
    {
        $isRequired ? $this->setMeta('required') : $this->unsetMeta('required') ;
        return $this;
    }

}