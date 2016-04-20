<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 8/19/2015
 * Time: 7:16 PM
 */

namespace eprocess360\v3core\Model;
use eprocess360\v3core\DB;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Entry\Bit;
use eprocess360\v3core\Keydict\Entry\Bits;
use eprocess360\v3core\Keydict\Entry\Bits8;
use eprocess360\v3core\Keydict\Entry\FixedString128;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;

/**
 * @package eprocess360\v3core\Model
 */
class UserRoles extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idUserRole', 'User Roles ID'),
            IdInteger::build('idUser', 'User ID')->joinsOn(Users::model())->index(),
            IdInteger::build('idSystemRole', 'System Role ID')->joinsOn(Roles::model())->index(),
            IdInteger::build('idProject', 'Project ID')->joinsOn(Projects::model())->index(),
            IdInteger::build('idLocalRole', 'Local Role ID')->index(),
            IdInteger::build('grantedBy', 'Role Granted By')->joinsOn(Users::model(), 'idUser')->index()
        )->setName('UserRoles')->setLabel('UserRoles');
    }

    /**
     * @param int $idUser
     * @param int $idSystemRole
     * @param int $idProject
     * @param int $idLocalRole
     * @param int $grantedBy
     * @return UserRoles
     */
    public static function make($idUser = 0, $idSystemRole = 0, $idProject = 0, $idLocalRole = 0, $grantedBy = 0)
    {
        $rowData = [
            'idUser' => $idUser,
            'idSystemRole' => $idSystemRole,
            'idProject' => $idProject,
            'idLocalRole' => $idLocalRole,
            'grantedBy' => $grantedBy
        ];

        return self::UserRoleConstruct($rowData);
    }

    /**
     * @param array $rowData
     * @return UserRoles
     */
    public static function UserRoleConstruct($rowData = [])
    {
        $instance = new self();
        $instance->data = self::keydict();
        $instance->data->acceptArray($rowData);
        return $instance;
    }

    /**
     * @param $idUser
     * @param $idSystemRole
     * @param $idProject
     * @param $idLocalRole
     * @param $grantedBy
     * @return mixed
     */
    public static function createProjectUser($idUser, $idSystemRole, $idProject, $idLocalRole, $grantedBy) {
        $f = static::make($idUser, $idSystemRole, $idProject, $idLocalRole, $grantedBy);

        $f->insert();

        $result = $f->data->toArray();

        return $result;
    }

    /**
     * @param $idProject
     * @return array|null
     * @throws \Exception
     */
    public static function getProjectUsers($parentId, $idProject)
    {
        $columns = "UserRoles.idUser, UserRoles.idProject, UserRoles.idLocalRole, Roles.flags_0 as flags, CONCAT(Users.firstName, ' ', Users.lastName) AS userName";

        $sql = "SELECT {$columns}, ((CASE WHEN Controllers.class = 'Group' THEN Groups.title ELSE 'self' END)) as grantedBy
                FROM UserRoles
                  LEFT JOIN Roles ON UserRoles.idSystemRole = Roles.idSystemRole
                  LEFT JOIN Controllers ON UserRoles.grantedBy = Controllers.idController
                  LEFT JOIN Groups ON Controllers.idController = Groups.idController
                  LEFT JOIN Users ON Users.idUser = UserRoles.idUser
                WHERE
                  UserRoles.idProject = {$idProject}
                  OR Roles.idSystemRole = {$parentId}";

        $projectUsers = DB::sql($sql);

        $keydict = Table::build(
            IdInteger::build('idUser', 'User ID'),
            IdInteger::build('idProject', 'Project ID'),
            IdInteger::build('idLocalRole', 'Local Role ID'),
            FixedString128::build('grantedBy', 'Role Granted By'),
            FixedString128::build('userName', 'User Name'),
            Bits8::make(
                'flags',
                Bit::build(0, 'isRead', 'Read'),
                Bit::build(1, 'isWrite', 'Write'),
                Bit::build(2, 'isCreate', 'Create'),
                Bit::build(3, 'isDelete', 'Delete'),
                Bit::build(4, 'isAdmin', 'Administrative')
            )
        );

        foreach ($projectUsers as &$projectUser) {
            $projectUser = $keydict->wakeup($projectUser)->toArray();
        }

        return $projectUsers;
    }

    public static function getProjectUser($idUser, $parentId, $idProject) {
        $columns = "UserRoles.idUser, UserRoles.idProject, UserRoles.idLocalRole, Roles.flags_0 as flags, CONCAT(Users.firstName, ' ', Users.lastName) AS userName";
        $idUser = (int)$idUser;

        $sql = "SELECT {$columns}, ((CASE WHEN Controllers.class = 'Group' THEN Groups.title ELSE 'self' END)) as grantedBy
                FROM UserRoles
                  LEFT JOIN Roles ON UserRoles.idSystemRole = Roles.idSystemRole
                  LEFT JOIN Controllers ON UserRoles.grantedBy = Controllers.idController
                  LEFT JOIN Groups ON Controllers.idController = Groups.idController
                  LEFT JOIN Users ON Users.idUser = UserRoles.idUser
                WHERE
                  (UserRoles.idProject = {$idProject}
                    OR Roles.idSystemRole = {$parentId}
                  )
                  AND UserRoles.idUser = {$idUser}";

        $projectUsers = DB::sql($sql);

        $keydict = Table::build(
            IdInteger::build('idUser', 'User ID'),
            IdInteger::build('idProject', 'Project ID'),
            IdInteger::build('idLocalRole', 'Local Role ID'),
            FixedString128::build('grantedBy', 'Role Granted By'),
            FixedString128::build('userName', 'User Name'),
            Bits8::make(
                'flags',
                Bit::build(0, 'isRead', 'Read'),
                Bit::build(1, 'isWrite', 'Write'),
                Bit::build(2, 'isCreate', 'Create'),
                Bit::build(3, 'isDelete', 'Delete'),
                Bit::build(4, 'isAdmin', 'Administrative')
            )
        );

        foreach ($projectUsers as &$projectUser) {
            $projectUser = $keydict->wakeup($projectUser)->toArray();
        }

        return $projectUsers;
    }

    /**
     * @param $idUserRole
     * @return bool
     */
    public static function deleteProjectUser($idUserRole)
    {
        self::deleteById($idUserRole);

        return true;
    }

    /**
     * @param $idUserRole
     * @param $idProject
     * @param $idLocalRole
     * @return array
     * @throws Keydict\Exception\InvalidValueException
     * @throws Keydict\Exception\KeydictException
     * @throws \Exception
     */
    public static function editProjectUser($idUserRole, $idProject, $idLocalRole)
    {
        $userRole = self::sqlFetch($idUserRole);

        if($idProject !== NULL)
            $userRole->idProject->set($idProject);
        if($idLocalRole !== NULL)
            $userRole->idLocalRole->set($idLocalRole);

        $userRole->update();

        return $userRole->toArray();
    }

    /**
     * @param $idUser
     * @param $idProject
     * @param $localRoles
     * @return array|null
     * @throws \Exception
     */
    public static function getSpecifiedUserRoles($grantedBy, $idUser, $idProject)
    {
        $sql = "SELECT *
                FROM UserRoles
                WHERE UserRoles.idProject = {$idProject}
                  AND UserRoles.idUser = {$idUser}
                  AND UserRoles.grantedBy = {$grantedBy}";

        $projectUsers = DB::sql($sql);

        $result = [];
        foreach ($projectUsers as &$projectUser) {
            $projectUser = self::keydict()->wakeup($projectUser)->toArray();
            $result[$projectUser['idLocalRole']][] = $projectUser;
        }

        return $result;
    }

    /**
     * @param $idUser
     */
    public static function getUserProjects($idUser) {
        $idUser = (int)$idUser;

        $sql = "SELECT
                  UserRoles.idUser,
                  Projects.idProject,
                  Projects.title,
                  Projects.state,
                  Projects.idController,
                  GROUP_CONCAT(UserRoles.idLocalRole) AS localRoles
                FROM UserRoles
                  INNER JOIN Projects ON Projects.idProject = UserRoles.idProject
                WHERE UserRoles.idUser = {$idUser}
                GROUP BY Projects.idProject";

        $results = DB::sql($sql);
        return $results;
    }
}