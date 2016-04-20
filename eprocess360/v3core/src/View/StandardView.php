<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 9/15/2015
 * Time: 11:11 AM
 */

namespace eprocess360\v3core\View;


use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\DB;
use eprocess360\v3core\Keydict\Entry;
use eprocess360\v3core\Keydict\Entry\SystemName;
use eprocess360\v3core\Keydict\Field;
use eprocess360\v3core\Keydict\StorageStrategy\SingleColumn;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;
use eprocess360\v3core\View;
use Exception;

/**
 * Class StandardView
 * @package eprocess360\v3core\View
 * @deprecated
 */
class StandardView
{

    /** @var Column[] */
    protected $columns = [];
    protected $name;
    /** @var Table|Model */
    protected $keydict;
    protected $columnSummary = [];
    protected $responseType = 'html';
    protected $label;
    protected $bucketBy = ['selected'=>'', 'order'=> ''];
    protected $selected = [];
    protected $join = [];
    protected $where = [];
    protected $from = '';
    protected $group = NULL;
    protected $sql;
    protected $filterBy = [];
    protected $searchBy = [];
    protected $sort = ['selected'=>'', 'order'=> ''];
    protected $totalCount = 0;
    protected $limit = 20;
    protected $current = 1;
    protected $last = 1;
    protected $table;

    /**
     * @param $name
     * @param $label
     * @throws \eprocess360\v3core\Keydict\Exception\InvalidValueException
     */
    public function __construct($name, $label)
    {
        $this->name = SystemName::validate($name);
        $this->label = $label;
    }

    /**
     * @param $name
     * @param $label
     * @param Table $keydict
     * @param $result ['keydict', 'select','join', 'where', "from", 'group']
     * @return StandardView
     * @throws \Exception
     */
    public static function build($name, $label, Table $keydict, $result)
    {
        $selected = $result['select'];
        $join = $result['join'];
        $where = $result['where'];
        $from = isset($result['from'])?$result['from']:$keydict->getRawName();
        $group = isset($result['group'])?$result['group']:NULL;
        $table = isset($result['table'])?$result['table']:NULL;

        $view = new static($name, $label);
        $view->setTable($table);
        $view->setKeydict($keydict);
        $view->add(Column::import($keydict->getPrimaryKey())->setEnabled(false));
        $view->addSql($selected, $join, $where, $from, $group);
        return $view;
    }

    /**
     * @return mixed
     */
    public function getSafeName()
    {
        return str_replace('.', '-', $this->getName());
    }

    /**
     * @param Column[] ...$column
     * @return $this
     */
    public function add(Column ...$column)
    {
        foreach ($column as $c) {
            /** @var Column $c */
            if (!in_array($c, $this->columns)) {
                $this->columns[$c->getName()] = $c;
                $c->setView($this);
            }
        }
        return $this;
    }

    /**
     * @param \eprocess360\v3core\Keydict\Field[] ...$fields
     * @return \Generator
     * @throws Exception
     */
    public function streamOut(Field ...$fields)
    {
        $sql = $this->sql;
        $results = DB::sql($sql);
        foreach ($results as $result) {
            yield $this->keydict->wakeup($result);
        }
    }

    /**
     * @return string
     */
    public function render()
    {
        global $pool, $twig;
        $pool->add($this, 'View');
        return $twig->render($this->template, $pool->asArray());
    }

