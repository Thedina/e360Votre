<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 12/10/15
 * Time: 10:29 AM
 */

namespace eprocess360\v3core\Controller;
use eprocess360\v3controllers\Group\Model\GroupProjects;
use eprocess360\v3controllers\Group\Model\GroupUsers;
use eprocess360\v3core\Controller\Identifier\Identifier;
use eprocess360\v3core\Controller\State\State;
use eprocess360\v3core\DB;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model\Controllers;
use eprocess360\v3core\Model\ProjectControllers;
use eprocess360\v3core\Model\Projects;
use Exception;

/**
 * Class Project
 * @package eprocess360\v3core\Controller\Project
 */
trait Project
{
    protected $projectData;
    protected $projectStates = [];
    /** @var Table */
    protected $keydict;

    /**
     * Gets the Controller of a Project by idProject, also loads up the data into projectData.
     * @param $idProject
     * @return Controllers
     * @throws Exception
     */
    public static function getProjectControllers()
    {
        $sql = "SELECT * FROM ProjectControllers LEFT JOIN Controllers ON ProjectControllers.idController = Controllers.idController";
        $controllers = DB::sql($sql);
        return $controllers;
    }

    /**
     * Gets the Controller of a Project by idProject, also loads up the data into projectData.
     * @param $idProject
     * @return Controllers
     * @throws Exception
     */
    public static function getProjectControllerByIdProject($idProject)
    {
        $projectTable = Projects::sqlFetch($idProject);
        /** @var Table $controllerTable */
        $controllerTable = Controllers::join(ProjectControllers::keydict())->fetchId($projectTable->idController->get());
        if (!($controllerTable->status->isActive->get())) {
            throw new Exception("Controller '{$controllerTable->title->get()}' is currently unavailable.");
        }
        $class = $controllerTable->class->get();
       
        $controllerNamespace = $controllerTable->status->isAbnormalNamespace->get()? '' : 'App\\'.$class.'\\';
        /** @var Controller|Project $controllerName */
        $controllerName = $controllerNamespace . $class;
        
        /** @var Controller|Project $controller */
        $controller = $controllerName::build($projectTable->idController->get());
        $controller->projectControllerTableCreate($controllerTable, $projectTable);

        return $controller;
    }

    /**
     * Gets a Project Controller by the idController of the Controller.
     * @param $idController
     * @return static
     * @throws Exception
     */
    public static function getProjectControllerByIdController($idController)
    {
        /** @var Controllers $controller */
        $controller = Identifier::get()->byId($idController);
        if (!($controller->status->isActive->get())) {
            throw new Exception("Controller '{$controller->title->get()}' is currently unavailable.");
        }
        $class = $controller->class->get();
        $controllerNamespace = $controller->status->isAbnormalNamespace->get()? '' : 'App\\'.$class.'\\';
        /** @var Controller $controllerName */
        $controllerName = $controllerNamespace . $class;
        return $controllerName::build($controller->idController->get());
    }

    /**
     * Create a new project with the specified Controller configuration
     * @param int $idController
     * @return Table
     * @throws Exception
     */
    public static function createProject($idController)
    {
        /** @var Table $controllerTable */
        $controllerTable = Controllers::join($c=ProjectControllers::keydict())->fetchId($idController);
        if (!($controllerTable->status->isActive->get())) {
            throw new Exception("Controller '{$controllerTable->title->get()}' is currently unavailable.");
        }
        $class = $controllerTable->class->get();
        $controllerNamespace = $controllerTable->status->isAbnormalNamespace->get()? '' : 'App\\'.$class.'\\';
        /** @var Controller $controllerName */
        $controllerName = $controllerNamespace . $class;
        if(get_called_class() !== $controllerName) {
            /** @var Project|Controller $controllerName */
            return $controllerName::createProject($idController);
        }

        $projectTable = Projects::keydict();
        $projectTable->title->set('New '. $controllerTable->title->get());
        $projectTable->description->set('Created on '. date('m-d-Y'));
        $projectTable->state->set(0);
        $projectTable->idController->set($idController);
        $projectTable->insert();
        /** @var Controller|Project $controller*/
        $controller = new $controllerName();
        $controller->projectControllerTableCreate($controllerTable, $projectTable);
//        /** @var Controller $controller */
//        $controller->trigger(Event::build('onProjectCreate'));
        return $projectTable;
    }

