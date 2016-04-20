<?php
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Entry\String;
use eprocess360\v3core\Keydict\StorageStrategy\MultiColumn;
use eprocess360\v3core\Keydict\StorageStrategy\SingleColumn;

/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 8/7/2015
 * Time: 4:43 PM
 */
class KeydictTest extends PHPUnit_Framework_TestCase
{

    public function testBuildKeydictWithStringAndSleep()
    {
        $keydict = Keydict::build(
            String::build('test', 'Test String', 'Default Value')
        );
        $this->assertEquals($keydict->sleep(), ['test'=>'Default Value']);
        return $keydict;
    }

    /**
     * @depends testBuildKeydictWithStringAndSleep
     * @param Keydict $keydict
     * @return Keydict
     */
    public function testAddEntryAndSleep(Keydict $keydict)
    {
        $keydict->add(String::build('food', 'Favorite Food', ''));
        $this->assertEquals($keydict->sleep(), ['test'=>'Default Value', 'food'=>'']);
        return $keydict;
    }

    /**
     * @depends testAddEntryAndSleep
     * @param Keydict $keydict
     * @return Keydict
     */
    public function testDefaultValue(Keydict $keydict)
    {
        $this->assertEquals('Default Value', $keydict->getField('test')->sleep());
    }

    /**
     * @depends testAddEntryAndSleep
     * @param Keydict $keydict
     * @return Keydict
     */
    public function testSetStorageStrategyToSingleColumn(Keydict $keydict)
    {
        $this->assertEquals([''=>'{"test":"Default Value","food":""}'], $keydict->setStorageStrategy(new SingleColumn())->sleep());
        return $keydict;
    }

    /**
     * @depends testAddEntryAndSleep
     * @param Keydict $keydict
     * @return Keydict
     */
    public function testSetEntryValue(Keydict $keydict)
    {
        $keydict->getField('food')->set('Cookies');
        $this->assertEquals([''=>'{"test":"Default Value","food":"Cookies"}'], $keydict->setStorageStrategy(new SingleColumn())->sleep());
    }

    /**
     * @depends testAddEntryAndSleep
     * @param Keydict $keydict
     * @return Keydict
     */
    public function testSetName(Keydict $keydict)
    {
        $this->assertEquals(['test'=>'Default Value', 'food'=>'Cookies'], $keydict->setStorageStrategy(new MultiColumn())->setName('root')->sleep());
        return $keydict;
    }

    /**
     * @depends testSetName
     * @param Keydict $keydict
     * @return Keydict
     */
    public function testNameWithSingleColumn(Keydict $keydict)
    {
        $this->assertEquals(['_root_'=>'{"test":"Default Value","food":"Cookies"}'], $keydict->setStorageStrategy(new SingleColumn())->sleep());
        return $keydict;
    }
}