    /**
     * @param Controller $controller
     * @throws Exception
     */
    public function response(Controller $controller)
    {
        //Keydict cannot completely be relyed upon becuase not every keydict entry is represented directly to a db entry
        //in the same table (ie, feeSchedule could not be used, but instead idFeeTag where FeeTagCategories.status_0 & 0b01 in FeeTemplates

        // filterBy: { feeType: ["Fees", "Valuation"],... }
        // bucketBy: {selected: "idFeeType", order: "ASC"}
        // textFilterBy: { feeType: "blah blah"}


        // filterBy: { feeType: ["Fees", "Valuation"],... }
        // for a given selected: add in the selected filter's SQL

        // bucketBy: {selected: "idFeeType", order: "ASC"} : ORDER BY columnname

        // textFilterBy: {feeType: "blah blah", ...} : columnname LIKE '%{$text}%' to WHERE's

        // Get Columns, from the set of columns and the set of Parse requests formulate the corresponding SQL;
        // get the sql and wake it up accordingly.

        // read in parameters
        $parameters = $this->parseRequest();
        $this->current = isset($parameters['page']['specified']) ? $parameters['page']['specified'] : $parameters['page']['default'];
        $this->last = isset($parameters['limit']['specified']) ? $parameters['limit']['specified'] : $parameters['limit']['default'];

        foreach (isset($parameters['columns']['specified'])? $parameters['columns']['specified'] : $parameters['columns']['default'] as $column=>$value) {
            $this->columnSummary['columns'][$column]['enabled'] = $value;
        }
        global $pool;

        $whereArray = $this->where;
        $sortArray = [];
        foreach($this->filterBy as $columnName => $selectedArray ){
            if($this->columns[$columnName]->getFilterByValue()) {
                $this->columns[$columnName]->setFilterByValueSelected($selectedArray);
                foreach ($selectedArray as $selected)
                    $whereArray[] = $this->columns[$columnName]->getFilterSql($selected);
            }
        }

        foreach($this->searchBy as $columnName => $text ){
            if($this->columns[$columnName]->getFilterBySearch()) {
                $fields = $this->columns[$columnName]->getFields();
                $text = DB::cleanse($text);
                $this->columns[$columnName]->setFilterBySearchText($text);
                $column = $this->columns[$columnName]->getFields() ? ($this->keydict->getRawName() !== NULL ? $this->keydict->getRawName().'.'.$this->columns[$columnName]->getName() : $this->columns[$columnName]->getName()): $this->columns[$columnName]->getSortMethods()[0]; //TODO Find an actual implimentation for this
                $whereArray[] = "{$column} LIKE '%{$text}%'";
            }
        }

        if($this->bucketBy['selected'] || ($this->columnSummary['defaultBucketBy'] && !isset($_GET['bucketBy']))){

            if(!$this->bucketBy['selected'] && !$this->bucketBy['order'] && ($this->columnSummary['defaultBucketBy'] && !isset($_GET['bucketBy'])))
                $this->bucketBy = $this->columnSummary['defaultBucketBy'];
            $selected = $this->bucketBy['selected'];
            $order = $this->bucketBy['order'];

            foreach($this->columns[$selected]->getSortMethods() as $method) {
                $order = DB::cleanse($order);
                if($order !== "ASC" && $order !== "DESC" && $order !== "")
                    throw new Exception("Invalid order has been given, order must be ASC or DESC, while ".$order." has been given.");
                if($order === "")
                    $order = "ASC";

                $sortArray[] = $method . " " . $order;
            }
        }

        if($this->sort['selected'] || $this->sort['order'] || $this->columnSummary['defaultSort']){
            $selected = $this->sort['selected'];
            $order = DB::cleanse($this->sort['order']);

            if($order !== "ASC" && $order !== "DESC" && $order !== "")
                throw new Exception("Invalid order has been given, order must be ASC or DESC, while ".$order." has been given.");
            if($order === "" && (isset($this->columnSummary['defaultSort']['order']) &&  $this->columnSummary['defaultSort']['order']))
                $order = $this->columnSummary['defaultSort']['order'];
            else if($order === "")
                $order = "ASC";

            if(!$selected && (isset($this->columnSummary['defaultSort']['selected']) &&  $this->columnSummary['defaultSort']['selected']))
                $selected = $this->columnSummary['defaultSort']['selected'];

            $this->sort = ['selected'=>$selected, 'order'=> $order];

            foreach($this->columns[$selected]->getSortMethods() as $method)
                $sortArray[] = $method." ".$order;
        }

        $select = implode(', ', $this->selected);
        $where = $whereArray? "WHERE ".implode(' AND ', $whereArray): '';
        $joins = implode(' ', $this->join);
        $sort = $sortArray? "ORDER BY ".implode(', ', $sortArray) : "";
        $table = $this->keydict;
        $primaryKey = $table->getPrimaryKey();
        $limit = $this->getLimit();
        if($this->getCurrent() < 1)
            throw new Exception("Current page cannot be less than 1.");
        $offset = ($this->getCurrent() - 1) * $limit;
        $group = $this->group ? "GROUP BY ".$this->group : "";
        $fullName = $this->table? $this->table.".".$primaryKey->getName() : $primaryKey->getFullColumnName();

        $select = $select.", (SELECT COUNT( DISTINCT ".$fullName.") FROM `{$this->from}` {$joins} {$where}) as totalCount";
        $sql = "SELECT {$select} FROM `{$this->from}` {$joins} {$where} {$group} {$sort} LIMIT {$offset},{$limit} ";

        $this->sql = $sql;

        //For composite entries that have represented options, order by both in proper order
        //for ordering by bit of a bits, order by IF that bit.
        $result = DB::sql($sql);

        if($result)
            $this->totalCount = (int)$result[0]['totalCount'];
        else{
            $sql = "SELECT COUNT( DISTINCT ".$fullName.") as totalCount FROM `{$this->from}` {$joins} {$where}";
            $result = DB::sql($sql);
            $this->totalCount = (int)$result[0]['totalCount'];
        }


        $pool->add($this, 'View');
    }