    /**
     * Assembles the Controller with a valid Controller config and Project.  Right now using the Model\Table method of
     * passing data, however this will change in the future to something less massive.
     * @param Table $controller
     * @param Table $project
     */
    public function projectControllerTableCreate(Table $controller, Table $project)
    {
        $name = explode('\\',get_called_class());
        $name = array_pop($name);
        $tableName = "controller_{$name}{$project->idController->get()}_project_data";
        if (!$controller) return;
        if ($project) {
            $this->restore($tableName, $project->idProject->get());
        }
    }

    /**
     * @param $idProject
     * @throws ControllerException
     * @throws Exception
     */
    public function loadProjectData($idProject)
    {
        /**
         * @var Controller|Project $this
         */
        $name = $this->getName();
        $tableName = "controller_{$name}{$this->getId()}_project_data";

        $this->restore($tableName, $idProject);
    }

    /**
     * Load the complete Project data from the table for the Project/Controller.  If the row is missing from the table,
     * this function will store a row with the default values at the time restore is run.
     * @return $this
     * @throws Exception
     * @throws \Exception
     */
    protected function restore($tableName, $idProject)
    {
        /** @var Controller|Project $this */
        if (!$idProject) {
            throw new \Exception("Cannot restore data for an unspecified project ID.");
        }
        $sql = "SELECT * FROM {$tableName} WHERE idproject = {$idProject}";
        $results = DB::sql($sql);
        if (!is_array($results) || sizeof($results) == 0) {
            $keydict = $this->getKeydict();
            $keydict->idProject->set($idProject);
            $keydict->setTableName($tableName);
            $keydict->insert();
            return $this;
        }
        $this->getKeydict()->wakeup($results[0]);
        return $this;
    }


    /**
     * Returns a view specification for the /projects/controllers/1# page
     * @param $table
     * @return mixed
     */
    public function view($table)
    {
        return NULL;
    }

    /**
     * Get the Keydict and load it if necessary
     * @param bool $complete
     * @return Table
     * @throws Exception
     */
    public function getKeydict($complete = false)
    {
        if (!$this->keydict) {
            $this->keydict = $this->keydict();
            // for each loaded module we will let the module modify the keydict if the request is for complete
        }
        $this->keydict->setTableName($this->getTableName());
        if (!($this->keydict instanceof Table)) {
            throw new Exception("The Controller Keydict must be an instance of Table.");
        }
        return $this->keydict;
    }

    /**
     * Returns this Controller's custom keydict.
     * @return Table
     */
    public function keydict()
    {
        return new Table();
    }

    /**
     * Create a table from the specification
     */
    public function createTable()
    {
        $keydict = $this->getKeydict();
        $columns = $keydict->getSpecification();
        $out = [];
        foreach ($columns as $column=>$data) {
            $out[] = "`{$column}` {$data['type']}".(array_key_exists('length', $data) && $data['length'] ? "({$data['length']})" : '').(array_key_exists('unsigned', $data)?' UNSIGNED':'').(array_key_exists('primary_key', $data)?' PRIMARY KEY':'').(array_key_exists('auto_increment', $data)?' AUTO_INCREMENT':'');
        }
        $out = implode(', ', $out);
        $sql = "CREATE TABLE `{$this->getTableName()}` ({$out}) CHAR SET latin1";
        DB::sql($sql);
    }

    /**
     * Get the name of the table that project data for this controller is stored in
     *
     * @return string
     */
    public function getTableName()
    {
        $name = explode('\\',get_called_class());
        $name = array_pop($name);
        /** @var Controller $this */
        return "controller_{$name}{$this->getId()}_project_data";
    }

