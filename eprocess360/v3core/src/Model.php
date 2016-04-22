<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 8/7/2015
 * Time: 9:07 PM
 */

namespace eprocess360\v3core;


use eprocess360\v3core\Keydict\Entry;
use eprocess360\v3core\Keydict\Entry\Bit;
use eprocess360\v3core\Keydict\Entry\Bits;
use eprocess360\v3core\Keydict\Exception\KeydictException;
use eprocess360\v3core\Keydict\Field;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\DB;


/**
 * Class Model
 * @package eprocess360\v3core
 */
class Model extends Keydict
{
    const START_SUBQUERY = 1;
    const END_SUBQUERY = -1;
    /** @var Table */
    protected $keydict;
    protected $tablename;
    const TABLE_NAME = null;
    protected $dataset;
    protected $pointer;
    protected $foreignPrimaryKey;
    protected $joinClauses = [];
    protected $where = [];
    /** @var Entry */
    protected $offsetField = null;
    protected $orderBy = [];
    protected $lastField = false;
    protected $lastSql = '';
    protected $limit = 20;
    protected $offset = 1;
    protected $id = '';
    protected $fieldsComplete = [];
    protected $more = 0;
    protected $page = 1;
    protected $sqlOverride = false;

    /**
     * @param null $sql
     * @return Keydict[]
     * @throws \Exception
     */
    public static function each($sql = null)
    {
        if ($sql) {
            $results = DB::sql($sql);
            
            if ($results) {
                
                $keydict = static::keydict();

                foreach ($results as $result) {
                    yield $keydict->wakeup($result);
                }
            }
        }
        else {
            throw new \Exception("No results.");
        }

    }

    /**
     * Delete row by primaryKey
     * @param $id
     * @throws KeydictException
     * @throws Keydict\Exception\InvalidValueException
     * @throws \Exception
     */
    public static function deleteById($id)
    {
        $users = static::keydict();
        $users->getPrimaryKey()->set($id);
        $users->delete();
    }

    /**
     * Figure out exactly how heavy an operation is with the each() method (which needs updating because it doesn't
     * follow the same pattern as below).  There is probably room for substantial improvements in time by creating a
     * wakeup tree the first time an entire keydict is wakeup'd, and then reusing that tree to determine which raw data
     * goes where.
     * @param $iterations
     * @param null $sql
     * @return Keydict[]
     * @throws \Exception
     */
    public static function benchmark($iterations, $sql = null)
    {
        $keydict = static::keydict();
        echo "Benchmarking {$iterations} of wakeup({$keydict->getName()}) with the first row from '{$sql}'\r\n";
        if ($sql) {
            $results = DB::sql($sql);
            $totalTime = 0;
            $totalMemory = 0;
            $usedMemory = 0;
            $peakMemory = 0;
            if ($results) {
                $start = Toolbox::microtimeFloat();
                $memory = memory_get_usage();
                for ($i=0;$i<$iterations;$i++) {
                    $out[] = $keydict->wakeup($results[0]);
                }
                $totalTime = Toolbox::microtimeFloat() - $start;
                $usedMemory = Toolbox::convertBytes(memory_get_usage() - $memory);
                $totalMemory = Toolbox::convertBytes(memory_get_usage());
                $peakMemory = Toolbox::convertBytes(memory_get_peak_usage());
            }
            echo "Total Time: {$totalTime}s; Memory Used: {$usedMemory} (Total: {$totalMemory}, Peak: {$peakMemory})\r\n";
            die();
        }
        throw new \Exception("Cannot benchmark because there are no results.");
    }

    /**
     * Returns this Table joined on the given Table
     * @param Table $table
     * @return Table
     * @throws \Exception
     */
    public static function join(Table $table)
    {
        $self = static::keydict();
        foreach ($table->allFields(Field::TYPE_ENTRY, true) as $field) {
            foreach ($field->getJoins() as $key=>$join) {
                if ($join instanceof static) {
                    return $table->joinByColumn($field->getName(), $key ?: $field->getName(), $self);
                }
            }
        }
        throw new \Exception("Cannot join these tables");
    }

    /**
     * @return \Generator
     * @throws \Exception
     */
    public static function sqlSelectAll()
    {
        $keydict = static::keydict();
        return static::each("SELECT * FROM {$keydict->getRawName()}");
    }

