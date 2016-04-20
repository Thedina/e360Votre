<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 8/19/2015
 * Time: 10:20 PM
 */

namespace eprocess360\v3core;


class DataSet extends \ArrayIterator
{

    protected $keydict;
    protected $model;
    protected $data;

    public function setKeydict(Keydict $keydict)
    {
        $this->keydict = $keydict;
    }

    public function setModel(Model $model)
    {
        $this->model = $model;
    }
}