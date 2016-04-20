<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 7/9/2015
 * Time: 10:58 AM
 */

namespace eprocess360\v3core\Updater;


interface InterfaceUpdaterModule
{
    public function register($settings_array);
    public function unregister();
    public function dropTables();
    public function createTables();
    public function ready();
    public function defaultSettings();
    public function manualSettings();
    public function availableSettings();
    public function getName();
    public function htmlMethodsLabels();
    public function getModuleSpace();
    public function htmlMethods();
    public function jsonMethods();
    public function supportsJsonMethod($function);
}