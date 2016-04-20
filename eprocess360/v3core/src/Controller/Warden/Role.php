<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 8/7/2015
 * Time: 8:34 PM
 */

namespace eprocess360\v3core\Controller\Warden;

/**
 * Class Role
 * Available for Controllers so they can specify their own roles
 * @package eprocess360\v3core\Roles
 */
class Role
{
    private $id;
    private $name;
    private $controller;

    /**
     * @param $id
     * @param $name
     */
    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * @param $id
     * @param $name
     * @return static
     */
    public static function build($id, $name)
    {
        return new static($id, $name);
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    public function setController($controller)
    {
        $this->controller = $controller;
    }

    /**
     * @return mixed
     */
    public function getController()
    {
        return $this->controller;
    }

}