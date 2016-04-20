<?php
/**
 * Created by PhpStorm.
 * User: kira
 * Date: 8/5/2015
 * Time: 12:19 PM
 */

namespace eprocess360\v3core\Controller\Trigger;
use eprocess360\v3core\Controller\StackItem;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\ControllerException;
use eprocess360\v3core\Controller\Event\Event;
use eprocess360\v3core\Controller\Project;
use eprocess360\v3core\Controller\State\State;
use eprocess360\v3core\Controller\Triggers;
use eprocess360\v3core\Controller\Warden\Role;
use Exception;

/**
 * Class Trigger
 * The base class for which all Trigger are built from.
 *
 * DEVELOPMENT NOTES
 * In the case an unauthorized User activates a Trigger under any circumstance, an Exception will be thrown unless the
 * Event is set to suppress Exceptions.  This design strategy doesn't work well if the Trigger itself has to suppress
 * Exceptions.  For now leaving it be but there might be cases where this functionality is available and we'll address
 * those at that time.
 *
 * @package eprocess360\v3core\Trigger
 */
class Trigger
{
    /** Allows child Trigger to specify they require an Event Object in order to exec() */
    const REQUIRE_EVENT_OBJECT = false;
    /** @var bool */
    protected $valid;
    /** @var State[] */
    protected $states = [];
    /** @var Controller[] The Controllers that this Trigger can activate for */
    protected $eventControllers = [];
    /** @var Role[] */
    protected $roles = [];
    /** @var null|\Closure */
    protected $closure;
    /** @var  string The name of the trigger */
    protected $name;
    /** @var Controller The Controller that will manage the trigger registry */
    protected $registrarController;

    /**
     * Trigger constructor.
     * @param $name
     * @param \Closure|null $closure
     * @param InterfaceTriggers[] ...$eventControllers
     */
    public function __construct($name, \Closure $closure = null, InterfaceTriggers ...$eventControllers)
    {
        $this->name = $name;
        if (is_callable($closure)) {
            $this->setClosure($closure);
            if (sizeof($eventControllers)) {
                $this->forEventController(...$eventControllers);
            }
            $this->valid = true;
        }
    }

    public static function build($name, \Closure $closure, InterfaceTriggers $eventControllers)
    {
        //echo 'add trigger: '.$name.'::'.$eventControllers->getId().PHP_EOL;
        $trigger = new static($name, $closure, $eventControllers);
        return $trigger;
    }

    /**
     * Set who tracks and executes the trigger stack
     * @param Controller|Triggers $registrarController
     * @return $this
     */
    public function bindRegistrar(Controller $registrarController)
    {
        $this->registrarController = $registrarController->registerTrigger($this);
        return $this;
    }

    /**
     * Gets the name of the Trigger by taking apart the class name and returning the name without a namespace.
     * @return array|mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Switch the Trigger from activating on any State to activating on only the given States.
     * @param string|array $states
     * @return Trigger
     * @throws Exception
     */
    public function forState($states)
    {
        if (!$this->getRegistrarController()->hasProjectStates()) throw new Exception("Cannot use Trigger::forState() when no States available.");
        if (!is_array($states)) $states = [$states];
        foreach ($states as $state) {
            $state = $this->getRegistrarController()->getProjectStateByName($state);
            if (!array_key_exists($state->getName(), $this->states)) {
                $this->states[$state->getName()] = $state;
                //$state->addTrigger($this);
            } else {
                throw new Exception("Trigger '{$this->getName()}' already has State '{$state->getName()}'.");
            }
        }
        return $this;
    }

    /**
     * Set the Trigger from activating for any EventObject to activating only when the EventObject is the given ones.
     * @param InterfaceTriggers|InterfaceTriggers[] ...$eventObjects
     * @return Trigger
     * @throws Exception
     */
    public function forEventController(InterfaceTriggers ...$eventObjects)
    {
        /** @var InterfaceTriggers $eventObject */
        foreach ($eventObjects as $eventObject) {
            if (!array_key_exists($eventObject->getId(), $this->eventControllers)) {
                $this->eventControllers[$eventObject->getId()] = $eventObject;
            } else {
                throw new Exception("Trigger '{$this->getName()}' already has EventObject '{$eventObject->getName()}'.");
            }
        }
        return $this;
    }

