<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 12/4/2015
 * Time: 1:10 PM
 */

namespace eprocess360\v3core\Controller;
use eprocess360\v3core\Controller\Identifier\Identifier;
use const eprocess360\v3core\CONTROLLER_ID_ALLOCATION;
use const eprocess360\v3core\CONTROLLER_ID_ANONYMOUS_ALLOCATION;
use eprocess360\v3core\DB;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Entry;
use eprocess360\v3core\Model\Controllers;
use eprocess360\v3core\Model\ProjectControllers;
use Exception;

/**
 * Class Persistent
 * @package eprocess360\v3core\Controller
 *
 * Allows a Controller to have a status and permanent ID.  ID are matched using the routing path.
 */
trait Persistent
{
    private $data = null;
    private $status = false;
    private $statusInit = false;

    /**
     * Gets the commissioned status of this Module, if not commissioned, it will do so.
     * @return mixed
     */
    public function getStatus()
    {
        if (!$this->isStatusInit()) $this->persistenceWakeup();
        return $this->status;
    }

    /**
     * Updates the status of this Persistent Controller.
     * @param boolean $status
     */
    public function setStatus($status)
    {
        $this->status = (bool)$status;
        /** @var Controller|Persistent $this */
        // update the Controller record
        if($this->controllerData()->status->isActive->get() != $status) {
            $sql = "UPDATE Controllers SET status_0 = " . ($status ? 1 : 0) . " WHERE idController = {$this->getId()}";
            DB::sql($sql);
        }
        $this->status = $status;
    }

    /**
     * Returns the statusInit variable, which determines if the status has been Initialized (if the controller has been commissioned)
     * @return boolean
     */
    public function isStatusInit()
    {
        return $this->statusInit;
    }

    /**
     * Firsts checks for/will commission the Controller, then returns the data on this controller.
     * @return Keydict|Entry
     * TODO This data isn't actually being used as intended, and coudld clash with other 'data' in the system, such as in Project.
     */
    public function controllerData()
    {
        if (!$this->isStatusInit()) $this->persistenceWakeup();
        return $this->data;
    }

    /**
     * Wakes up the Object and loads project data if avaialble, IF the controller is not registered, will commission.
     */
    private function persistenceWakeup()
    {
        /** @var Controller|Persistent $this */
        try {
            if ($this->uses('Project')){
                $this->data = Controllers::join($c=ProjectControllers::keydict())->fetchId($this->getId());
            }
            else{
                $this->data = Identifier::get()->byId($this->getId());
                if(!$this->data)
                    throw new Exception;
            }
        } catch (\Exception $e) {
            // This controller is not registered
            $this->commission();
            $this->setStatus(true);
            $this->persistenceWakeup();
        }
        $this->statusInit = true;
        $this->setStatus($this->controllerData()->status->isActive->get());
    }

    /**
     * Things that need to happen before a Controller status is true and an ID is assigned
     */
    public function commission()
    {

    }

    /**
     * Things that need to happen to make a Controller status false.  Should set status false.
     */
    public function decommission()
    {

    }

    /**
     * Persistent build used by Controllers that are not Modules, but are persistent.
     * TODO Combine this with Module's Build to have one central build in Persitent that checks for Module and does the work of both as to avoid duplicate code.
     * @param null $name
     * @param null $parent
     * @param null $id
     * @param null $objectId
     * @return static
     * @throws ControllerException
     */
    public static function persistentBuild($name = null, $parent = null, $id = null, $objectId = null)
    {
        /** @var Controller $controller */
        $controller = new static();
        $controller->setName($name);
        $controller->setParent($parent);
        $controller->setId($id);
        $controller->setObjectId($objectId);

        return $controller;
    }

}