    /**
     * Get the name of the table that project data for this controller is stored in
     *
     * @return string
     */
    public static function constructTableName($class, $idController)
    {
        return "controller_{$class}{$idController}_project_data";
    }

    /**
     * Gets current project ID from the Project Controller
     * @return mixed
     * @throws \eprocess360\v3core\Controller\Exception
     */
    public function getIdProject()
    {
        /** @var Controller $this */
        return $this->getObjectId();
    }

    /**
     * Gets the Project Data, if not available loads it.
     * TODO Should be combined/removed with accordance to the data in persistent
     * @return Table|Keydict
     */
    public function getProjectData()
    {
        if (!$this->projectData) {
            $this->projectLoadData();
        }
        return $this->projectData;
    }

    /**
     * Loads the data from the Projects table
     * @throws ControllerException
     * @throws Exception
     */
    public function projectLoadData()
    {
        /** @var Controller|Project $this */
        if (!$this->getObjectId()) {
            throw new Exception("No project loaded, cannot load from Projects table.");
        }
        $this->projectData = Projects::sqlFetch($this->getObjectId());
    }

    /**
     * returns the state of the Project.
     * @return State
     * @throws Exception
     */
    public function getProjectState()
    {
        /** @var Controller|Project|Persistent|Children $this */
        if ($this->hasObjectId()) {
            $state = $this->getProjectData()->state->get();
            if (isset($this->projectStates[$state])) {
                return $this->projectStates[$state];
            }
        }
        return State::build(null);
    }

    /**
     * Add additional available States to the Project.
     * @param State\State[] ...$states
     * @return $this
     */
    public function addProjectStates(State ...$states)
    {
        /** @var State $state */
        foreach ($states as $state) {
            if (!in_array($state, $this->projectStates)) {
                $this->projectStates[$state->getName()] = $state;
                $state->setController($this);
            }

        }
        return $this;
    }

    /**
     * Get the State object for a given name.
     *
     * @param $stateName
     * @return State
     * @throws Exception
     */
    public function getProjectStateByName($stateName)
    {
        if (isset($this->projectStates[$stateName])) {
            return $this->projectStates[$stateName];
        }
        throw new Exception("State {$stateName} unavailable.");
    }

    /**
     * Get the State object for a given name.
     * @param $stateName
     * @return State
     * @throws Exception
     */
    public function state($stateName)
    {
        return $this->getProjectStateByName($stateName);
    }

    /**
     * Get all the available states for the project.
     * @return State[]
     */
    public function getProjectStates()
    {
        return $this->projectStates;
    }

    /**
     * Determines whether a project as States
     * @return bool
     */
    public function hasProjectStates()
    {
        return (bool)sizeof($this->projectStates);
    }

    /**
     * Set the current project state.
     * @param string $stateName
     * @throws Exception
     */
    public function setProjectState($stateName)
    {
        if (isset($this->projectStates[$stateName])) {
            $oldState = $this->getProjectData()->state->get();
            if (isset($this->projectStates[$oldState])) $this->state($oldState)->trigger('onExit');
            $this->getProjectData()->state->set($stateName);
            $this->getProjectData()->update();
            $this->state($stateName)->trigger('onEnter');
            $this->stateRun(); // run the new state
        } else {
            throw new Exception("State {$stateName} unavailable.");
        }
    }

    /**
     * Runs a Project State
     */
    public function stateRun()
    {
        if (method_exists($this, $method='state'.($this->getProjectState()->getInternalName()?:'Null'))) {
            $this->$method();
        }
    }

    /**
     * Trigger that is fired when a Project is created
     * @param \Closure $closure
     * @return Trigger\Trigger
     */
    public function onProjectCreate(\Closure $closure)
    {
        /** @var Triggers|Controller|Rules $this */
        return $this->addTrigger(__FUNCTION__, $closure);
    }

    /**
     * Save the keydict into the database
     */
    public function save()
    {
        $this->keydict->update();
    }