    /**
     * Receives the request from client and parses the settings into a usable format.  Validates data.  In the future
     * will be extended to store the settings as defaults per user.
     * @param null $jsonBody
     * @return array
     */
    public function parseRequest($jsonBody = null)
    {
        $cols = $this->columnSummary();
        reset($this->columns);
        $firstCol = current($this->columns);
        $base = [
            'limit'=>[          // limitations and default for page length
                'default'=>20,
                'min'=>1,
                'max'=>100
            ],
            'page'=>[           // limitations and default for page number
                'default'=>1,
                'min'=>1,
                'max'=>1
            ],
            'columns'=>[        // default columns enabled
                'default'=>$cols['defaultCols']
            ],
            'sort'=>[           // default sort on columns
                'default'=>[
                    $firstCol->getName()=>$firstCol->getSort()
                ]
            ],
            'matching'=>[       // default text search
                'default'=>''
            ]
        ];
        $requestName = 'view-'.$this->getSafeName();
        if (!$jsonBody) {
            $jsonBody = json_decode(file_get_contents('php://input'), true);
        }
        if (sizeof($jsonBody)) {
            $this->responseType = 'json';
            if (isset($jsonBody['limit'])) {
                $limit = $jsonBody['limit'];
                unset($jsonBody['limit']);
                $jsonBody = ['limit'=>$limit] + $jsonBody;
            }
            /**
             * [body]
             *      [page]      => 1+
             *      [limit]     => 1+
             *      [columns] ** this doesn't prevent data from being sent, only displayed in the results
             *              [columnName] => 0/1
             *              ...
             *      [sort]
             *              [fieldName] => 'ASC'|'DESC'
             *              ...
             *      [matching]  => (string)
             */
            foreach ($jsonBody as $key=>$fullValue) {
                if (isset($base[$key])) {
                    if (isset($base[$key]['min']) && $fullValue < $base[$key]['min']) {
                        $base[$key]['specified'] = $base[$key]['min'];
                    } elseif (isset($base[$key]['max']) && $fullValue > $base[$key]['max']) {
                        $base[$key]['specified'] = $base[$key]['max'];
                    } elseif ($key == 'columns') {
                        foreach ($fullValue as $colKey=>$colValue) {
                            if (isset($base['columns']['default'][$colKey])) {
                                $base['columns']['specified'][$colKey] = ($colValue == 1?true:false);
                            }
                        }
                    } elseif ($key == 'sort') {
                        $out = [];
                        foreach ($fullValue as $colKey=>$colValue) {
                            if (isset($base['columns']['default'][$colKey])) {
                                if (!isset($out[$colKey])) {
                                    $out[$colKey] = ($colValue=='ASC'?'ASC':'DESC');
                                }
                            }
                        }
                        if (sizeof($out)) {
                            $base['sort']['specified'] = $out;
                        }
                        //var_dump($base['sort']['specified']);
                    } else {
                        $base[$key]['specified'] = $fullValue;
                    }

                    if ($key=='limit') {
                        $this->limit = $base[$key]['specified'];
                        $base['page']['max'] = (int)$this->getNumPages()+1;
                    }
                }
            }
        }
        elseif (isset($_GET)) {

            $data = $_GET;
            if(isset($data['limit']))
                $base['limit']['specified'] = (int)$data['limit'];
            if(isset($data['currentPage']))
                $base['page']['specified'] = (int)$data['currentPage'];
            if(isset($data['sort'])){
                if(isset($data['sort']['selected']))
                    $this->sort['selected'] = $data['sort']['selected'];
                if(isset($data['sort']['order']))
                    $this->sort['order'] = $data['sort']['order'];
            }
            if(isset($data['searchBy']))
                $this->searchBy = $data['searchBy'];
            if(isset($data['filterBy']))
                $this->filterBy = $data['filterBy'];
            if(isset($data['bucketBy']))
                $this->bucketBy = $data['bucketBy'];
        }

        return $base;
    }

