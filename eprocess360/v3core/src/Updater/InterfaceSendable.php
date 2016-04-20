<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 7/8/2015
 * Time: 12:07 PM
 */

namespace eprocess360\v3core\Updater;


interface InterfaceSendable
{
    function getPackage();

    function sign();
}