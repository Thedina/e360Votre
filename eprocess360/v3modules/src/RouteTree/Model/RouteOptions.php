<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 8/19/2015
 * Time: 7:16 PM
 */

namespace eprocess360\v3modules\RouteTree\Model;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Entry\FixedString128;
use eprocess360\v3core\Keydict\Entry\FixedString256;
use eprocess360\v3core\Keydict\Entry\Flag;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\JSONArrayFixed128;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\TinyInteger;
use eprocess360\v3core\Keydict\Entry\UnsignedSmallInteger;
use eprocess360\v3core\Keydict\Entry\UnsignedTinyInteger;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;

/**
 * Class RouteOptions
 * @package eprocess360\v3core\RouteTree\Model
 */
class RouteOptions extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idRouteOption', 'Routing Option ID'),
            IdInteger::build('parent', 'Parent Routing Option ID'),
            FixedString128::build('title', 'Option Title'),
            FixedString256::build('helpText', 'Help Text'),
            JSONArrayFixed128::build('flags', 'Flags Set By Option'),
            IdInteger::build('idRoute', 'Route ID')->joinsOn(RouteTrees::model()),
            UnsignedTinyInteger::build('itemOrder', 'Order Position'),
            UnsignedTinyInteger::build('bitPosition', 'Bit Position'),
            UnsignedTinyInteger::build('depth', 'Depth')
        )->setName('RouteOptions')->setLabel('Route Tree Options');
    }
}