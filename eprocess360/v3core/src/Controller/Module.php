<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 12/10/15
 * Time: 11:05 AM
 */

namespace eprocess360\v3core\Controller;
use eprocess360\v3modules\Toolbar\Toolbar;

/**
 * Class Module
 * @package eprocess360\v3core\Controller\Module
 */
trait Module
{
    protected $moduleInitName = false;
    protected $moduleCommissionName = false;

    /**
     * Module specific Build, which builds the Controller, DB insert Controller object with path formed by $name and $parent, and inits the Module.
     * @param null $name
     * @param null $parent
     * @param null $id
     * @param null $objectId
     * @return static
     * @throws ControllerException
     */
    public static function build($name = null, $parent = null, $id = null, $objectId = null)
    {
        /** @var Controller|Module $controller */
        $controller = new static();
        $controller->setName($name);
        $controller->setParent($parent);
        $controller->setId($id);
        $controller->setObjectId($objectId);
        $controller->traitModuleReady();

        return $controller;
    }

    /**
     * Readies the Module trait and if available, calls the parent defined init function for this Module.
     * @throws ControllerException
     */
    public function traitModuleReady()
    {
//        if ($this->moduleInitName) return;
//        /** @var Controller|Module $this */
//        $name = $this->getName();
//        $this->moduleInitName = 'init'.strtoupper(substr($name,0,1)).substr($name,1);
//        if (method_exists($this->getClosest('Project'), $this->moduleInitName)) {
//            $this->getClosest('Project')->{$this->moduleInitName}($this);
//        }
    }

    /**
     * Commission function for Module, which also calls the parent defined commission function for this Module if available.
     * @throws ControllerException
     */
    public function commissionModule()
    {
        if ($this->moduleCommissionName) return;
        /** @var Controller|Module $this */
        $name = $this->getName();
        $this->moduleCommissionName = 'commission'.strtoupper(substr($name,0,1)).substr($name,1);
        if (method_exists($this->getClosest('Project'), $this->moduleCommissionName)) {
            $this->getClosest('Project')->{$this->moduleCommissionName}($this);
        }
    }

    /**
     * Gets the Module Path, which is the path of a unique module on top of a controller. This is so we don't create a new DB Obj with every Project on a Controller.
     * @param bool|true $withSlash
     * @return array|string
     * @throws ControllerException
     */
    public function getModulePath($withSlash = true)
    {
        /** @var Controller $this */
        $name = $this->getName();
        $controllerId = $this->getClosest('Project')->getId();
        $path = "/controllers/{$controllerId}/modules/{$name}";
        return $path;
    }

    /**
     * Sets the data variable. The use case of this has data being a Table.
     * @param array $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Verifies the Privilege against the Module against the closest Warden
     * @param $privilege
     */
    final public function verifyPrivilege($privilege)
    {
        /** @var Controller $this */
        $warden = $this->getClosest('Warden');
        /** @var Controller|Warden $warden */
        $warden->verifyPrivilege($privilege, $this);
    }

    /**
     * Creates a Toolbar Link, and if isActive a Progress bar, for the given Module.
     * @param Toolbar $toolbar
     * @param $isActive
     * @param bool|true $isAvailable
     */
    public function createModuleToolbar(Toolbar $toolbar, $isActive, $isAvailable = true){
        /** @var Controller|Module $this */
        if($this->uses('ProjectToolbar')){
                $this->moduleToolbar($toolbar, $isActive, $isAvailable);
        }
    }

    /**
     * Creates a Toolbar Link for the given Module. Can be over written to define custom needs.
     * @param Toolbar $toolbar
     * @param $isActive
     * @param $isAvailable
     */
    public function moduleToolbar(Toolbar $toolbar, $isActive, $isAvailable)
    {
        /** @var Controller|Module $this */
        $toolbar->addToolbarLink($this->getDescription(), $this->getPath(), $isActive, $isAvailable);
    }

    /**
     * Creates a Progress bar for the given Module. Can be over written to define custom needs.
     * @param Toolbar $toolbar
     */
    public function moduleProgressBar(Toolbar $toolbar)
    {
        /** @var Controller|Module $this */
        $toolbar->setToolbarProgress($this->getDescription(), 0, $this->getPath(), "view");
    }

}