<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 8/6/2015
 * Time: 8:56 AM
 */

namespace eprocess360\v3core\Controller\State;
use eprocess360\v3core\Controller\Trigger\InterfaceTriggers;
use eprocess360\v3core\Controller\Triggers;

/**
 * Class State
 * @package eprocess360\v3core\Controller
 *
 * State is just a human readable string describing what state the project is in.  The project will store one of these
 * states in the ProjectController table.
 */
class State implements InterfaceTriggers
{
    use Triggers;

    protected $name;
    protected $home;
    protected $idState;
    protected $internalName;
    private $controller;

    protected function __construct($name, $idState = 0, $home = null, $internalName = null)
    {
        if (strlen($name)<65) {
            $this->name = $name;
        } else {
            throw new \Exception("State name must be 0-64 characters long.");
        }
        $this->home = $home;
        $this->idState = $idState;
        $this->internalName = $internalName ? $internalName : preg_replace('/[^a-zA-Z]/','',$name);

    }

    public function getClass()
    {
        return 'State';
    }

    /**
     * @param $name
     * @param int $idState
     * @param null $home
     * @param null $internalName
     * @return static
     */
    public static function build($name, $idState = 0, $home = null, $internalName = null)
    {
        return new static($name, $idState, $home, $internalName);
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param \Closure $closure
     * @return \eprocess360\v3core\Controller\Trigger\Trigger
     */
    public function onEnter(\Closure $closure)
    {
        return $this->addTrigger(__FUNCTION__, $closure);
    }

    /**
     * @param \Closure $closure
     * @return \eprocess360\v3core\Controller\Trigger\Trigger
     */
    public function onExit(\Closure $closure)
    {
        return $this->addTrigger(__FUNCTION__, $closure);
    }


    public function triggersDefaultRegistrar()
    {
        return $this->getController();
    }

    public function getController()
    {
        return $this->controller;
    }

    /**
     * @param mixed $controller
     * @return State
     */
    public function setController($controller)
    {
        $this->controller = $controller;
        return $this;
    }

    public function getId()
    {
        return $this->internalName;
    }

    /**
     * @return mixed|null
     */
    public function getInternalName()
    {
        return $this->internalName;
    }

    /**
     * @return null
     */
    public function getHome()
    {
        return $this->home;
    }

    /**
     * @return int
     */
    public function getIdState()
    {
        return $this->idState;
    }
}