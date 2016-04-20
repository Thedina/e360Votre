<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 9/15/2015
 * Time: 11:17 AM
 */

namespace eprocess360\v3core\View;


use Composer\Util\Filesystem;
use eprocess360\v3core\Keydict\Entry;
use eprocess360\v3core\Keydict\Entry\Bit;
use eprocess360\v3core\Keydict\Entry\SystemName;
use eprocess360\v3core\Keydict\Field;
use eprocess360\v3core\View;

/**
 * Class Column
 * Configure a column that the user can select and use on a View.  Adding a column exposes the data in that column to
 * the end user.
 * @package eprocess360\v3core\View
 * @deprecated
 */
class Column
{
    protected $name;
    protected $label;
    /** @var Entry[] */
    protected $fields = [];
    protected $view;
    /** @var null|\Closure  */
    protected $handler = null;
    /** @var bool Whether or not to use this column by default */
    protected $enabled = true;
    protected $sort = null;
    protected $template = null;
    protected $filterBySearch = false;
    protected $selectedFilterBySearch = false;
    protected $filterByValue = [];
    protected $selectedFilterByValue = [];
    protected $bucketBy = false;
    protected $filterBySearchText = '';
    protected $sortMethods = [];
    protected $isLink = false;
    protected $defaultSort = false;
    protected $defaultbucketBy = false;
    /**
     * @param $name
     * @param $label
     * @throws \eprocess360\v3core\Keydict\Exception\InvalidValueException
     */
    private function __construct($name, $label)
    {
        $this->name = SystemName::validate($name);
        $this->label = $label;
    }

    /**
     * @param $name
     * @param $label
     * @param $fields
     * @return Column
     */
    public static function build ($name, $label, Field ...$fields)
    {
        $column = new static($name, $label);
        foreach ($fields as $field) {
            $column->addField($field);
        }
        return $column;
    }

    /**
     * @param Field $field
     * @param $label
     * @return static
     */
    public static function import(Field $field, $label = NULL)
    {
        if($label === NULL)
            $label = $field->getLabel();
        /** @var Bit|Field $field */
        if($field->getClass() === 'Bit')
            $column = new static ($field->getBits()->getName().".".$field->getName(), $label);
        else
            $column = new static ($field->getName(), $label);
        $column->addField($field);
        return $column;
    }

    /**
     * @param Field $field
     * @return Column
     */
    private function addField(Field $field)
    {
        if (!in_array($field, $this->fields)) {
            $this->fields[] = $field;
        }
        return $this;
    }

    /**
     * The handler is the function for compiling the available data and returning a response.  If it isn't specified,
     * the Column will return the data available from the Field->get() method.  For multiple columns, it will separate
     * the responses with a space.
     * @param $handler
     * @return Column
     */
    public function addHandler($handler)
    {
        $this->handler = $handler;
        return $this;
    }

    /**
     * @param StandardView $view
     * @return Column
     */
    public function setView(StandardView $view)
    {
        $this->view = $view;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param boolean $default
     * @param ...$sortMethods
     * @return $this
     */
    public function setSort($default, ...$sortMethods)
    {
        $this->defaultSort = $default;
        $this->sort = true;
        if($sortMethods)
            $this->sortMethods = $sortMethods;
        return $this;
    }

    /**
     * @return string
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @return bool
     */
    public function getDefaultSort()
    {
        return $this->defaultSort;
    }

    /**
     * @return bool
     */
    public function getBucketBy()
    {
        return $this->bucketBy;
    }

    /**
     * @param $template
     * @param bool $useLoader
     * @return Column
     */
    public function setTemplate($template, $useLoader = false)
    {
        if ($useLoader) {
            global $twig_loader;
            $this->template = $twig_loader->getSource($template);
        } else {
            $this->template = $template;
        }
        return $this;
    }

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     * @return Column
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * @return Field[]|Entry[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @return Column
     */
    public function filterBySearch()
    {
        $this->filterBySearch = true;
        return $this;
    }

    /**
     * @param string $defaultOrder
     * @return $this
     */
    public function bucketBy($defaultOrder = '')
    {
        $this->defaultbucketBy = $defaultOrder;
        $this->bucketBy = true;
        return $this;
    }

    /**
     * @return bool
     */
    public function getFilterBySearch()
    {
        return $this->filterBySearch;
    }

    /**
     * ->FilterByValue([value=>Description, value=>description])
     * @param array $values
     * @return $this
     */
    public function filterByValue(Array $values)
    {
//        $feeScheduleFilter = [['option' => 'Active', 'sql' => "FeeTagCategories.status_0 & 0b01"],
//            ['option' => 'Active', 'sql' => "NOT FeeTagCategories.status_0 & 0b01"]];

        foreach($values as $value)
        $this->filterByValue[$value['option']] = $value['sql'];
        return $this;
    }

    /**
     * @return array
     */
    public function getFilterByValue()
    {
        return $this->filterByValue;
    }

    /**
     * @return array
     */
    public function getFilterByValueOptions()
    {
        $result = [];
        $filters = $this->filterByValue;
        foreach($filters as $key => $value){
            $result[] = $key;
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getFilterByValueSelected()
    {
        return $this->selectedFilterByValue;
    }

    /**
     * @return array
     */
    public function setFilterByValueSelected($selected)
    {
        $this->selectedFilterByValue = $selected;
        return $this;
    }

    /**
     * @return string
     */
    public function getFilterBySearchText()
    {
        return $this->filterBySearchText;
    }

    /**
     * @param $text
     * @return $this
     */
    public function setFilterBySearchText($text)
    {
        $this->filterBySearchText = $text;
        return $this;
    }

    /**
     * @param $selected
     * @return mixed
     */
    public function getFilterSql($selected)
    {
        return $this->filterByValue[$selected];
    }

    /**
     * @return array|string
     */
    public function getSortMethods()
    {
        if($this->sortMethods)
            return $this->sortMethods;
        else
            return [$this->getName()];
    }

    /**
     * @param $boolean boolean
     * @return $this
     */
    public function setIsLink($boolean)
    {
        $this->isLink = $boolean;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsLink()
    {
        return $this->isLink;
    }

    /**
     * @return bool
     */
    public function getDefaultBucketBy()
    {
        return $this->defaultbucketBy;
    }

}