<?php

namespace eprocess360\v3core\Controller\Identifier;

use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Module;
use eprocess360\v3core\Controller\Persistent;
use eprocess360\v3core\DB;
use eprocess360\v3core\Model\Controllers;

/**
 * Class Identifier
 * @package eprocess360\v3core\Controller\Identifier
 *
 * Allows for the automatic identification of Controllers.  Used by Persistent Controllers.
 */
class Identifier
{
    private static $instance;
    private $table = [];
    private $reverse = [];

    /**
     * Identifier constructor.
     */
    protected function __construct()
    {
        $data = DB::sql("SELECT * FROM Controllers");
        foreach ($data as $row) {
            $row['idController'] = (int)$row['idController'];
            $row['path'] = stripslashes($row['path']);
            $this->table[$row['idController']] = $row;
            $this->reverse[$row['path']] = $row['idController'];
        }
    }

    /**
     * @return Identifier
     */
    public static function get()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * @param $id
     * @return null
     */
    public function byId($id)
    {
        $keydict = Controllers::keydict();
        return isset($this->table[$id])?$keydict->wakeup($this->table[$id]):null;
    }

    /**
     * @param $path
     * @return null
     */
    public function byPath($path)
    {
        return isset($this->reverse[$path])?$this->table[$this->reverse[$path]]['idController']:null;
    }

    /**
     * @param Controller $controller
     * @return int
     * @throws \Exception
     * @throws \eprocess360\v3core\Controller\ControllerException
     */
    public function make(Controller $controller)
    {
        if($controller->uses('Module')){
            /** @var Module|Controller $controller */
            $data = [
                'path'=>$controller->getModulePath(),
            ];
        }
        else {
            $data = [
                'path' => $controller->getPath(),
            ];
        }
        $path = DB::cleanse($data['path']);
        $class = DB::cleanse($controller->getClass());
        $status = (int)$controller->uses('Persistent'); // If the Controller uses Persistent, it is commissioned
        DB::sql("INSERT INTO Controllers (class, title, path, status_0)
                 VALUES ('{$class}', '{$controller->getName()}', '{$path}', {$status})");
        $data['idController'] = (int)DB::iid();
        $this->addController($data);
        if($status){
            /** @var Persistent $controller */
            $controller->commission();
        }
        return $data['idController'];
    }

    /**
     * @param $data
     */
    private function addController($data)
    {
        $this->table[$data['idController']] = $data;
        $this->reverse[$data['path']] = $data['idController'];
    }
}