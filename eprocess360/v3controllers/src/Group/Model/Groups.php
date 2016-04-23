<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 8/19/2015
 * Time: 7:16 PM
 */

namespace eprocess360\v3controllers\Group\Model;
use eprocess360\v3core\Controller\Controller;
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
use eprocess360\v3controllers\Group\Group;
use eprocess360\v3core\DB;
use eprocess360\v3core\Model\Controllers;
use eprocess360\v3core\Model\Roles;
use eprocess360\v3core\Model\UserRoles;
use eprocess360\v3core\Warden;
use eprocess360\v3core\Controller\Warden\Privilege;

/**
 * Class Groups
 * @package eprocess360\v3controllers\Group\Model
 */
class Groups extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idGroup', 'Group ID'),
            IdInteger::build('idController', 'Controller ID'),
            FixedString128::build('title', 'Group Title')->setRequired(),
            Bits8::make('status',
                Keydict\Entry\Bit::build(0, 'isActive'),
                Keydict\Entry\Bit::build(1, 'isSystem')
            )
        )->setName('Groups')->setLabel('Groups');
    }

    /**
     * @param String $title
     * @param int $idController
     * @param array $status
     * @return Groups
     */
    public static function make($title = '', $idController = 0, $status = [0]) {

        $rowData = ['title'=>$title,
            'idController'=>$idController,
            'status'=>$status];

        return self::GroupConstruct($rowData);
    }

    /**
     * @param array $rowData
     * @return Groups
     */
    public static function GroupConstruct($rowData = []) {
        $instance = new self();
        $instance->data = self::keydict();
        $instance->data->acceptArray($rowData);
        return $instance;
    }

    /**
     * Instantiate a Group and insert into the DB
     * @param $title
     * @param $isActive
     * @param bool|false $isSystem
     * @return array
     * @throws \Exception
     */
    public static function create($title, $isActive, $isSystem = false) {

        $idController = Group::register($title);
        $status = ['isActive'=>$isActive,'isSystem'=>$isSystem];

        $administrator = (int)Privilege::ROLE_ADMIN;
        $facilitator = (int)Privilege::READ + Privilege::CREATE + Privilege::WRITE + Privilege::DELETE;
        $member = (int)Privilege::READ;

        $sql = "INSERT INTO Roles
                  (title, idController, flags_0)
                    VALUES
                      ('Administrator', {$idController}, {$administrator}),
                      ('Facilitator', {$idController}, {$facilitator}),
                      ('Member', {$idController}, {$member})";
        DB::sql($sql);

        $f = static::make($title, $idController, $status);
        $f->insert();

        $result = $f->data->toArray();


        return $result;
    }

    /**
     * @param bool|false $readable
     * @return array
     * @throws \Exception
     */
    public static function allGroups($readable = false)
    {
        global $pool;
        //find all Groups where User has a UserGroup
        $sql = "SELECT Groups.* FROM GroupUsers
                LEFT JOIN Groups ON Groups.idGroup = GroupUsers.idGroup
                WHERE GroupUsers.idUser = {$pool->User->getIdUser()}
                  AND GroupUsers.status_0 & 0b10
                ORDER BY Groups.idGroup DESC
                LIMIT 0,30";

        if($readable){
            $sql = "SELECT * FROM `Groups`
                    ORDER BY `idGroup` DESC";
         }

        $new = array();
        foreach (self::each($sql)
                 as $sqlResult){
            $resultArray = $sqlResult->toArray();

            if(isset($resultArray['idGroup'])) {
                $new[] = $resultArray;
            }
        }

        return $new;
    }

    /**
     * @param $idGroup
     * @return array|string
     * @throws \Exception
     */
    public static function allGroupUsers($idGroup)
    {
        $keydict = self::sqlFetch($idGroup);
        $group = $keydict->toArray();

        $sql = "SELECT Users.idUser, Roles.idSystemRole as idRole, Users.firstName,
                Users.lastName, GroupUsers.idGroupUser, GroupUsers.status_0 FROM UserRoles LEFT JOIN Users
                ON UserRoles.idUser = Users.idUser LEFT JOIN GroupUsers
                ON Users.idUser = GroupUsers.idUser LEFT JOIN Roles
                ON UserRoles.idSystemRole = Roles.idSystemRole WHERE GroupUsers.idGroup = {$idGroup} AND Roles.idController = {$group['idController']}";
        $users = DB::sql($sql);
        $group['users'] = $users;
        foreach($group['users'] as $user){
            $keydict = GroupUsers::keydict();
            GroupUsers::wakeupAndTranslateStatus($keydict, $user);
        }

        $group['roles'] = self::getRoles($idGroup);

        return $group;
    }

    /**
     * @param $idGroup
     * @return bool
     * @throws \Exception
     */
    public static function deleteGroup($idGroup)
    {
        $group = self::sqlFetch($idGroup);
        $idController = $group->idController->get();
        $sql = "SELECT GroupUsers.idGroupUser FROM GroupUsers WHERE idGroup = {$idGroup}";
        $groupUsers = DB::sql($sql);
        $sql = "SELECT Roles.idSystemRole FROM Roles WHERE idController = {$idController}";
        $roles = DB::sql($sql);
        $sql = "SELECT UserRoles.idUserRole FROM UserRoles LEFT JOIN Roles
                ON UserRoles.idSystemRole = Roles.idSystemRole WHERE Roles.idController = {$idController}
                OR UserRoles.grantedBy = {$idController}";
        $userRoles = DB::sql($sql);
        $sql = "SELECT GroupRoles.idGroup FROM GroupRoles WHERE idGroup = {$idGroup}";
        $groupRoles = DB::sql($sql);
        $sql = "SELECT GroupProjects.idGroupProject FROM GroupProjects WHERE idGroup = {$idGroup}";
        $groupProjects = DB::sql($sql);

        foreach ($groupProjects as $groupProject) {
            if(isset($groupProject['idGroupProject']))
                GroupProjects::deleteById($groupProject['idGroupProject']);
        }
        foreach ($userRoles as $userRole) {
            if(isset($userRole['idUserRole']))
                UserRoles::deleteById($userRole['idUserRole']);
        }
        foreach ($groupRoles as $groupRole) {
            if(isset($groupRole['idGroupRole']))
                GroupRoles::deleteById($groupRole['idGroupRole']);
        }
        foreach ($groupUsers as $groupUser) {
            if(isset($groupUser['idGroupUser']))
                GroupUsers::deleteById($groupUser['idGroupUser']);
        }
        foreach ($roles as $role) {
            if(isset($role['idSystemRole']))
                Roles::deleteById($role['idSystemRole']);
        }
        Controllers::deleteById($group->idController->get());
        self::deleteById($idGroup);

        return true;
    }

    /**
     * @param $idGroup
     * @return array|null
     * @throws \Exception
     */
    public static function getRoles($idGroup){
        $group = self::sqlFetch($idGroup);
        $sql = "SELECT Roles.idSystemRole as idRole, Roles.title FROM Roles WHERE idController = {$group->idController->get()}";
        $roles = DB::sql($sql);

        return $roles;
    }

    /**
     * @param $idRole
     * @return array|null
     * @throws \Exception
     */
    public static function getRole($idRole){
        $sql = "SELECT * FROM Roles WHERE idSystemRole = $idRole";
        $roles = DB::sql($sql);

        return $roles[0];
    }

    /**
     * @param $idGroup
     * @param $idUser
     * @throws \Exception
     */
    public static function assignGroupRoles($idGroup, $idUser)
    {
        $group = self::sqlFetch($idGroup);
        $idController = $group->idController->get();
        $sql = "SELECT GroupRoles.* FROM GroupRoles WHERE idGroup = {$idGroup}";
        $roles = DB::sql($sql);
        $sqlArr = [];
        $sql = "INSERT INTO UserRoles (idUser, idSystemRole, idProject, idLocalRole, grantedBy) VALUES ";
        foreach($roles as $role){
            if(isset($role['idGroupRole']))
                $sqlArr[] =  "({$idUser}, {$role['idSystemRole']}, {$role['idProject']}, {$role['idLocalRole']}, {$idController})";
        }
        if(sizeof($sqlArr)){
            DB::sql($sql.implode(',', $sqlArr));
        }
    }

    /**
     * @param $idGroup
     * @param $idUser
     * @throws \Exception
     */
    public static function deassignGroupRoles($idGroup, $idUser)
    {
        $group = self::sqlFetch($idGroup);
        $idController = $group->idController->get();
        $sql = "SELECT UserRoles.idUserRole FROM UserRoles WHERE
                UserRoles.grantedBy = {$idController} AND UserRoles.idUser = {$idUser}";
        $userRoles = DB::sql($sql);
        foreach ($userRoles as $userRole) {
            if(isset($userRole['idUserRole']))
                UserRoles::deleteById($userRole['idUserRole']);
        }
    }

    /**
     * @param $idGroup
     * @param $idSystemRole
     * @param null $idProject
     * @param null $idLocalRole
     * @return bool
     * @throws \Exception
     */
    public static function addGroupRole($idGroup, $idSystemRole, $idProject = NULL, $idLocalRole = NULL)
    {
        $group = self::sqlFetch($idGroup);
        $idController = $group->idController->get();
        $idSystemRole = $idSystemRole?:'NULL';
        $idProject = $idProject?:'NULL';
        $idLocalRole = $idLocalRole?:'NULL';
        $sql = "SELECT GroupRoles.idGroupRole FROM GroupRoles WHERE idGroup = {$idGroup}
                AND (idSystemRole = {$idSystemRole} OR (idProject = {$idProject}
                AND idLocalRole = {$idLocalRole}))";

        if(DB::sql($sql) !== [])
            return true;

        $sql = "INSERT INTO GroupRoles (idSystemRole, idGroup, idProject, idLocalRole) VALUES ('{$idSystemRole}', '{$idGroup}', '{$idProject}', '{$idLocalRole}');";
        DB::sql($sql);

        $sql = "SELECT GroupUsers.idUser FROM GroupUsers WHERE
                GroupUsers.idGroup = {$idGroup}";
        $users = DB::sql($sql);

        $sqlArr = [];
        $sql = "INSERT INTO UserRoles (idUser, idSystemRole, idProject, idLocalRole, grantedBy) VALUES ";
        foreach($users as $user){
            if(isset($user['idUser']))
                $sqlArr[] =  "({$user['idUser']}, '{$idSystemRole}', '{$idProject}', '{$idLocalRole}', '{$idController}')";
        }
        if(sizeof($sqlArr)){
            DB::sql($sql.implode(',', $sqlArr));
        }

        return true;
    }

    /**
     * @param $idGroup
     * @param $idSystemRole
     * @param null $idProject
     * @param null $idLocalRole
     * @param null $idGroupRole
     * @return bool
     * @throws \Exception
     */
    public static function deleteGroupRole($idGroup, $idSystemRole, $idProject = NULL, $idLocalRole = NULL, $idGroupRole = NULL)
    {
        $group = self::sqlFetch($idGroup);
        $idController = $group->idController->get();
        if($idGroupRole){
            $groupRole = GroupRoles::sqlFetch($idGroupRole);
            $idSystemRole = $groupRole->idSystemRole->get()?:'NULL';
            $idProject = $groupRole->idProject->get()?:'NULL';
            $idLocalRole = $groupRole->idProject->get()?:'NULL';

            $groupRoles = [$idGroupRole];
        }
        else{
            $idSystemRole = $idSystemRole?:'NULL';
            $idProject = $idProject?:'NULL';
            $idLocalRole = $idLocalRole?:'NULL';
            $sql = "SELECT GroupRoles.idGroupRole FROM GroupRoles WHERE idGroup = {$idGroup}
                    AND (idSystemRole = {$idSystemRole}
                    OR (idProject = {$idProject}
                    AND idLocalRole = {$idLocalRole}))";
            $groupRoles = DB::sql($sql);
        }
        $sql = "SELECT UserRoles.idUserRole FROM UserRoles
                WHERE (UserRoles.idSystemRole = {$idSystemRole}
                OR (UserRoles.idProject = {$idProject}
                AND UserRoles.idLocalRole = {$idLocalRole}))
                AND UserRoles.grantedBy = {$idController}";
        $userRoles = DB::sql($sql);

        foreach ($groupRoles as $groupRole) {
            if(isset($groupRole['idGroupRole'])) {
                foreach ($userRoles as $userRole) {
                    if(isset($userRole['idUserRole']))
                        UserRoles::deleteById($userRole['idUserRole']);
                }
                GroupRoles::deleteById($groupRole['idGroupRole']);
            }
        }
        return true;
    }

    /**
     * @param $idGroup
     * @return array|null
     */
    public static function getGroupRoles($idGroup)
    {
        $sql = "SELECT GroupRoles.* FROM GroupRoles WHERE idGroup = {$idGroup}";
        $groupRoles = DB::sql($sql);

        return $groupRoles;
    }

}