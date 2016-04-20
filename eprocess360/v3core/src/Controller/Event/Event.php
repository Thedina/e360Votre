<?php
/**
 * Created by PhpStorm.
 * User: kira
 * Date: 8/5/2015
 * Time: 12:37 PM
 */

namespace eprocess360\v3core\Controller\Event;


use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Project;
use eprocess360\v3core\Controller\Trigger\InterfaceTriggers;
use eprocess360\v3core\Controller\Trigger\Trigger;

class Event
{
    protected $triggering_route;
    protected $controller;
    protected $suppress_exceptions = false;
    protected $trigger;
    protected $parameters = [];

    /**
     * @param string $trigger
     * @param ...$parameters
     */
    public function __construct($trigger, ...$parameters)
    {
        $this->setTrigger(new Trigger($trigger));
        $this->parameters = $parameters;
    }

    /**
     * @param string $trigger
     * @param InterfaceTriggers $controller
     * @param ...$parameters
     * @return static
     */
    public static function build($trigger, InterfaceTriggers $controller = null, ...$parameters)
    {
        $event = new static($trigger, ...$parameters);
        if ($controller) {
            $event->setController($controller);
        }
        return $event;
    }

    /**
     * Get the Routes this Event activates on; includes 0 route
     * @param InterfaceTriggers $controller
     * @return array
     */
    public function getRoutes(InterfaceTriggers $controller)
    {
        $triggerName = $this->getTrigger()->getName();
        $state = null;
        if ($controller instanceof Controller) {
            /** @var Controller|Project $controller */
            $state = $controller->uses('Project')?"#{$controller->getProjectState()->getName()}":'@'.$controller->getId();
        }
        $results = ["{$triggerName}{$state}"];
        if ($state) {
            $results[] = "{$triggerName}#0";
        }
        return $results;
    }

    /**
     * @param $route
     */
    public function addTriggeringRoute($route)
    {
        $this->triggering_route = $route;
    }


    /**
     * @return Controller
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @param InterfaceTriggers $object
     */
    public function setController(InterfaceTriggers $object)
    {
        $this->controller = $object;
    }

    /**
     * @return bool
     */
    public function suppressPermissionFailureExceptions()
    {
        return $this->suppress_exceptions;
    }

    /**
     * @return Trigger
     */
    public function getTrigger()
    {
        return $this->trigger;
    }

    /**
     * @param Trigger $trigger
     */
    public function setTrigger($trigger)
    {
        $this->trigger = $trigger;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param array $parameters
     * @return $this
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }

}