    /**
     * @return array
     */
    public function columnSummary()
    {
        $fields = [];
        $columns = [];
        $defaultCols = [];
        $fieldsIndexed = [];
        $sort = [];
        $filterBySearch = [];
        $filterByValue = [];
        $defaultSort = [];
        $defaultBucketBy = [];
        foreach ($this->columns as $column) {
            foreach ($column->getFields() as $field) {
                if (!in_array($field, $fields)) {
                    $fields[] = $field;
                    $fieldsIndexed[$field->getName()] = $field;
                }
            }
            $columns[$column->getName()] = [
                'label'=>$column->getLabel(),
                'enabled'=>$column->isEnabled(),
                'filterBy'=>(boolean)$column->getFilterByValue(),
                'filterByOptions'=>$column->getFilterByValueOptions(),
                'searchBy'=>$column->getFilterBySearch(),
                'template'=> $column->getTemplate(),
                'sort'=> (boolean)$column->getSort(),
                'bucketBy'=> $column->getBucketBy(),
                'isLink'=> $column->getIsLink()
            ];
            $defaultCols[$column->getName()] = $column->isEnabled();
            if ($column->getSort()) {
                $sort[$column->getName()] = $column->getSort();
                if ($column->getDefaultSort()) {
                    $defaultSort['selected'] = $column->getName();
                }
            }
            if ($column->getFilterByValue()) {
                $filterByValue[$column->getName()] = $column->getFilterByValueSelected();
            }
            if ($column->getFilterBySearch()) {
                $filterBySearch[$column->getName()] = $column->getFilterBySearchText();
            }
            if ($column->getBucketBy() && $column->getDefaultBucketBy()) {
                $defaultBucketBy = ['selected'=>$column->getName(), 'order' =>$column->getDefaultBucketBy()];
            }
        }

        return $this->columnSummary = [
            'fields'=>$fields,
            'columns'=>$columns,
            'sort'=>$sort,
            'defaultSort'=>$defaultSort,
            'defaultCols'=>$defaultCols,
            'defaultBucketBy'=>$defaultBucketBy,
            'fieldsIndexed'=>$fieldsIndexed,
            'searchBy'=>$filterBySearch,
            'filterBy'=>$filterByValue
        ];
    }

