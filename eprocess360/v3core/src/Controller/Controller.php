<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 12/2/2015
 * Time: 10:02 AM
 */

namespace eprocess360\v3core\Controller;
use const eprocess360\v3core\CONTROLLER_ID_ALLOCATION;
use const eprocess360\v3core\CONTROLLER_ID_ANONYMOUS_ALLOCATION;
use const eprocess360\v3core\CONTROLLER_ID_DATABASE_AUTOINDEX;
use eprocess360\v3core\Controller\Identifier\Identifier;
use eprocess360\v3core\Controller\Trigger\InterfaceTriggers;
use eprocess360\v3core\DB;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\MailManager;
use eprocess360\v3core\Model\Controllers;
use eprocess360\v3core\Model\ProjectControllers;
use eprocess360\v3core\Request\Request;
use eprocess360\v3core\Toolbox;

/**
 * The base Controller class that everything is extended from.  Has a basic set of functions to allow traversal of the
 * Controller hierarchy.  By default this class doesn't know it has Models, Trigger, etc.  By default the idController
 * is determined by the bootstrap files or the place the Controller was instantiated.
 * Class Controller
 * @package eprocess360\v3core\Controller
 */
class Controller
{

    const READ_ALL = false;
    protected $id = 0;
    protected $parent = null;
    protected $name = false;
    protected $description = null;
    protected $objectId = 0;
    protected $ready;
    protected $initName = false;
    protected $init = false;

    /**
     * Build function for a Controller. If the Controller uses Persistent or Module, use their builds instead.
     * @param null $id
     * @param null $objectId
     * @return static
     * @throws ControllerException
     */
    public static function build($id = null, $objectId = null)
    {
        $controller = new static();
        $controller->setId($id);
        $controller->setObjectId($objectId);
        return $controller;
    }



    /**
     * Init function that runs a Dependency Check, as well as the parent specified Init and Trigger build.
     */
    public function init()
    {
        //TODO This If Statement was placed here because Auth redirection to Forbidden Page occurs AFTER the Init in Routing. As such, Auth redirection needs to be moved to a place so that it occurs Immediately after the Auth has Failed.
        if (!Request::get()->isAuthRequired()) {
            if ($this->initName)
                return $this;
            /** @var Controller|Module $this */
            $name = $this->getName();
            $this->initName = 'init' . strtoupper(substr($name, 0, 1)) . substr($name, 1);
            $triggerName = 'trigger' . strtoupper(substr($name, 0, 1)) . substr($name, 1);
            $this->init = true; //todo dynamically declared
            if (method_exists($this->getParent(), $this->initName)) {
                $this->getParent()->{$this->initName}($this);
            }
            if (method_exists($this->getParent(), $triggerName)) {
                $this->getParent()->{$triggerName}($this);
            }
            $this->dependencyCheck();
        }
        return $this;
    }

    /**
     * These things happen before main() is allowed to run, or the object is returned for use.  Return self
     */
    public function ready()
    {
        if(!$this->ready)
            foreach ($this->class_uses($this) as $trait) {
                $trait = substr($trait,strrpos($trait,'\\')+1);
                $method = "trait{$trait}Ready";
                if (method_exists($this, $method)) {
                    call_user_func([$this, $method]);
                }
            }
        $this->ready = true;
        return $this;
    }

    /**
     * Main run function, which routes the request to this controller, and if ResponseHandler, renders the page.
     * @throws ControllerException
     */
    public function run()
    {
        if (!$this->isReady()) throw new ControllerException("{$this->getClass()} is not ready.");
        /** @var Controller|Router|ResponseHandler $this */
        $requestHandler = Request::get()->getRequestHandler()?:$this;

        Request::get()->setResponder($this);
        $requestHandler->route(Request::get());
        if ($this->uses('ResponseHandler')) {
            $this->render();
        }
    }

    /**
     * Returns the Path of of the Controller, which is formed by a controller's Name, idObject, and their parent Controllers. ex:
     * /controllers/2052/projects/4/phases/12, '/' is the parent of 'controllers' who has a idObject of 2052, which is the parent of 'projects'...
     * @param bool|true $withSlash
     * @param bool|true $includeObjectId
     * @return array|string
     * @throws ControllerException
     */
    public function getPath($withSlash = true, $includeObjectId = true)
    {
        if ($this->hasObjectId() && $includeObjectId) {
            $path = [$this->getObjectId()];
            array_unshift($path, $this->getName());
        }
        else {
            $path = [$this->getName()];
        }
        $cursor = $this;
        /** @var Controller $cursor */
        while (($cursor = $cursor->getParent()) && $cursor->hasName()) {
            if ($cursor->hasObjectId()) {
                array_unshift($path, $cursor->getObjectId());
            }
            array_unshift($path, $cursor->getName());
        }
        $path = ($withSlash?'/':'').implode('/',$path);
        return $path;
    }

    /**
     * Returns the path of a controller's static directory.
     * @return string
     */
    public function getStaticPath()
    {
        $class = $this->getClass(true);
        $pos = strpos($class, '\\', strpos($class, '\\')+1);
        $classPrefix = substr($class, 0, $pos);
        $classSuffix = substr($class, $pos, $pos-strrpos($class, '\\', 1));
        $class = $classPrefix . '\\src' . $classSuffix;
        return '/'.str_replace('\\', '/', $class) . '/static';
    }

