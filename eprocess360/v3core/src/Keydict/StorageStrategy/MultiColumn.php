<?php
/**
 * Created by PhpStorm.
 * User: kira
 * Date: 8/13/2015
 * Time: 11:34 AM
 */

namespace eprocess360\v3core\Keydict\StorageStrategy;


use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Entry\Bits;
use eprocess360\v3core\Keydict\Entry\Flags;
use eprocess360\v3core\Keydict\Field;

/**
 * Class MultiColumn
 * Designed for moving data between the Keydict and multiple column tables.  When using this strategy, it is assumed
 * that the end use will always be for INSERT or UPDATE of one or more columns in the database where a key/value pair
 * is required.
 * @package eprocess360\v3core\Keydict\StorageStrategy
 */
class MultiColumn implements InterfaceStorageStrategy
{
    protected $flags = false;

    public function __construct($flags = false)
    {
        $this->flags = $flags;
    }

    /**
     * @param Keydict $keydict
     * @param int $depth
     * @return array
     * @throws \Exception
     */
    public function sleep(Keydict $keydict, $depth = 0)
    {
        $out = array();
        $source = $keydict->allFields(Field::TYPE_ANY, true);
        foreach ($source as $entry) {
            /** @var Field $results */
            $results = $entry->sleep($depth + 1);
            if (is_array($results)) {
                foreach ($results as $key=>$result) {
                    $out[$key] = $result;
                }
            } else {
                $out[$entry->getName($depth)] = $results;
            }
        }
        return $out;
    }

    /**
     * @param Keydict $keydict
     * @param int $depth
     * @return array
     * @throws \Exception
     */
    public function cleanSleep(Keydict $keydict, $depth = 0)
    {
        $out = array();
        $source = $keydict->allFields(Field::TYPE_ANY, true);
        foreach ($source as $entry) {
            if (!$entry->hasMeta('ignore')) {
                /** @var Field $results */
                $results = $entry->cleanSleep($depth + 1);
                if (is_array($results)) {
                    foreach ($results as $key => $result) {
                        $out[$key] = $result;
                    }
                } else {
                    $out[$entry->getName($depth)] = $results;
                }
            }
        }
        return $out;
    }

    /**
     * @param Keydict $keydict
     * @param int $depth
     * @return array
     * @throws \Exception
     */
    public function getSpecification(Keydict $keydict, $depth = 0)
    {
        $out = array();

        $source = $keydict->allFields(Field::TYPE_ANY, true);
        foreach ($source as $entry) {
            /** @var Field $results */
            if ($entry::TYPE & Field::TYPE_CONTAINER) {
                $results = $entry->getSpecification($depth + 1);
                foreach ($results as $key => $result) {
                    $out[$key] = $result;
                }
            } else {
                $out[$entry->getName($depth)] = $entry->getSpecification($depth);
            }
        }
        return $out;
    }

    /**
     * @param Keydict $keydict
     * @param $value
     * @param int $depth
     * @throws \Exception
     */
    public function wakeup(Keydict $keydict, $value, $depth = 0)
    {
        $source = $keydict->allFields(Field::TYPE_ANY, true);
        foreach ($source as $entry) {
            $key = $entry->getName($depth);
            if ($entry::TYPE & Field::TYPE_ENTRY && ($entry::TYPE & Field::TYPE_COMPOSITE) != Field::TYPE_COMPOSITE) {
                // Regular Entry
                if (isset($value[$key])) {
                    $entry->wakeup($value[$key]);
                }
            } elseif ($entry::TYPE & Field::TYPE_ENTRY && $entry::TYPE & Field::TYPE_COMPOSITE) {
                if ($entry instanceof Bits) {
                    $out = [];
                    foreach ($value as $keyname=>$data) {
                        if (substr($keyname,0,strlen($key)+1) == $key.Keydict::DELIMITER) {
                            $out[] = $data;
                            unset($value[$keyname]);
                        }
                    }

                    if (sizeof($out)) {
                        $entry->wakeup($out);
                    }
                }
                if (isset($value[$key])) {
                    $entry->wakeup($value[$key]);
                } else {
                    $out = [];
                    foreach ($value as $keyname=>$data) {
                        if (substr($keyname,0,strlen($key)+1) == $key.Keydict::DELIMITER) {
                            $out[substr($keyname,strlen($key)+1)] = $data;
                        }
                    }
                    if (sizeof($out)) {
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
     * @param $value
     * @param int $depth
     * @throws \Exception
     */
    public function acceptArray(Keydict $keydict, $value, $depth = 0)
    {
        $source = $keydict->allFields(Field::TYPE_ANY, true);
        foreach ($source as $entry) {
            $key = $entry->getName($depth);
            if ($entry::TYPE & Field::TYPE_ENTRY && ($entry::TYPE & Field::TYPE_COMPOSITE) != Field::TYPE_COMPOSITE) {
                // Regular Entry
                if (isset($value[$key])) {
                    $entry->set($value[$key]);
                }
            } elseif ($entry::TYPE & Field::TYPE_ENTRY && $entry::TYPE & Field::TYPE_COMPOSITE) {
                if ($entry instanceof Bits) {
                    $out = [];
                    foreach ($value as $keyname=>$data) {
                        if (substr($keyname,0,strlen($key)+1) == $key.Keydict::DELIMITER) {
                            $out[] = $data;
                            unset($value[$keyname]);
                        }
                    }

                    if (sizeof($out)) {
                        $entry->set($out);
                    }
                }
                if (isset($value[$key])) {
                    $entry->set($value[$key]);
                } else {
                    $out = [];
                    foreach ($value as $keyname=>$data) {
                        if (substr($keyname,0,strlen($key)+1) == $key.Keydict::DELIMITER) {
                            $out[substr($keyname,strlen($key)+1)] = $data;
                        }
                    }
                    if (sizeof($out)) {
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