    /**
     * @return string
     */
    public function json($string = true)
    {
        $cols = $this->columnSummary();
        $indexedColumns = [];
        $i = 0;
        foreach($cols['columns'] as $key => $value){
            $value['key'] = $key;
            $indexedColumns[$i] = $value;
            $i++;
        }
        $buffer = [];
        /**
         * @var Entry $row
         */
        foreach ($this->streamOut(...$cols['fields']) as $row) {
            $rowData = $row->toArray();
            // need to handle flags
            $buffer[] = $rowData;
        }
        $result = [
            'results'=>$buffer,             // Row as array output
            'currentPage'=>$this->getCurrent(), // Current page number
            'numPages'=>$this->getNumPages(),   // Last page number
            'limit'=>$this->getLimit(),     // Results per page
            'rowCount'=>$this->totalCount,
            'columns'=>$indexedColumns,    // Column summary name=>[]
            'sort'=>$this->sort,          // Column sort information
            'searchBy'=>$cols['searchBy'],          // Column filterBySearch information
            'filterBy'=>$cols['filterBy'],          // Column filterByValue information
            'bucketBy'=>$this->bucketBy   // Grouping information
        ];
        if($string)
            return json_encode($result);
        else
            return $result;

    }

    /**
     * @return mixed|string
     */
    public function getSingleTemplate()
    {
        $raw = '<tr>';
        foreach ($this->columns as $column) {
            $raw .= '<td class="datacol-'.$column->getName().'">'.$column->getTemplate().'</td>';
        }
        $raw .= '</tr>';
        return $raw;
    }

    /**
     * @return mixed|string
     */
    public function getClientSingleTemplate()
    {
        $raw = $this->getSingleTemplate();
        /* This performs the substitution replacements */
        $raw = preg_replace("/{% .*? %}{# (.*?) #}/", "$1", $raw);

        /* This does automatic substitution for variables */
        $raw = preg_replace_callback("/{{ (.*?) }}/", function ($match) {
            $match = $match[1];
            if (substr($match,0,6) == 'SysVar') {
                // SysVar, we'll just insert the value
                global $pool;
                return $pool->SysVar->get(substr($match,strpos($match,'(')+2,-2));
            } else {
                // We will expect the object in the JSON
                $match = substr_replace($match, 'this', 0, strpos($match,'.'));
                $match = substr($match,0,strpos($match, '.get()'));
                return "<%{$match}%>";
            }
        }, $raw);
        return $raw;
    }

    /**
     * @return mixed
     */
    public function getMore()
    {
        return $this->keydict->getMore();
    }

    /**
     * @return mixed
     */
    public function getLess()
    {
        return $this->keydict->getLess();
    }

    /**
     * @return mixed
     */
    public function getCurrent()
    {
        return $this->current;
    }

    /**
     * @return mixed
     */
    public function getNumPages()
    {
        return ceil($this->totalCount / $this->limit);
    }

    /**
     * @return mixed
     */
    public function getNext()
    {
        return $this->keydict->getLastField();
    }

    /**
     * @return mixed|string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return Table|Model
     */
    public function getKeydict()
    {
        return $this->keydict;
    }

    /**
     * @param Table|Model $keydict
     * @return StandardView
     */
    public function setKeydict($keydict)
    {
        $this->keydict = $keydict;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param $selected
     * @param $join
     * @param $where
     * @param $from
     * @param $group
     */
    private function addSql($selected, $join, $where, $from, $group)
    {
        $this->selected  = [$selected];
        $this->join = [$join];
        $this->where = $where?[$where]:[];
        $this->from  = $from;
        $this->group = $group;
    }

    public function setTable($table)
    {
        $this->table = $table;
    }

    public function getTable()
    {
        return $this->table;
    }
}