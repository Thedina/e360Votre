<?php
/**
 * Created by PhpStorm.
 * User: kira
 * Date: 8/13/2015
 * Time: 11:34 AM
 */

namespace eprocess360\v3core\Keydict\StorageStrategy;


use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Entry;
use eprocess360\v3core\Keydict\Field;
use eprocess360\v3core\DB;

/**
 * Class SingleColumn
 * Designed to collapse down a Keydict into a single-column value.  Mostly used for CompositeEntry or Settings.  It's
 * not a good idea to use this strategy if the data needs to be searchable since it will be stored in JSON.
 * @package eprocess360\v3core\Keydict\StorageStrategy
 */
class SingleColumn implements InterfaceStorageStrategy
{
    /**
     * @param Keydict $keydict
     * @param null $depth
     * @return array
     * @throws \Exception
     */
    public function sleep(Keydict $keydict, $depth = null)
    {
        $out = array();
        $source = $keydict->allFields(Field::TYPE_ANY, true);
        /** @var Field $entry */
        foreach ($source as $entry) {
            $depth = 0;
            $results = $entry->sleep($depth + 1);
            if ($entry->getStorageStrategy() instanceof $this) {
                $name = $entry->getName($depth);
                $out[$name] = json_decode(array_shift($results),true);
            } elseif ($entry->getStorageStrategy() instanceof MultiColumn) {
                foreach ($results as $key=>$result) {
                    $out[$key] = $result;
                }
            } else {
                $out[$entry->getName($depth)] = $results;
            }

        }
        return [$keydict->getName($depth + 1) => json_encode($out)];
    }

    /**
     * @param Keydict $keydict
     * @param null $depth
     * @return array
     * @throws \Exception
     */
    public function cleanSleep(Keydict $keydict, $depth = null)
    {
        $out = array();
        $source = $keydict->allFields(Field::TYPE_ANY, true);
        /** @var Field $entry */
        foreach ($source as $entry) {
            $depth = 0;
            $results = $entry->cleanSleep($depth + 1);
            if ($entry->getStorageStrategy() instanceof $this) {
                $name = $entry->getName($depth);
                $out[$name] = json_decode(array_shift($results),true);
            } elseif ($entry->getStorageStrategy() instanceof MultiColumn) {
                foreach ($results as $key=>$result) {
                    $out[$key] = $result;
                }
            } else {
                $out[$entry->getName($depth)] = $results;
            }

        }
        return [$keydict->getName($depth + 1) => json_encode($out)];
    }

    public function getSpecification(Keydict $keydict, $depth = 0)
    {
        ++$depth;
        $length = 2;
        /** @var Entry $field */
        foreach ($keydict->allFields(Field::TYPE_ANY, true) as $field) {
            $spec = $field->getSpecification($depth);
            if ($field::TYPE & Field::TYPE_CONTAINER)
                $spec = array_shift($spec);
            $length += 5 + strlen($field->getName($depth)) + $spec['length'];
        }
        return [
            $keydict->getName($depth)=>
            ['type'=>DB::VARCHAR,
            'length'=>$length]
        ];
    }

    /**
     * @param Keydict $keydict
     * @param string $value JSON originally stored, the key is not necessary.
     * @param int $depth
     * @throws \Exception
     */
    public function wakeup(Keydict $keydict, $value, $depth = 0)
    {
        if (is_array($value) && array_keys($value) == [0]) $value = array_shift($value);
        $source = $keydict->allFields(Field::TYPE_ANY, true);
        if (!is_array($value))
        $value = json_decode($value, true);
        foreach ($source as $entry) {
            $key = $entry->getName($depth);
            if ($entry::TYPE & Field::TYPE_ENTRY && $entry::TYPE ^ Field::TYPE_COMPOSITE) {
                // Regular Entry
                if (isset($value[$key])) {
                    $entry->wakeup($value[$key]);
                }
            } elseif ($entry::TYPE & Field::TYPE_ENTRY && $entry::TYPE & Field::TYPE_COMPOSITE) {
                if (isset($value[$key])) {
                    $entry->wakeup($value[$key]);
                } else {
                    $out = [];
                    foreach ($value as $keyname=>$data) {
                        //echo "do {$keyname}=>{$data}\r\n";
                        //echo "trying ",substr($keyname,0,strlen($key)+1) ," == " , $key.Keydict::DELIMITER.PHP_EOL;
                        if (substr($keyname,0,strlen($key)+1) == $key.Keydict::DELIMITER) {
                            $out[substr($keyname,strlen($key)+1)] = $data;
                        }
                    }
                    if (sizeof($out)) {
                        //var_dump('!',$out);
                        $entry->wakeup($out);
                    }
                }
            } elseif ($entry::TYPE & Field::TYPE_KEYDICT) {
                // pull the elements starting with $key
                $out = [];
                foreach ($value as $keyname=>$data) {
                    if (substr($keyname,0,strlen($key)) == $key) {
                        $out[substr($keyname,strlen($key))] = $data;
                    }
                }
                if (sizeof($out)) {
                    $entry->wakeup($out);
                }
            }
        }
    }

    /**
     * @param Keydict $keydict
     * @param string $value
     * @param int $depth
     * @throws \Exception
     */
    public function acceptArray(Keydict $keydict, $value, $depth = 0)
    {
        if (is_array($value) && array_keys($value) == [0]) $value = array_shift($value);
        $source = $keydict->allFields(Field::TYPE_ANY, true);
        if (!is_array($value))
            $value = json_decode($value, true);
        foreach ($source as $entry) {
            $key = $entry->getName($depth);
            if ($entry::TYPE & Field::TYPE_ENTRY && $entry::TYPE ^ Field::TYPE_COMPOSITE) {
                // Regular Entry
                if (isset($value[$key])) {
                    $entry->set($value[$key]);
                }
            } elseif ($entry::TYPE & Field::TYPE_ENTRY && $entry::TYPE & Field::TYPE_COMPOSITE) {
                if (isset($value[$key])) {
                    $entry->set($value[$key]);
                } else {
                    $out = [];
                    foreach ($value as $keyname=>$data) {
                        //echo "do {$keyname}=>{$data}\r\n";
                        //echo "trying ",substr($keyname,0,strlen($key)+1) ," == " , $key.Keydict::DELIMITER.PHP_EOL;
                        if (substr($keyname,0,strlen($key)+1) == $key.Keydict::DELIMITER) {
                            $out[substr($keyname,strlen($key)+1)] = $data;
                        }
                    }
                    if (sizeof($out)) {
                        //var_dump('!',$out);
                        $entry->set($out);
                    }
                }
            } elseif ($entry::TYPE & Field::TYPE_KEYDICT) {
                // pull the elements starting with $key
                $out = [];
                foreach ($value as $keyname=>$data) {
                    if (substr($keyname,0,strlen($key)) == $key) {
                        $out[substr($keyname,strlen($key))] = $data;
                    }
                }
                if (sizeof($out)) {
                    $entry->acceptArray($out);
                }
            }
        }
    }
}