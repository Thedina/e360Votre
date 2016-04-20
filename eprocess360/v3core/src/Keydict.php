<?php
/**
 * Created by PhpStorm.
 * User: kira
 * Date: 8/5/2015
 * Time: 10:55 AM
 */

namespace eprocess360\v3core;



use eprocess360\v3core\Keydict\Field;
use eprocess360\v3core\Keydict\StorageStrategy\MultiColumn;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Keydict\Entry;

/**
 * @property bool composite_container
 */
class Keydict extends Field
{
    const DELIMITER = '_';
    const TYPE = Field::TYPE_CONTAINER + Field::TYPE_KEYDICT;
    protected $lateValidators = [];


    /**
     * @param Keydict\StorageStrategy\InterfaceStorageStrategy|null $storage_strategy
     */
    public function __construct(Keydict\StorageStrategy\InterfaceStorageStrategy $storage_strategy = null)
    {
        if (!$storage_strategy) {
            $storage_strategy = new Keydict\StorageStrategy\MultiColumn();
        }
        $this->storage_strategy = $storage_strategy;
        $this->position = 0;
    }


    /**
     * Returns a Keydict with the given Fields.  Only the Keydict has a pattern that accepts additional Fields.
     * @param Field|Keydict\Field[] $fields
     * @return Keydict|Table
     */
    public static function build(Field ...$fields)
    {
        $keydict = new static();
        /** @var Field $field */
        foreach ($fields as $field) {
            $keydict->add($field);
            $field->eventAddedToParent();
        }
        return $keydict;
    }

    /**
     * @return Keydict
     */
    public function getChildren()
    {
        return $this->getField('children');
    }

    /**
     * @return int
     */
    public function count()
    {
        return sizeof($this->fields);
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        if ($this->__isset('children')) {
            return (bool)$this->getChildren()->count();
        }
        return false;
    }

    public function setIsCompositeContainer()
    {
        $this->composite_container = true;
    }

    /**
     * Outputs the Keydict in a format that can be stored in the database and revived later
     * @param int $depth
     * @return array|string
     */
    public function sleep($depth = 0)
    {
        return $this->storage_strategy->sleep($this, $depth);
    }

    /**
     * Save the first level keydict to a flat php constants file
     * Only the Entry with meta 'constantName' will be saved
     * @param $destination
     * @throws \Exception
     */
    public function configSleep($destination)
    {
        $buffer = "<?php\r\n";
        foreach ($this->allFields(self::TYPE_ENTRY, true) as $entry) {
            if ($entry->hasMeta('constantName')) {
                $buffer .= "{$entry->getMeta('constantName')} = {$entry->cleanSleep()};\r\n";
            }
        }
        if (!file_exists($destination)) {
            file_put_contents($destination, $buffer);
            return;
        }
        throw new \Exception("configSleep cannot write to a file that already exists.");
    }

    /**
     * Outputs the Keydict in a format that is database-formatted for storage
     * @param int $depth
     * @return array
     */
    public function cleanSleep($depth = 0)
    {
        return $this->storage_strategy->cleanSleep($this, $depth);
    }

    /**
     * Populate the Entry and Keydict in this Keydict via a stored value.  Should always restore the state of the
     * Keydict to how it was before sleep.  Wakeup does not do any validation of the data so it should never be used
     * to accept user input.
     * @param $value
     * @return $this
     */
    public function wakeup($value)
    {
        $this->storage_strategy->wakeup($this, $value);
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function acceptArray($value)
    {
        $this->storage_strategy->acceptArray($this, $value);
        return $this;
    }

    /**
     * See if the provided array of keys can be completely found in the Keydict
     * @param string[] $keys
     * @return bool
     * @throws \Exception
     */
    public function hasKeys($keys)
    {
        if (!is_array($keys)) {
            throw new \Exception("hasKeys requires the parameter to be an array.");
        }
        foreach ($this->allFields() as $entry) {
            foreach ($keys as $k=>$key) {
                if ($key == $entry->getName()) {
                    unset($keys[$k]);
                    break;
                }
            }
        }
        if (sizeof($keys)==0) return true;
        return false;
    }


    /**
     * Remove a Field
     * @param Field $field
     * @return $this
     * @throws \Exception
     */
    public function remove(Field $field)
    {
        $field->setParent(null);
        if ($field == $this->fields[$field->getName()]) {
            unset($this->fields[$field->getName()]);
            return $this;
        }
        throw new \Exception("Cannot remove {$field->getName()} as it does not exist in Field '{$this->getName()}'.");
    }

    /**
     * Returns the Specification of the Field or Fields.  Needs to be refactored to differentiate between the two.
     * @param int $depth
     * @return array
     */
    public function getSpecification($depth = 0)
    {
        $strategy = $this->getStorageStrategy();
        if ($strategy instanceof MultiColumn) {
            $result = $this->getStorageStrategy()->getSpecification($this, $depth);
            return $result;
        }
        $length = 2;
        $result = $this->setStorageStrategy(new MultiColumn())->getStorageStrategy()->getSpecification($this, -1);
        $this->setStorageStrategy($strategy);


        foreach ($result as $name => $field) {
            $length += 5 + strlen($name) + $field['length'];
        }
        return [
            $this->getName($depth) => $this->specification
        ];


    }

    public function bindDataSet(DataSet $dataset)
    {
        $this->dataset = $dataset;
    }

    /**
     * Set status values *from $keydict* into $array as 'status' sub-array with
     * correct names, replacing status_0
     * @param Keydict $keydict
     * @param $array
     */
    public static function translateStatus(Keydict $keydict, &$array)
    {
        unset($array['status_0']);
        /** @var Entry $field */
        foreach ($keydict->status->getFields() as $field) {
            $array['status'][$field->getName()] = $field->get();
        }
    }

    /**
     * Set status values *from $keydict* into $array as 'flags' sub-array with
     * correct names, replacing flags_0
     * @param Keydict $keydict
     * @param $array
     */
    public static function translateFlags(Keydict $keydict, &$array)
    {
        global $pool;
        unset($array['flags_0']);
        /** @var Entry $field */
        foreach ($keydict->flags->getFields() as $field) {
            $array['flags'][$field->getName()] = $field->get();
        }
    }

    /**
     * Translate *actual value of flags_0* into 'flags' array and set into $array
     * @param Keydict $keydict
     * @param $array
     */
    public static function wakeupAndTranslateFlags(Keydict $keydict, &$array)
    {
        $flags = $keydict->flags;
        $flags->wakeup([$array['flags_0']]);

        if(!isset($array['flags']))
            $array['flags'] = [];

        /** @var Entry $field */
        foreach ($flags->getFields() as $field) {
            $array['flags'][$field->getName()] = $field->get();
        }
        unset($array['flags_0']);
    }

    /**
     * Translate *actual value of status_0* into 'status' array and set into $array
     * @param Keydict $keydict
     * @param $array
     */
    public static function wakeupAndTranslateStatus(Keydict $keydict, &$array)
    {
        $flags = $keydict->status;
        $flags->wakeup([$array['status_0']]);

        if(!isset($array['status']))
            $array['status'] = [];

        /** @var Entry $field */
        foreach ($flags->getFields() as $field) {
            $array['status'][$field->getName()] = $field->get();
        }
        unset($array['status_0']);
    }
}