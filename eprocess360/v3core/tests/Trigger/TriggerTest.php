<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 8/24/2015
 * Time: 2:49 PM
 */

namespace Trigger;


use eprocess360\v3core\Controller\StackItem;
use eprocess360\v3core\Controller\State;
use eprocess360\v3core\Event;
use eprocess360\v3core\Role;
use eprocess360\v3core\Trigger\Trigger;

/**
 * Class TriggerTest
 * todo Deep testing of exec()
 * @package Trigger
 */
class TriggerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @return Trigger
     */
    public function testIsValid()
    {
        $trigger = new Trigger(function (){ return 123;});
        $this->assertEquals(true, $trigger->isValid());
        return $trigger;
    }

    public function testIsInvalid()
    {
        $trigger = new Trigger();
        $this->assertEquals(false, $trigger->isValid());
    }

    /**
     * @depends testIsValid
     * @param Trigger $trigger
     * @return Trigger
     */
    public function testGetName($trigger)
    {
        $this->assertEquals('Trigger', $trigger->getName());
        return $trigger;
    }

    /**
     * @depends testGetName
     * @param Trigger $trigger
     */
    public function testCanGetRoutes($trigger)
    {
        $this->assertEquals(["{$trigger->getName()}#0"], $trigger->getRoutes());
    }

    /**
     * @depends testGetName
     * @param Trigger $trigger
     * @return StackItem
     */
    public function testCanGetStackItem($trigger)
    {
        $result = $trigger->getStackItem();
        $this->assertEquals('eprocess360\v3core\Controller\StackItem', get_class($result));
        return $result;
    }

    /**
     * @depends testCanGetStackItem
     * @param StackItem $stackItem
     * @return StackItem
     */
    public function testCanGetStackItem2($stackItem)
    {
        $this->assertEquals(123, $stackItem->exec());
    }

    /**
     * @depends testGetName
     * @param Trigger $trigger
     * @return StackItem
     */
    public function testCanExec($trigger)
    {
        $result = $trigger->exec(new Event(new Trigger()));
        $this->assertEquals(123, $result->exec());
        return $result;
    }

    /**
     * @depends testIsValid
     * @param Trigger $trigger
     * @return Trigger
     */
    public function testCanAddState($trigger)
    {
        $state = new State();
        $state->setKey('Test');
        $trigger->forState($state);
        $this->assertEquals([$state->getKey()=>$state], $trigger->getStates());
        return $trigger;
    }

    /**
     * @depends testIsValid
     * @param Trigger $trigger
     */
    public function testCanAddRole($trigger)
    {
        $role = new Role();
        $trigger->forRole($role);
        $this->assertEquals([$role->getIdentifier()=>$role], $trigger->getRoles());
    }

    /**
     * @depends testIsValid
     * @param Trigger $trigger
     */
    public function testCanAddEventObject($trigger)
    {
        $eventObject = new Event\EventObject();
        $trigger->forEventObject($eventObject);
        $this->assertEquals([$eventObject->getIdentifier()=>$eventObject], $trigger->getEventObjects());
    }

    /**
     * @depends testCanAddState
     * @param Trigger $trigger
     */
    public function testCanGetRoutes2($trigger)
    {
        $this->assertEquals(["{$trigger->getName()}#Test"], $trigger->getRoutes());
    }


}
