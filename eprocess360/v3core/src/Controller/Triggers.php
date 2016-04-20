<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 12/2/2015
 * Time: 1:56 PM
 */

namespace eprocess360\v3core\Controller;

use eprocess360\v3core\Controller\StackItem;
use eprocess360\v3core\Controller\Event\Event;
use eprocess360\v3core\Controller\Trigger\InterfaceTriggers;
use eprocess360\v3core\Controller\Trigger\Trigger;

/**
 * Class Triggers
 * @package eprocess360\v3core\Controller
 */
trait Triggers
{
    protected $triggersAvailable = [];
    protected $triggersRoutesCurrent;
    protected $triggers = [];
    protected $triggersStack = [];
    protected $triggersRunning = false;
    protected $triggersBubbles = true;


    /**
     * Adds a Trigger with a name and a given closure.
     * @param $name
     * @param $closure
     * @return Trigger
     */
    public function addTrigger($name, \Closure $closure)
    {
        /** @var Controller|Triggers|InterfaceTriggers $this */
        return Trigger::build($name, $closure, $this)->bindRegistrar($this->triggersDefaultRegistrar());
    }

    /**
     * Gets the existing Triggers.
     * @return Trigger[]
     */
    public function getTriggers()
    {
        return $this->triggers;
    }

    /**
     * Add a trigger to the controller.  Generally this function will never need to be called.
     * @param Trigger $trigger
     * @return $this
     */
    public function registerTrigger(Trigger $trigger)
    {
        $this->triggersAvailable[] = $trigger;
        $this->triggersRoutesCurrent = false;
        return $this;
    }

    /**
     * Refreshes the existing trigger routes.
     */
    public function refreshTriggerRoutes()
    {

        if ($this->triggersRoutesCurrent) return;
        $this->triggers = [];
        foreach ($this->triggersAvailable as $trigger) {
            /** @var Trigger $trigger */
            foreach ($trigger->getRoutes() as $case) {
                //echo $case.PHP_EOL;
                $this->triggers[$case][] = $trigger;
            }
        }
        $this->triggersRoutesCurrent = true;
    }

    /**
     * Trigger something on the current controller
     *
     * @param Event|string $event
     * @param array $parameters
     * @return mixed
     * @throws /Exception
     */
    public function trigger($event, ...$parameters)
    {

        if (is_string($event)) {
            $event = Event::build($event, $this)->setParameters($parameters);
        }
        $nearest = $this->triggersDefaultRegistrar();
        if ($this!=$nearest) {
            $nearest->trigger($event);
        }
        $this->refreshTriggerRoutes();
        /** @var InterfaceTriggers|Triggers $this */
        foreach ($event->getRoutes($this) as $case) {
            //echo $this->getId().'testing case: '.$case.PHP_EOL;
            if (isset($this->triggers[$case])) {
                //echo 'match case: '.$case.PHP_EOL;
                foreach ($this->triggers[$case] as $function) {
                    /** @var Trigger $function */
                    $this->push($function->exec($event));
                }
            }
        }
        $this->exec();
    }

    /**
     * Push an item onto the Stack
     * @param StackItem $item
     */
    public function push(StackItem $item)
    {
        $this->triggersStack[] = $item;
    }


    /**
     * Execute the stack
     */
    public function exec()
    {
        if ($this->triggersRunning) return;
        $this->triggersRunning = true;
        /** @var StackItem $item */
        while (sizeof($this->triggersStack)) {
            $item = array_shift($this->triggersStack);
            if ($this->canBubbleEventExecution()) {
                $item->exec();
            } else {
                break;
            }
        }
        $this->triggersRunning = false;
    }

    /**
     * Can triggers continue executing?
     *
     * @return bool
     */
    public function canBubbleEventExecution()
    {
        return $this->triggersBubbles;
    }

    /**
     * Set whether triggers can continue executing the case stack
     *
     * @param bool $value
     */
    public function setTriggersBubbles($value)
    {
        $this->triggersBubbles = $value;
    }

    /**
     * gets the current Triggers on the stack.
     * @return mixed
     */
    public function getTriggersStack()
    {
        return $this->triggersStack;
    }
}