    public function getPresetPath() {
        // TODO just do this once at init and save it?
        $ref = new \ReflectionClass(get_class($this));
        return dirname($ref->getFileName()).'/preset';
    }

    /**
     * Determine if this Controller uses a particular trait.
     * @param $trait
     * @return bool
     */
    public function uses($trait)
    {
        if (!strpos('\\',$trait)) {
            $trait = __NAMESPACE__ . '\\' . $trait;
        }
        return (bool)array_search($trait, self::class_uses($this));
    }

    /**
     * Returns the closest Controller that uses the ResponseHandler Trait.
     * @return Children|Controller|ResponseHandler
     * @throws ControllerException
     */
    public function getResponseHandler()
    {
        /** @var Controller|Router|Children $this */
        /** @var Controller|ResponseHandler|Children $responseHandler */
        $responseHandler = $this->getClosest('ResponseHandler');
        return $responseHandler;
    }

    /**
     * Find the closest Controller that matches $query.  Query can be the name of a Trait, or it can be a Closure that
     * accepts a Controller as its first argument.
     * @param $query
     * @param bool $ignoreSelf
     * @param bool $throwException
     * @return bool|Controller|Project|null
     * @throws ControllerException
     */
    public function getClosest($query, $ignoreSelf = false, $throwException = true)
    {
        $callable = null;
        if (!is_callable($query)) {
            if (is_string($query)) {
                if (!strpos($query, '\\')) $query = __NAMESPACE__ .'\\'. $query;
                $callable = function ($cursor) use ($query) {
                    return array_key_exists($query, Controller::class_uses($cursor)) ? $cursor : null;
                };
            }
        } else {
            $callable = $query;
        }
        $cursor = $this;

        if ($callable === null) {
            if ($throwException) {
                throw new ControllerException("Cannot find Controller using {$query}.");
            } else {
                return null;
            }
        }
        // Match if we are allowed to check $this for meeting the criteria
        if (!$ignoreSelf && $callable($cursor))  {
            return $cursor;
        }
        while (($cursor = $cursor->getParent()) && !$callable($cursor)) {}
        if ($cursor===null && $throwException) {
            throw new ControllerException("Cannot find Controller using {$query}.");
        }
        return $cursor;
    }

    /**
     * Returns an array of traits that the $cursor uses.
     * @param $cursor
     * @return array
     */
    public static function class_uses($cursor)
    {
        $traits = [];
        do {
            $traits = array_merge(class_uses($cursor, true), $traits);
        } while($cursor = get_parent_class($cursor));
        foreach ($traits as $trait => $same) {
            $traits = array_merge(class_uses($trait, true), $traits);
        }
        return array_unique($traits);
    }

    /**
     * Gets the parent of this Controller, if none returns null.
     * @return null|Controller
     */
    public function getParent()
    {
        if (is_object($this->parent)) {
            return $this->parent;
        }
        return null;
    }

    /**
     * Gets the Name of the Controller, if no name an exception is thrown.
     * @return null|string
     * @throws ControllerException
     */
    public function getName()
    {
        if ($this->hasName()) {
            return $this->name;
        }
        throw new ControllerException("Trying to get Name from Controller with no Name.");
    }

    /**
     * Gets the Class of this Controller.
     * @param bool|false $full
     * @return string
     */
    public function getClass($full = false)
    {
        $class = get_called_class();
        if (!strpos(' '.$class,'eprocess360')) {
            $class = get_parent_class($this);
        }
        return $full?$class:substr($class, strrpos($class,'\\')+1);
    }

    /**
     * Gets the Class of this Controller, statically.
     * @param bool|false $full
     * @return string
     */
    public static function getStaticClass($full = false)
    {
        $class = get_called_class();
        if (!strpos(' '.$class,'eprocess360') && !strpos(' '.$class,'App')) {
            $class = get_parent_class(new static);
        }
        return $full?$class:substr($class, strrpos($class,'\\')+1);
    }

    /**
     * Returns a boolean stating whether this controller has a name or not.
     * @return bool
     */
    public function hasName()
    {
        return (bool)$this->name;
    }

    /**
     * Returns a booleans stating if this Controller has an ObjectId.
     * @return bool
     */
    public function hasObjectId()
    {
        return (bool)$this->objectId;
    }

    /**
     * Gets the ObjectId of this Controller. If no ObjectId, an exception is thrown.
     * @return mixed
     * @throws ControllerException
     */
    public function getObjectId()
    {
        if ($this->hasObjectId()) {
            return $this->objectId;
        }
        throw new ControllerException("Trying to get Object ID from Controller with no Object ID.");
    }

    /**
     * Sets the ObjectId of this Controller.
     * @param mixed $objectId
     * @return $this
     */
    public function setObjectId($objectId)
    {
        $this->objectId = $objectId;
        return $this;
    }