    /**
     * Set the Trigger from activating for an User to instead only activate for the given User.
     * @param Role|\eprocess360\v3core\Controller\Warden\Role[] ...$roles []
     * @return $this
     * @throws Exception
     */
    public function forRole(Role ...$roles)
    {
        /** @var Role $role */
        foreach ($roles as $role) {
            if (!array_key_exists($role->getId(), $this->roles)) {
                $this->roles[$role->getId()] = $role;
            } else {
                throw new Exception("Trigger '{$this->getName()}' already has Role '{$role->getId()}'.");
            }
        }
        return $this;
    }

    /**
     * Returns all the Roles this Trigger is accessible to.
     * @return Role[]
     */
    public function getRoles()
    {
        $out = $this->roles;
        /**
         * The last Role option adds the ability for global roles to gain access to this EventObject
         */
        //$out[] = Role::build(0, 'globalAccess', 'Global Access', -1)->setController($this->getRegistrarController());
        return $out;
    }

    /**
     * Returns all the Event Objects this Trigger is setup to respond to.
     * @return Controller[]
     */
    public function getEventControllers()
    {
        return $this->eventControllers;
    }

    /**
     * Returns all the States this Trigger is setup to respond to.
     * @return State[]
     */
    public function getStates()
    {
        return $this->states;
    }

    /**
     * Returns the Routes this Trigger will activate on.  Called by the Controller when building Trigger Routes.
     * @return array
     */
    public function getRoutes()
    {
        $name = $this->getName();
        $out = [];
        $block = false;
        if (sizeof($this->states)) {
            foreach ($this->states as $state) {
                /** @var State $state */
                $out[] = $name . '#' . $state->getName();
//                if ($state->getName()==0) {
//                    $block = true;
//                }
            }
        } elseif (!$block) {
            /** @var InterfaceTriggers $eventController */
            foreach ($this->eventControllers as $eventController) {
                $out[] = $name . '@' . $eventController->getId();
            }
            $out[] = $name.'#0';
        }
        return $out;
    }

    /**
     * Handles Trigger execution.  Verifies that all requirements are met and also assembles the PrivatePool that is
     * passed to the Trigger Closure.
     * @param Event $event
     * @return mixed
     * @throws Exception
     */
    public function exec(Event $event)
    {
        global $pool;
        if (!$this->isValid()) {
            throw new Exception("Trigger is not valid, cannot exec().");
        }
        if ($this->eventControllers) {
            if (!in_array($event->getController(), $this->eventControllers)) {
                return new StackItem(function(){
                    /**
                     * Nothing to execute since EventObject check failed.  This is a null function because typically
                     * the Controller won't need to show any warnings or such for a mismatched EventObject, though it is
                     * possible.
                     */
                });
            }
        }

        if ($this->getRoles()) {
            foreach ($this->roles as $role) {
                //var_dump($role, $event->getEventObject());
                //$role->setEventObject($event->getEventObject());
            }
            try {
                //todo
                //$pool->User->verifyRole(...$this->getRoles());
            } catch (\Exception $e) {
                if (!$event->suppressPermissionFailureExceptions()) {
                    throw $e;
                }
            }

        }
        return $this->getStackItem($event);

    }

    /**
     * Returns the Trigger Closure as a Stack ready StackItem for the Controller
     * @param Event|null $event
     * @return StackItem
     * @throws Exception
     */
    public function getStackItem(Event $event = null)
    {
        if (!$this->isValid()) {
            throw new Exception("Trigger is not valid, cannot exec().");
        }

        $callback = $this->closure;
        if ($callback instanceof \Closure) {
            global $pool;
            if ($event) {
                $pool->add($event, 'Event');
                if ($event->getController()) {
                    $pool->add($event->getController(), 'Event'.$event->getController()->getClass());
                }
            }
            $pool->add($this, 'EventTrigger');
            return new StackItem($callback, ...$event->getParameters());
        }
        throw new Exception("Trigger is not valid: Invalid Closure.");
    }

    /**
     * Whether or not this Trigger is valid.
     * @return boolean
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * @return \Closure|null
     */
    public function getClosure()
    {
        return $this->closure;
    }

    /**
     * @param \Closure|null $closure
     * @return Trigger
     */
    public function setClosure($closure)
    {
        $this->closure = $closure;
        return $this;
    }

    /**
     * @return Controller|Project
     */
    public function getRegistrarController()
    {
        return $this->registrarController;
    }

}