    /**
     * Return a Keydict/Model loaded the data from the database with the given Primary Key ID.
     * @param $id
     * @return Table
     * @throws \Exception
     */
    public static function sqlFetch($id)
    {
        $table = static::keydict();
        /** @var Entry $field */
        foreach ($table->allFields(Field::TYPE_ENTRY, true) as $field) {
            if ($field->isPrimaryKey()) {
                $id = $field::validate($id);
                $id_clean = DB::cleanse($id);
                $sql = "SELECT * FROM `{$table->getRawName()}` WHERE {$field->getColumnName()} = '{$id_clean}'";
                $results = DB::sql($sql);
                
                if ($results) {
                    return $table->wakeup($results[0]);
                }
                throw new \Exception("Could not find row for '{$id}' in table {$table->getRawName()}.");
            }
        }
        throw new \Exception("Could not find Primary Key for table {$table->getRawName()}.");
    }

    /**
     * @param Entry $field
     * @param string $operator
     * @param int $value
     * @param int $subquery
     * @param string $concat
     * @return Table
     * @throws Keydict\Exception\InvalidValueException
     * @throws \Exception
     */
    public function where(Entry $field, $operator = '=', $value = 0, $subquery = 0, $concat = '')
    {
        if ($subquery==self::START_SUBQUERY) {
            $this->where[] = '(';
        }

        $concat = $concat?$concat.' ':'';
        if (method_exists($field, 'where')) {
            $this->where[] = "{$concat}{$field->where($operator, $value)}";
        } else {
            throw new KeydictException("where() operator unavailable for {$field->getClass()}.");
        }

        if ($subquery==self::START_SUBQUERY) {
            $this->where[] = ')';
        }
        return $this;
    }

    /**
     * @param Entry $field
     * @param string $operator
     * @param int $value
     * @param int $subquery
     * @return Table
     * @throws \Exception
     */
    public function andWhere(Entry $field, $operator = '=', $value = 0, $subquery = 0)
    {
        return $this->where($field, $operator = '=', $value, $subquery, 'AND');
    }

    /**
     * @param Entry $field
     * @param string $operator
     * @param int $value
     * @param int $subquery
     * @return Table
     * @throws \Exception
     */
    public function orWhere(Entry $field, $operator = '=', $value = 0, $subquery = 0)
    {
        return $this->where($field, $operator = '=', $value, $subquery, 'OR');
    }

    /**
     * @param $id
     * @return $this
     * @throws \Exception
     */
    public function fetchId($id)
    {
        $table = $this;
        /** @var Entry $field */
        foreach ($table->allFields(Field::TYPE_ENTRY, true) as $field) {
            if ($field->isPrimaryKey()) {
                $id = $field::validate($id);
                $id_clean = DB::cleanse($id);
                $joins = implode(' ', $this->joinClauses);
                $sql = "SELECT * FROM `{$table->getRawName()}` {$joins} WHERE {$field->getColumnName()} = '{$id_clean}'";
                $results = DB::sql($sql);
                if ($results) {
                    return $table->wakeup($results[0]);
                }
                throw new \Exception("Could not find row for '{$id}' in table {$table->getRawName()}.");
            }
        }
        throw new \Exception("Could not find Primary Key for table {$table->getRawName()}.");
    }

    /**
     * Primary row fetcher, does things as you would expect them
     * @return Table|$this
     * @throws \Exception
     */
    public function fetch()
    {
        $sql = $this->buildQuery([
            'limit' => $this->limit+1
        ]);
        $i = 0;
        $this->lastSql = $sql;
        $results = DB::sql($sql);
        foreach ($results as $result) {
            ++$i;
            if ($i<=$this->limit) {
                yield $this->wakeup($result);
            }
        }
        if ($i>$this->limit) {
            $this->more = 1;
        } else {
            $this->more = 0;
        }
    }

    /**
     * Row fetcher, but only populates the Fields specified
     * @param Keydict\Field[] ...$fields
     * @return \Generator
     * @throws \Exception
     */
    public function fetchOnly(Field ...$fields)
    {
        if (!$this->fieldsComplete) $this->fieldsComplete = $this->fields;
        else $this->fields = $this->fieldsComplete;
        foreach ($this->fields as $field) {
            if (!in_array($field, $fields)) {
                $this->remove($field);
            }
        }
        $sql = $this->buildQuery([
            'limit' => $this->limit+1
        ]);
        $i = 0;
        $this->lastSql = $sql;
        $results = DB::sql($sql);
        foreach ($results as $result) {
            ++$i;
            if ($i<=$this->limit) {
                yield $this->wakeup($result);
            }
        }
        if ($i>$this->limit) {
            $this->more = 1;
        } else {
            $this->more = 0;
        }
    }

