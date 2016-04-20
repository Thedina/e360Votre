<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 7/9/2015
 * Time: 10:58 AM
 */

namespace eprocess360\v3core\Updater;


interface InterfaceModule
{
    public function register($settings_array);
    public function unregister();
    public function dropTables();
    public function createTables();
    public function ready();
    public function defaultSettings();
    public function hasManualSettings();
    public function availableSettings();
    public function getModuleName();
    public function getModuleSpace();
    public function getModuleSubSpace();
    public function hasMenu();
    public function getMenuOptions();
    public function htmlMethods();
    public function jsonMethods();
    public function hasJsonMethod($function);
}