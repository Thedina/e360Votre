<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 12/22/15
 * Time: 12:30 PM
 */

namespace eprocess360\v3controllers\Group\Model;


use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;

/**
 * Class GroupRoles
 * @package eprocess360\v3controllers\Group\Model
 */
class GroupRoles extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idGroupRole', 'Group Roles ID'),
            IdInteger::build('idGroup', 'Group ID'),
            IdInteger::build('idSystemRole', 'System Role ID'),
            IdInteger::build('idProject', 'Project ID'),
            IdInteger::build('idLocalRole', 'Local Role ID')
        )->setName('GroupRoles')->setLabel('GroupRoles');
    }
}