<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 8/19/2015
 * Time: 7:16 PM
 */

namespace eprocess360\v3core\Model;
use eprocess360\v3core\DB;
use eprocess360\v3core\Form;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Entry\Bit;
use eprocess360\v3core\Keydict\Entry\Bits8;
use eprocess360\v3core\Keydict\Entry\BitString;
use eprocess360\v3core\Keydict\Entry\FixedString128;
use eprocess360\v3core\Keydict\Entry\FixedString256;
use eprocess360\v3core\Keydict\Entry\FixedString32;
use eprocess360\v3core\Keydict\Entry\Flag;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\JSONArrayFixed128;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\TinyInteger;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;
use eprocess360\v3core\Toolbox;
use eprocess360\v3core\User;
use eprocess360\v3core\Controller\Warden\Privilege;

/**
 * This is the base Controllers table.  All other Controllers will join on this table.  Project Controllers are stored
 * in ProjectControllers now.
 * @package eprocess360\v3core\Model
 */
class Controllers extends Model
{
    const STATUS_DELETED = 0;
    const STATUS_DOWN = 1;
    const STATUS_UP = 2;
    const STATUS_ABNORMAL_NAMESPACE = 4;

    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idController', 'Controller ID'),
            FixedString128::build('class', 'Controller Class'),
            FixedString128::build('title', 'Controller Title'),
            FixedString256::build('path', 'Controller Path'),
            Bits8::make('status',
                Bit::build(0, 'isActive', 'Controller Active'),
                Bit::build(1, 'isAllCreate', 'Creatable By Anyone'),
                Bit::build(2, 'isAbnormalNamespace', 'Abnormal Namespace')
            )
        )->setName('Controllers');
    }

}
