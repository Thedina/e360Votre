<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 12/2/2015
 * Time: 2:05 PM
 */

namespace eprocess360\v3core\Controller;

/**
 * Class Children
 * @package eprocess360\v3core\Controller
 *
 * Allows a Controller to have child Controllers.  Gives access to the build method.
 */
trait Children
{

    protected $children = [];
    protected $childrenById = [];

    /**
     * Adds a child Controller to this (parent) Controller. Child Controller must have a Name.
     * @param Controller[] ...$objects
     * @return $this
     * @throws ControllerException
     */
    public function addController(Controller ...$objects)
    {
        $save = false;
        /** @var Controller|Children $this */
        /** @var Controller $object */
        foreach ($objects as $k=>$object) {
           
            $this->children[$object->getName()] = $object->setParent($this);
            $this->childrenById[$object->getId()] = $object;
            /** @var Persistent|Controller $object */
            if ($object->uses('Persistent') && !$object->getStatus()) {
                $object->commission();
                $object->setStatus(true);
            }
        }
        if ($save) {
            die("==da");
            // we only save if we absolutely have to
            $this->controllerTable->update();
        }
        return $this;
    }

    /**
     * Get a child Controller using it's idController.
     * @param $id
     * @return Module
     * @throws ControllerException
     * @throws \Exception
     */
    public function getChildById($id)
    {
        if (isset($this->childrenById[$id])) {
            return $this->childrenById[$id];
        }
        throw new ControllerException("Child with Id of {$id} does not exist.");
    }

    /**
     * Gets a child Controller by $name of the child.
     * @param $name
     * @param bool|true $init
     * @return Controller|Module
     * @throws \Exception
     */
    public function getChild($name, $init = true, $exception = true)
    {
        if ($name && array_key_exists($name, $this->children)) {
            /** @var Controller|Module $module */
            $module = $this->children[$name];
            if($init)
                $module->ready()->init();
            else
                $module->ready();
            return $module;
        }
        if($exception)
            throw new \Exception("Module interface '{$name}' does not exist.");
        else
            return null;
    }

    /**
     * Returns a generator of Child Controllers which are of the specified $class.
     * @param $class
     * @return Controller
     * @throws \Exception
     */
    public function getChildByClass($class)
    {
        /** @var Module $interface */
        foreach ($this->children as $interface) {
            if ($interface instanceof $class) {
                /** @var Controller|Module $interface */
                yield $interface->ready()->init();
            }
        }
    }

    /**
     * Skeleton function to allow a place for controllers to build and add their child controllers.
     */
    public function buildChildren()
    {

    }
}