<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 8/19/2015
 * Time: 7:16 PM
 */

namespace eprocess360\v3controllers\Group\Model;
use Composer\Command\SelfUpdateCommand;
use Dompdf\Exception;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Entry\Bits8;
use eprocess360\v3core\Keydict\Entry\FixedString128;
use eprocess360\v3core\Keydict\Entry\FixedString256;
use eprocess360\v3core\Keydict\Entry\FixedString32;
use eprocess360\v3core\Keydict\Entry\Flag;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\JSONArrayFixed128;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\String;
use eprocess360\v3core\Keydict\Entry\TinyInteger;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;
use eprocess360\v3core\Model\Roles;
use eprocess360\v3core\Model\UserRoles;
use eprocess360\v3core\Model\Users;
use eprocess360\v3core\User;
use eprocess360\v3core\DB;
use SebastianBergmann\Comparator\ExceptionComparatorTest;

/**
 * Class GroupUsers
 * @package eprocess360\v3controllers\Group\Model
 */
class GroupUsers extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idGroupUser', 'Group Users ID'),
            IdInteger::build('idGroup', 'Group ID')->joinsOn(Groups::model()),
            IdInteger::build('idUser', 'User ID')->joinsOn(Users::model()),
            Bits8::make('status',
                Keydict\Entry\Bit::build(0, 'isActive')
            )
        )->setName('GroupUsers')->setLabel('GroupUsers');
    }

    /**
     * @param int $idGroup
     * @param int $idUser
     * @param array $status
     * @return GroupUsers
     */
    public static function make($idGroup = 0, $idUser = 0, $status = [0]) {

        $rowData = ['idGroup'=>$idGroup,
            'idUser'=>$idUser,
            'status'=>$status];

        return self::GroupUserConstruct($rowData);
    }

    /**
     * @param array $rowData
     * @return GroupUsers
     */
    public static function GroupUserConstruct($rowData = []) {
        $instance = new self();
        $instance->data = self::keydict();
        $instance->data->acceptArray($rowData);
        return $instance;
    }

    /**
     * @param $idUser
     * @param $idRole
     * @param $idGroup
     * @param $isActive
     * @param null $grantedBy
     * @return array|string
     * @throws Exception
     * @throws \Exception
     */
    public static function create($idUser, $idRole, $idGroup, $isActive, $grantedBy = NULL) {

        global $pool;
        $group = Groups::sqlFetch($idGroup)->toArray();
        $user = Users::sqlFetch($idUser)->toArray();
        $role = Roles::sqlFetch($idRole);
        $idController = $group['idController'];
        $status = ['isActive'=>$isActive];
        $sql = "SELECT GroupUsers.idGroupUser FROM GroupUsers WHERE
                GroupUsers.idGroup = {$idGroup} AND GroupUsers.idUser = {$idUser}";
        $groupUser = DB::sql($sql);

        if($role->idController->get() != $idController)
            throw new Exception("Invalid Role");
        if($groupUser !== [])
            throw new Exception("User is already in this Group.");

        if($grantedBy === NULL)
            $grantedBy = $idController;
        $sql = "INSERT INTO UserRoles (idUser, idSystemRole, grantedBy) VALUES ({$idUser}, {$idRole}, {$grantedBy})";
        DB::sql($sql);

        $f = static::make($idGroup, $idUser, $status);
        $f->insert();

        $result = $f->data->toArray();

        Groups::assignGroupRoles($idGroup, $idUser);

        $result = array("idUser" => $idUser, "firstName" => $user['firstName'], "lastName" => $user['lastName'], 'idRole' => $idRole, 'status' => $result['status']);

        return $result;
    }

    /**
     * @param $idGroup
     * @param $idUser
     * @param $idRole
     * @param $isActive
     * @return array|string
     * @throws Keydict\Exception\InvalidValueException
     * @throws Keydict\Exception\KeydictException
     * @throws \Exception
     */
    public static function editGroupUser($idGroup, $idUser, $idRole, $isActive){
        global $pool;
        $group = Groups::sqlFetch($idGroup);
        $user = Users::sqlFetch($idUser)->toArray();
        $sql = "SELECT GroupUsers.idGroupUser FROM GroupUsers WHERE
                GroupUsers.idGroup = {$idGroup} AND GroupUsers.idUser = {$idUser}";
        $groupUser = GroupUsers::sqlFetch(DB::sql($sql)[0]['idGroupUser']);
        $role = Roles::sqlFetch($idRole);
        $idController = $group->idController->get();
        assert($role->idController->get() == $idController);
        $sql = "SELECT UserRoles.idUserRole FROM UserRoles LEFT JOIN Roles
                ON UserRoles.idSystemRole = Roles.idSystemRole WHERE
                Roles.idController = {$idController} AND UserRoles.idUser = {$idUser}";
        $userRole = UserRoles::sqlFetch(DB::sql($sql)[0]['idUserRole']);
        $userRole->idSystemRole->set($idRole);
        $userRole->grantedBy->set($idController);
        $groupUser->status->isActive->set($isActive);
        $userRole->update();
        $groupUser->update();

        $result = $groupUser->toArray();

        $result = array("idUser" => $idUser, "firstName" => $user['firstName'], "lastName" => $user['lastName'], 'idRole' => $idRole, 'status' => $result['status']);

        return $result;
    }

    /**
     * @param $idGroup
     * @param $idUser
     * @return bool
     * @throws \Exception
     */
    public static function deleteGroupUser($idGroup, $idUser) {

        $group = Groups::sqlFetch($idGroup)->toArray();
        $sql = "SELECT GroupUsers.idGroupUser FROM GroupUsers WHERE idGroup = {$idGroup} AND idUser = {$idUser}";
        $groupUsers = DB::sql($sql);
        $sql = "SELECT UserRoles.idUserRole FROM UserRoles LEFT JOIN Roles
                ON UserRoles.idSystemRole = Roles.idSystemRole WHERE
                (Roles.idController = {$group['idController']} OR UserRoles.grantedBy = {$group['idController']}) AND UserRoles.idUser = {$idUser}";
        $userRoles = DB::sql($sql);

        Groups::deassignGroupRoles($idGroup, $idUser);

        foreach($userRoles as $userRole) {
            if(isset($userRole['idUserRole']))
                UserRoles::deleteById($userRole['idUserRole']);
        }
        foreach($groupUsers as $groupUser) {
            if(isset($groupUser['idGroupUser']))
                GroupUsers::deleteById($groupUser['idGroupUser']);
        }
        return true;
    }

    /**
     * @param $idUser
     * @return array
     * @throws \Exception
     */
    public static function getGroupsByUserId($idUser){

        $sql = "SELECT DISTINCT GroupUsers.idGroup FROM GroupUsers WHERE idUser = {$idUser}";
        $groups = DB::sql($sql);
        $result = [];
        foreach($groups as $group)
            if(isset($group['idGroup']))
                $result[(int)$group['idGroup']] = (int)$group['idGroup'];

        return $result;
    }

}