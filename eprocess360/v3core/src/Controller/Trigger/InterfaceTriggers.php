<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 12/22/2015
 * Time: 12:16 PM
 */

namespace eprocess360\v3core\Controller\Trigger;


interface InterfaceTriggers
{
    public function getId();
    public function getName();
    public function getClass();
    public function triggersDefaultRegistrar();
}