    /**
     * @return Keydict|Entry|Field
     */
    public function getMore()
    {
        return $this->more;
    }

    /**
     * Get current page
     * @return array
     */
    public function getCurrent()
    {
        return $this->page;
    }

    /**
     * Are there results on a previous page?
     * @return int
     */
    public function getLess()
    {
        return $this->page > 1 ? 1 : 0;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return float|int
     * @throws \Exception
     */
    public function getLastPage()
    {
        $sql = $this->buildQuery(['count'=>true]);
        $results = DB::sql($sql);
        if ($results) {
            return ceil($results[0]['totalCount'] / $this->limit);
        }
        return 1;
    }

    /**
     * @param array $options
     * @return string
     * @throws \Exception
     */
    private function buildQuery($options = [])
    {
        if($this->sqlOverride)
            return $this->sqlOverride;
        $table = $this;
        $joins = implode(' ', $this->joinClauses);
        $where = '';
        $primaryKey = $this->getPrimaryKey();
        $primaryKeyName = $primaryKey->getFullColumnName();

        if (sizeof($this->where)) {
            $where = "WHERE ".implode(' ', $this->where);
        }

        $select = '*';
        if (isset($options['count']) && $options['count']) {
            $select = 'COUNT('.$primaryKey->getFullColumnName().') as totalCount';
        }

        $sql = "SELECT {$select} FROM `{$table->getRawName()}` {$joins} {$where} ";

        // SORT HANDLER
        if (!isset($options['count']) || !$options['count']) {
        $sortOut = [];
        $orderHasPrimaryKey = false;
        $sql .= "ORDER BY ";
        if (sizeof($this->orderBy)) {
            foreach ($this->orderBy as $key=>$orderBy) {
                /** @var Entry $orderBy */
                if ($primaryKeyName == $key) {
                    $orderHasPrimaryKey = true;
                }
                if ($orderBy->hasMeta('sort')) {
                    $sort = $orderBy->getMeta('sort');
                } elseif (isset($options['sort'])) {
                    $sort = $options['sort'];
                } else {
                    $sort = 'ASC';
                }

                $sortOut[] = "{$orderBy->getFullColumnName()} {$sort}";
            }
        }
        if (!$orderHasPrimaryKey) {
            if ($primaryKey->hasMeta('sort')) {
                $sort = $primaryKey->getMeta('sort');
            } elseif (isset($options['sort'])) {
                $sort = $options['sort'];
            } else {
                $sort = 'ASC';
            }
            $sortOut[] = "{$primaryKey->getFullColumnName()} {$sort}";
        }
        $sql .= implode(', ', $sortOut).' ';

            // LIMIT HANDLER
            if (isset($options['limit'])) {
                $limit = (int)$options['limit'];
            } else {
                $limit = $this->limit;
            }

            $offset = ($this->page - 1) * $this->limit;
            $sql .= "LIMIT {$offset},{$limit}";
        }
        //echo $sql;
        return $sql;
    }

//    /**
//     * @param null $offset
//     * @return Model|Table
//     * @deprecated Use getPage
//     */
//    public function page($offset = null)
//    {
//        $out = 0;
//        if ($offset==null) $offset = 0;
//        if (!is_numeric($offset)) {
//            $offset = explode(',',$offset);
//            for ($i=0; $i<sizeof($offset); $i=$i+2) {
//                if ($offset[$i]==$this->id) {
//                    $out = (int)$offset[$i+1];
//                }
//            }
//        }
//        $this->offset = ($out?:(int)$offset)?:1;
//        return $this;
//    }

    /**
     * @param int $page
     * @return Model|Table
     */
    public function setPage($page = 1)
    {
        $this->page = $page>0?(int)$page:1;
        return $this;
    }


    /**
     * @param $id
     * @return Model|Table
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Specify the sort order for a field as ASCENDING
     * @param Entry $field
     * @return Model|Table
     */
    public function asc(Entry $field)
    {
        $field->setMeta('sort', 'ASC');
        $this->orderBy[$field->getFullColumnName()] = $field;
        return $this;
    }

    /**
     * Specify the sort order for a field as DESCENDING
     * @param Entry $field
     * @return Model|Table
     */
    public function desc(Entry $field)
    {
        $field->setMeta('sort', 'DESC');
        $this->orderBy[$field->getFullColumnName()] = $field;
        return $this;
    }

    /**
     * @param Entry $field
     * @param null $sort
     * @return $this
     */
    public function setSort(Entry $field, $sort = null)
    {
        if ($sort === null) {
            if ($field->hasMeta('sort')) {
                $field->unsetMeta('sort');
            }
        } else {
            $field->setMeta('sort', strtoupper($sort)=='ASC'?'ASC':'DESC');
            $this->orderBy[$field->getFullColumnName()] = $field;
        }
        return $this;
    }


    /**
     * @param null $limit
     * @return $this
     */
    public function setLimit($limit = null)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @param $string
     * @return $this
     */
    public function addJoinClause($string)
    {
        $this->joinClauses[] = $string;
        return $this;
    }

    /**
     * @return Table
     */
    public static function keydict()
    {
        return null;
    }

    /**
     * @return Entry
     * @throws \Exception
     */
    public function getPrimaryKey()
    {
        foreach ($this->getKeydict()->allFields(Field::TYPE_ENTRY, true) as $field) {
            if ($field->isPrimaryKey() || $field->hasMeta('foreignPrimaryKey')) {
                return $field;
            }
        }
        throw new \Exception("Primary key does not exist.");
    }

    /**
     * Create a table from the specification
     * @param bool|false $returnSql
     * @return array|null|string
     * @throws \Exception
     */
    public static function createTable($returnSql = false)
    {
        $primaryKeys = [];
        $keydict = static::keydict();
        $columns = $keydict->getSpecification();
        $out = [];
        foreach ($columns as $column=>$data) {
            $out[] = "`{$column}` {$data['type']}".(array_key_exists('length', $data) && $data['length'] ? "({$data['length']})" : '').(array_key_exists('unsigned', $data)?' UNSIGNED':'').(array_key_exists('auto_increment', $data)?' AUTO_INCREMENT':'');
            if(array_key_exists('primary_key', $data)) {
                $primaryKeys[] = "`{$column}`";
            }
        }
        if($primaryKeys)
            $out[] = "PRIMARY KEY (".implode(', ', $primaryKeys).")";
        $out = implode(', ', $out);
        $sql = "CREATE TABLE `{$keydict->getRawName()}` ({$out}) CHAR SET latin1";
        if ($returnSql) {
            return $sql;
        }
        return DB::sql($sql);
    }


    /**
     * @return Keydict
     */
    public function getKeydict()
    {
        return $this->keydict;
    }

    /**
     * @param Keydict $keydict
     */
    public function setKeydict($keydict)
    {
        $this->keydict = $keydict;
    }


//    /**
//     * @return mixed
//     */
//    public function getColumnName()
//    {
//        die(); // need analysis naming is off
//        return $this->tablename;
//    }
//
//    /**
//     * @param mixed $tablename
//     */
//    public function setTablename($tablename)
//    {
//        $this->tablename = $tablename;
//    }

    public function setDataSet(DataSet $dataset)
    {
        $this->dataset = $dataset;
        $this->dataset->setModel($this);
        $this->getKeydict()->bindDataSet($dataset);
    }

    /**
     * Mostly used for joinsOn when you don't want to pass Keydicts that could potentially infinite loop
     * @return Model
     */
    public static function model()
    {
        return new static;
    }

    public static function dropTable()
    {
        $keydict = static::keydict();
        $sql = "DROP TABLE `{$keydict->getRawName()}`";
        DB::sql($sql);
    }

    /**
     * @return boolean
     */
    public function getLastField()
    {
        return $this->lastField;
    }

    /**
     * @param string $lastSql
     * @return Model
     */
    public function setLastSql($lastSql)
    {
        $this->lastSql = $lastSql;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastSql()
    {
        return $this->lastSql;
    }

    public function setSqlOverride($sql)
    {
        $this->sqlOverride = $sql;
    }

    public function insert()
    {
        $this->data->insert();
    }
}