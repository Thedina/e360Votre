<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 8/19/2015
 * Time: 7:16 PM
 */

namespace eprocess360\v3core\Model;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Entry\Bit;
use eprocess360\v3core\Keydict\Entry\Bits;
use eprocess360\v3core\Keydict\Entry\Bits8;
use eprocess360\v3core\Keydict\Entry\FixedString128;
use eprocess360\v3core\Keydict\Entry\FixedString256;
use eprocess360\v3core\Keydict\Entry\FixedString32;
use eprocess360\v3core\Keydict\Entry\Flag;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\IdTinyInt;
use eprocess360\v3core\Keydict\Entry\Integer;
use eprocess360\v3core\Keydict\Entry\JSONArrayFixed128;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\TinyInteger;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;

/**
 * @package eprocess360\v3core\Model
 */
class Roles extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idSystemRole', 'Role ID'),
            FixedString32::build('title', 'Role Title'),
            IdInteger::build('idController', 'Controller ID')->joinsOn(ProjectControllers::model()),
            Bits8::make(
                'flags',
                Bit::build(0, 'isRead', 'Read'),
                Bit::build(1, 'isWrite', 'Write'),
                Bit::build(2, 'isCreate', 'Create'),
                Bit::build(3, 'isDelete', 'Delete'),
                Bit::build(4, 'isAdmin', 'Administrative')
            )
        )->setName('Roles')->setLabel('Roles');
    }
}