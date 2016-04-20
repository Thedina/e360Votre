<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 8/19/2015
 * Time: 7:16 PM
 */

namespace eprocess360\v3modules\RouteTree\Model;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\PrimaryKeySmallInt;
use eprocess360\v3core\Keydict\Entry\String;
use eprocess360\v3core\Keydict\Entry\Text;
use eprocess360\v3core\Keydict\Entry\UnsignedSmallInteger;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;
use eprocess360\v3core\Model\ProjectControllers;


/**
 * Class RouteTrees
 * @package eprocess360\v3core\RouteTree\Model
 */
class RouteTrees extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idRoute', 'Route ID'),
            IdInteger::build('idController', 'Controller ID'),
            String::build('title', 'Controller\'s Designation'),
            Text::build('helpText', 'Help Text'),
            Text::build('configuredFlags', 'The Flags configured by the RouteTree'),
            UnsignedSmallInteger::build('lastBitPosition', 'Last Bit Position')
        )->setName('RouteTrees')->setLabel('Route Trees');
    }
}