    /**
     * Sets the parent Controller of this Controller.
     * @param Controller $parent
     * @return Controller
     */
    public function setParent(Controller $parent)
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * Gets the id of this Controller.
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Determines if the Controller is using an anonymous allocated id.
     * @return bool
     */
    public function isAnonymous()
    {
        return !($this->getId() > 0 && ($this->getId() <= CONTROLLER_ID_ALLOCATION || $this->getId() > CONTROLLER_ID_ANONYMOUS_ALLOCATION));
    }

    /**
     * Sets the id of this Controller. If no id given and the Controller is Persistent/Module, an DB Controller is created for them.
     * @param int $id
     * @return $this
     * @throws ControllerException
     */
    public function setId($id)
    {
        if ($id < 0) throw new ControllerException("Cannot assign negative Controller id.");
        if ($id && $id <= CONTROLLER_ID_ALLOCATION) {
            throw new ControllerException("Cannot hard assign Controller id lower than or equal to ".CONTROLLER_ID_ALLOCATION.".");
        }
        if (!$id && $this->uses('Module')) {
            /** @var Module|Controller $this */
            if (!$id = Identifier::get()->byPath($this->getModulePath())) {
                $id = Identifier::get()->make($this);
                $this->id = $id;
                $this->commissionModule();
            }
        }
        else if (!$id && $this->uses('Persistent')) {
            if (!$id = Identifier::get()->byPath($this->getPath())) {
                $id = Identifier::get()->make($this);
            }
        } else if (!($id && $id >= CONTROLLER_ID_DATABASE_AUTOINDEX)){
            global $autoAnonymousId;
            if (!isset($autoAnonymousId) || $autoAnonymousId === 0) $autoAnonymousId = CONTROLLER_ID_ALLOCATION + 1;
            $id = $autoAnonymousId++;
        }
        $this->id = $id;
        return $this;
    }

    /**
     * @return bool|Controller|Project|null
     * @throws ControllerException
     * TODO KIll this function or the interface it's duplicated in.
     */
    public function triggersDefaultRegistrar()
    {
        return $this->getClosest('TriggerRegistrar');
    }

    /**
     * Returns whether the Module has been readied or not.
     * @return bool
     */
    public function isReady()
    {
        return $this->ready;
    }

    /**
     * Sets the Name of the Controller.
     * @param String $name
     * @return Controller
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Creates a new DB Controller with the given Title and Description. If the Class uses Project, also create ProjectController.
     * @param $title
     * @param null $description
     * @param null $status
     * @return int
     * @throws ControllerException
     * @throws \Exception
     */
    public static function register($title, $description = NULL, $status = NULL)
    {
        global $pool;

        /** Verify User is an Admin */
        //TODO Look at this: $pool->User->verifyRole(Role::build(0,'admin','Admin', Role::ADMIN));

        /** Create a configuration in the database for the Controller */
        $controllerKeydict = Controllers::join($projectController=ProjectControllers::keydict());
        $name = DB::cleanse(self::getStaticClass());
        $title = $controllerKeydict->title->validate($title);
        //$status = ord($controllerKeydict->status->sleep()['status_0']);
        if($status === NULL)
            $status = Controllers::STATUS_UP;
        $title = DB::cleanse($title);
        $sql = "INSERT INTO Controllers (`class`, `title`, `status_0`) VALUES ('{$name}','{$title}', {$status})";
        $iid = DB::insert($sql);
        $path = "/controllers/{$iid}";
        $sql = "UPDATE Controllers SET `path` = '{$path}' WHERE `idController` = '{$iid}'";
        DB::sql($sql);

        $controller = new static();
        $controller->setName($name);
        $controller->setId($iid);

        if ($controller->uses('Project')) {

            /** Create a system User for the Controller */
            $domain = $pool->SysVar->get('siteUrl');
            $domain = substr($domain, strrpos($domain, '/')+1);
            while (!preg_match('/[0-9]+/', $salt=Toolbox::generateSalt())) {

            }

            $sql = "INSERT INTO ProjectControllers (`idController`, `description`) VALUES ({$iid}, '{$description}')";
            DB::insert($sql);
            /** Tell the Controller to run custom configuration scripts. The Controller is expected to make itself active */
            /** @var Project|Controller $controller */
            //$controller->finishRegistration();
            $controller->createTable();
        }

        if($controller->uses('Persistent')){
            /** @var Persistent $controller */
            $controller->commission();
        }

        return $iid;
    }

    /**
     * Gets the Description of this Controller.
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the Description of this Controller.
     * @param string $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Used as a fail-safe in Controllers to make sure that their dependencies and initializations are met; If not, exception is thrown.
     */
    public function dependencyCheck()
    {

    }

    /**
     * Returns a permission array for this Controller's scope for the current user.
     * @return array
     * @throws ControllerException
     */
    public function getControllerPermissions()
    {
        /** @var Controller $this */
        $warden = $this->getClosest('Warden', false, false);
        /** @var Controller|Warden $warden */
        if($warden && $this->uses('Rules'))
            $result = $warden->getPermissions($this);
        elseif($warden)
            $result = $warden->getPermissions();
        else
            $result = Warden::wardenlessFlags();

        return $result;
    }
}