    /**
     * @return array
     */
    public static function getWorkflowClasses()
    {
        $result = [];
        foreach(glob(APP_PATH."/app/*/*.php") as $file) {
            include($file);
        }
        foreach(get_declared_classes() as $className) {
            if(strpos($className, "App\\") === 0 && strpos($className, "App\\System") !== 0) {
                $result[] = substr($className, strrpos($className, "\\")+1);
            }
        }
        return $result;
    }

    /**
     * @param $class
     * @param $title
     * @param $description
     * @param $isActive
     * @param $isAllCreate
     * @param $idGroups
     */
    public static function createWorkflow($class, $title, $description, $isActive, $isAllCreate, $idGroups)
    {
        /** @var Controller $classPath */
        $classPath = 'App\\'.$class.'\\'.$class;

        if($isActive && $isAllCreate)
            $status = Controllers::STATUS_UP;
        else if($isActive)
            $status = Controllers::STATUS_DOWN;
        else
            $status = Controllers::STATUS_DELETED;

        $idController = $classPath::register($title, $description, $status);
        foreach($idGroups as $idGroup)
            GroupProjects::create($idGroup, $idController);
    }

    /**
     * @param $idController
     * @param $title
     * @param $description
     * @param $isActive
     * @param $isAllCreate
     * @param $idGroups
     * @return array
     * @throws Exception
     * @throws Keydict\Exception\InvalidValueException
     * @throws Keydict\Exception\KeydictException
     */
    public static function editWorkflow($idController, $title, $description, $isActive, $isAllCreate, $idGroups)
    {
        /** @var Table|Controllers|ProjectControllers $controller */
        $controller = Controllers::join(ProjectControllers::keydict())->fetchId($idController);

        if($title !== NULL)
            $controller->title->set($title);
        if($description !== NULL)
            $controller->description->set($description);
        if($isActive !== NULL)
            $controller->status->isActive->set($isActive);
        if($isAllCreate !== NULL)
            $controller->status->isAllCreate->set($isAllCreate);

        $sql = "SELECT GroupProjects.* FROM GroupProjects WHERE idController = {$idController}";
        $groupProjects = DB::sql($sql);
        foreach($groupProjects as $groupProject){
            /** @var GroupProjects $data */
            $data = GroupProjects::keydict()->wakeup($groupProject);
            if(isset($idGroups[$data->idGroupProject->get()]))
                unset($idGroups[$data->idGroupProject->get()]);
            else if(isset($idGroups[$data->idGroupProject->get()]))
                unset($idGroups[$data->idGroupProject->get()]);
        }
        foreach($idGroups as $key=>$value)
            GroupProjects::create($key,$idController);

        $controller->update();

        return $controller->toArray();
    }

    /**
     * @param $idController
     * @throws Exception
     */
    public static function deleteWorkflow($idController)
    {

        $sql = "SELECT GroupProjects.idGroupProject FROM GroupProjects WHERE idController = {$idController}";
        $groupProjects = DB::sql($sql);

        foreach ($groupProjects as $groupProject) {
            if(isset($groupProject['idGroupProject']))
                GroupProjects::deleteById($groupProject['idGroupProject']);
        }

        Controllers::deleteById($idController);
    }

    /**
     * @param $idController
     * @return bool
     * @throws Exception
     */
    public static function checkPermission($idController)
    {
        global $pool;
        /** @var Controllers $controller */
        $controller = Identifier::get()->byId($idController);
        if($controller->status->isAllCreate->get())
            return true;

        $sql = "SELECT GroupProjects.* FROM GroupProjects WHERE idController = {$idController}";
        $groupProjects = DB::sql($sql);
        $groups = GroupUsers::getGroupsByUserId($pool->User->getIdUser());
        foreach($groupProjects as $groupProject){
            if(isset($groups[(int)$groupProject['idGroup']]))
                return true;
        }
        throw new Exception("User does not have permissions to create a  new project on this Workflow.");
    }
}