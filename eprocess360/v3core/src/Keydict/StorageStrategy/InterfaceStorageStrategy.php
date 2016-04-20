<?php
/**
 * Created by PhpStorm.
 * User: kira
 * Date: 8/13/2015
 * Time: 11:43 AM
 */

namespace eprocess360\v3core\Keydict\StorageStrategy;


use eprocess360\v3core\Keydict;

interface InterfaceStorageStrategy
{

    public function sleep(Keydict $keydict);
    public function wakeup(Keydict $keydict, $value);
}