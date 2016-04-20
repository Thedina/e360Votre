<?php

namespace eprocess360\v3core\tests\base;

/**
 * Class PHPUnitArrayTable
 * Quick and dirty extension to PHPUnit so we can build DB comparison tables
 * from arrays the same was as DataSets. Oddly this functionality does not seem
 * to come standard.
 * @package eprocess360\v3core\tests\base
 */
class PHPUnitArrayTable extends \PHPUnit_Extensions_Database_DataSet_AbstractTable
{
    /**
     * Getup table metadata based on column names extracted from row data
     */
    protected function createTableMetaData() {
        if($this->data[0]) {
            $cols = array_keys($this->data[0]);
            $this->tableMetaData = new \PHPUnit_Extensions_Database_DataSet_DefaultTableMetaData($this->tableName, $cols);
        }
    }

    /**
     * @param string $tableName
     * @param array $data
     */
    public function __construct($tableName, $data) {
        $this->tableName = $tableName;
        $this->data = $data;
    }

    /**
     * Returns the table's metadata.
     * @return \PHPUnit_Extensions_Database_DataSet_ITableMetaData
     */
    public function getTableMetaData()
    {
        $this->createTableMetaData();
        return parent::getTableMetaData();
    }

    /**
     * Checks if a given row is in the table
     * @param array $row
     * @return bool
     */
    public function assertContainsRow(array $row)
    {
        return parent::assertContainsRow($row);
    }

    /**
     * Returns the number of rows in this table.
     * @return int
     */
    public function getRowCount()
    {
        return parent::getRowCount();
    }

    /**
     * Returns the value for the given column on the given row.
     * @param int $row
     * @param int $column
     */
    public function getValue($row, $column)
    {
        return parent::getValue($row, $column);
    }

    /**
     * Returns the an associative array keyed by columns for the given row.
     * @param int $row
     * @return array
     */
    public function getRow($row)
    {
        return parent::getRow($row);
    }

    /**
     * Asserts that the given table matches this table.
     * @param \PHPUnit_Extensions_Database_DataSet_ITable $other
     */
    public function matches(\PHPUnit_Extensions_Database_DataSet_ITable $other)
    {
        return parent::matches($other);
    }
}