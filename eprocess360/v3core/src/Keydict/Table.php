<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 8/22/2015
 * Time: 9:57 AM
 */

namespace eprocess360\v3core\Keydict;


use eprocess360\v3core\DB;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Model;


/**
 * Class Table
 * Specifically for use in Model - It's just a Keydict with a specific class
 * @package eprocess360\v3core\Keydict
 */
class Table extends Model
{

    /**
     * Modifies the build function to also set table name and label
     * @param Field $fields
     * @return Keydict|void
     * @throws \Exception
     */
    public static function build(Field ...$fields)
    {
        $result = parent::build(...$fields);
        if (!($result instanceof self)) throw new \Exception("Table type expected but not received from build.");
        return $result;
    }

    /**
     * @param $selfColumn
     * @param $foreignColumn
     * @param Table $keydict
     * @return Table
     */
    public function joinByColumn($selfColumn, $foreignColumn, Table $keydict)
    {
        $keydict->addJoinClause("LEFT JOIN `{$this->getName()}` ON `{$this->getName()}`.`{$selfColumn}` = `{$keydict->getName()}`.`{$foreignColumn}`");
        foreach ($this->allFields(Field::TYPE_ENTRY, true) as $field) {
            if ($field->getName() != $foreignColumn) {
                $field->setMeta('table', $this->getName());
                $keydict->add($field, false);
            }
        }
        return $keydict;
    }

    /**
     * Overrides Keydict naming conventions to return only the raw name
     * @param int $depth
     * @return string
     */
    public function getName($depth = 0)
    {
        return $this->name;
    }

    /**
     * Mark Fields as ignore.  They won't be inserted or updated into the database.
     * @param Field ...$fields
     * @return Table
     */
    public function ignore(Field ...$fields)
    {

        return $this;
    }

    /**
     * @param $name
     */
    public function setTableName($name)
    {
        $this->name = $name;
    }

    /**
     * Insert the current data into the database. If primary key is not 0 throw error.
     */
    public function insert()
    {
        $primaryKey = $this->getPrimaryKey();
        if ((int)$primaryKey->get() != 0 && !$primaryKey->isForeignPrimaryKey()) {
            throw new Keydict\Exception\KeydictException("Row already has an ID and cannot be inserted.");
        } elseif (!$primaryKey->isForeignPrimaryKey()) {
            $primaryKey->setMeta('ignore');
        }


        $data = $this->cleanSleep();
        $keys = implode('`,`', array_keys($data));
        $values = implode (',', $data);
        $sql = "INSERT INTO `{$this->name}` (`{$keys}`) VALUES ({$values})";
        $this->{$primaryKey->getName()}->set(DB::insert($sql));
        return $this;
    }

    /**
     * @return Entry
     * @throws \Exception
     */
    public function getPrimaryKey()
    {
        foreach ($this->allFields(Field::TYPE_ENTRY, true) as $field) {
            if ($field->isPrimaryKey() || $field->isForeignPrimaryKey()) {
                return $field;
            }
        }
        throw new \Exception("Primary key does not exist.");
    }

    /**
     * Store the current data into the database for the available primary key.  Requires an IntPrimaryKey to work.
     */
    public function update()
    {
        $primaryKey = $this->getPrimaryKey();
        if ((int)$primaryKey->get()==0 || $primaryKey->hasMeta('changed')) {
            throw new Keydict\Exception\KeydictException("Row needs the primary key and original ID to be inserted ({$primaryKey->getName()}, {$primaryKey->get()} given).");
        }
        $primaryKey->setMeta('ignore');
        $data = $this->cleanSleep();
        $output = [];
        foreach ($data as $k=>$v) {
            $output[] = "`{$k}` = {$v}";
        }
        if (!sizeof($output)) throw new Keydict\Exception\KeydictException("No changes to update()");
        $output = implode(', ', $output);
        $sql = "UPDATE `{$this->name}` SET {$output} WHERE {$primaryKey->getColumnName()} = {$this->{$primaryKey->getName()}->get()}";
        DB::sql($sql);
        return $this;
    }
    
    /**
     * Delete the current row from the database using the available primary key.
     */
    public function delete()
    {
        $primaryKey = $this->getPrimaryKey();
        if ((int)$primaryKey->get()==0) {
            throw new Keydict\Exception\KeydictException("Row needs the primary key to be deleted ({$primaryKey->getName()}, {$primaryKey->get()} given).");
        }
        $sql = "DELETE FROM `{$this->name}` WHERE {$primaryKey->getColumnName()} = {$this->{$primaryKey->getName()}->get()}";
        DB::sql($sql);
        return $this;
    }



}