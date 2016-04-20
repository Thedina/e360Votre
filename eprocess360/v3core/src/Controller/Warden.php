<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 8/24/2015
 * Time: 4:06 PM
 */

namespace eprocess360\v3core\Controller;
use eprocess360\v3core\Controller\Warden\Role;
use eprocess360\v3core\Controller\Warden\Scope;
use eprocess360\v3core\Controller\Warden\Privilege;
use eprocess360\v3core\DB;
use eprocess360\v3core\User;
use Exception;

/**
 * Class Warden
 * Allows for a class to grant access to a User to all or parts of the system.  The Warden additionally has its own
 * privileges from the User it is attached to and it cannot give out more privileges than it already has.  For this
 * reason any Class that is extended with Warden must have the getWardenUser() available.
 * @package eprocess360\v3core
 */
trait Warden
{
    /** @var Scope */
    private $scope;
    private $idUser;

    /**
     * Should be called by the inheritor.  For planned functionality.
     */
    protected function __construct()
    {
    }

//    /**
//     * This Warden will grant one or more given Roles to one given User
//     * @param User $user
//     * @param Role ...$roles
//     * @return $this
//     */
//    final public function grantRolesToUser(User $user, Role ...$roles)
//    {
//        foreach ($roles as $role) {
//            $this->verifyRole($role);
//            $this->grant($user, $role);
//        }
//        return $this;
//    }
//
//    /**
//     * This Warden will grant one or more Users the given Role
//     * @param Role $role
//     * @param User ...$users
//     * @return $this
//     */
//    final public function grantRoleToMany(Role $role, User ...$users)
//    {
//        $this->verifyRole($role);
//        foreach ($users as $user) {
//            $this->grant($user, $role);
//        }
//        return $this;
//    }
//
//    /**
//     * This Warden will grant one or more given Roles to one given User
//     * @param User $user
//     * @param Role ...$roles
//     * @return $this
//     */
//    final public function revokeRolesFromUser(User $user, Role ...$roles)
//    {
//        foreach ($roles as $role) {
//            $this->verifyRole($role);
//            $this->revoke($user, $role);
//        }
//        return $this;
//    }
//
//    /**
//     * This Warden will grant one or more Users the given Role
//     * @param Role $role
//     * @param User ...$users
//     * @return $this
//     */
//    final public function revokeRoleFromMany(Role $role, User ...$users)
//    {
//        $this->verifyRole($role);
//        foreach ($users as $user) {
//            $this->revoke($user, $role);
//        }
//        return $this;
//    }
//
//    /**
//     * The core Role grant function.  Private because it doesn't verify this Warden can grant the Roles for efficiency
//     * purposes, but maybe this will change.
//     * @param User $user
//     * @param Role $role
//     * @return Warden
//     */
//    final private function grant(User $user, Role $role)
//    {
//        switch ($role->isGlobal()) {
//            case true:
//                $sql = "INSERT INTO UserRoles (idUser, idSystemRole, grantedBy) VALUES ({$user->getIdUser()}, {$role->getComponentID()}, {$this->getIdUser()})";
//                break;
//            default:
//                $sql = "INSERT INTO UserRoles (idUser, idProject, idLocalRole, grantedBy) VALUES ({$user->getIdUser()}, {$role->getController()->getObjectId()}, {$role->getComponentID()}, {$this->getIdUser()})";
//                break;
//        }
//        echo $sql;
//        return $this;
//    }
//
//    /**
//     * The exact opposite of grant.  Works with specific instances of roles.
//     * @param User $user
//     * @param Role $role
//     * @return Warden
//     */
//    final private function revoke(User $user, Role $role)
//    {
//        switch ($role->isGlobal()) {
//            case true:
//                $sql = "DELETE FROM UserRoles WHERE idUser = {$user->getIdUser()} AND idSystemRole = {$role->getComponentID()}";
//                break;
//            default:
//                $sql = "DELETE FROM UserRoles WHERE idUser = {$user->getIdUser()} AND idLocalRole = {$role->getComponentID()} AND idProject = {$role->getController()->getObjectId()}";
//                break;
//        }
//        DB::sql($sql);
//        return $this;
//    }
//
//    /**
//     * Completely removes a User from a project
//     * @param User $user
//     * @param Controller|Project $controller
//     * @return Warden
//     * @internal param Project $project
//     */
//    final public function revokeProjectFromUser(User $user, Controller $controller)
//    {
//        $this->verifyRole(Role::build(0,'adminController', 'Administrative Access to Controller', Role::ADMIN)->setController($controller));
//        $sql = "DELETE FROM UserRoles WHERE idUser = {$user->getIdUser()} AND idProject = {$controller->getObjectId()}";
//        DB::sql($sql);
//        return $this;
//    }
//
//    /**
//     * Returns the raw verification information from the last hasRole query
//     * @return array
//     */
//    final public function lastVerification()
//    {
//        return $this->lastVerification;
//    }

    /**
     * gets the current User
     * @return User
     */
    final public function wardenGetUser()
    {
        global $pool;
        return $pool->User;
    }

    /**
     * Helper function to check the privilege of a specific User
     * @param $idUser
     * @return $this
     */
    final private function wardenSetIdUser($idUser)
    {
        $this->idUser = $idUser;
        return $this;
    }

    /**
     * Checks the privileges of the current user against their pre feteched permission array.
     * @param Scope $scope
     * @param Warden\Privilege[] $privileges
     * @return bool
     */
    private function checkReceivedPrivilege($scope, $privileges)
    {
        $user = $this->wardenGetUser();
        //['idController'=>Flags_0,...]
        $controllerPermissions = $user->getControllerPermissions();
        //['idProject'=>[Role,Role...],///]
        $projectPermissions = $user->getProjectPermissions();

        $results = 0;

        /**
         * If we have idController and Privileges, we can verify using global Roles
         */
        if ($scope->getIdController()) {
            $idController = (int)$scope->getIdController();
            /** @var Privilege $privilege */
            foreach ($privileges as $privilege) {
                if(isset($controllerPermissions['0']))
                    $results = $results | ($controllerPermissions['0'] & $privilege->get());
                if(isset($controllerPermissions[$idController]))
                    $results = $results | ($controllerPermissions[$idController] & $privilege->get());
            }
        }

        /**
         * If we have idLocalRoles we can use them to verify as well
         */
        if ($scope->hasIdLocalRole() && $scope->getIdProject()) {
            foreach ($scope->getIdLocalRole() as $role) {
                /** This option will check the idLocalRole */
                if(isset($projectPermissions[(string)$scope->getIdProject()]))
                    $results = $results | (int)isset($projectPermissions[$scope->getIdProject()][$role]);
            }
        }

        if ($results) {
            return true;
        }
        return false;
    }

    /**
     * Checks to see if the Warden has the given Roles and returns a boolean.
     * @param Scope $scope
     * @param Warden\Privilege[] ...$privileges
     * @return bool
     * @throws Exception
     */
    final public function checkPrivilege(Scope $scope, Privilege ...$privileges)
    {
        if(!$this->idUser)
            return $this->checkReceivedPrivilege($scope, $privileges);
        else
            $idUser = $this->idUser;
        $base = "SELECT * FROM UserRoles LEFT JOIN Roles ON Roles.idSystemRole = UserRoles.idSystemRole WHERE UserRoles.idUser = {$idUser} ";
        $options = [];
        /** @var Privilege $privilege */

        /**
         * If we have idController and Privileges, we can verify using global Roles
         */
        if ($scope->getIdController()) {
            foreach ($privileges as $privilege) {
                $controllerInclude = $scope->getIdController() != null ? "IN (0, {$scope->getIdController()})" : '= 0';
                $options[] = " (Roles.flags_0 & {$privilege->get()} AND Roles.idController {$controllerInclude}) ";
            }
        }

        /**
         * If we have idLocalRoles we can use them to verify as well
         */
        if ($scope->hasIdLocalRole() && $scope->getIdProject()) {
            foreach ($scope->getIdLocalRole() as $role) {
                /** This option will check the idLocalRole */
                $options[] = " (UserRoles.idLocalRole = {$role} AND UserRoles.idProject = {$scope->getIdProject()}) ";
            }
        }

        if (!sizeof($options)) {
            throw new Exception("No roles to verify against.");
        }

        $results = DB::sql($sql = $base.'AND ('.implode('OR',$options).')');
        if ($results) {
            return true;
        }
        return false;
    }

    /**
     * Returns a Boolean whether the user has the specified privilege or not.
     * @param $privilege
     * @param Controller|null $rules
     * @param Controller|null $warden
     * @param int $idUser
     * @return bool
     * @throws Exception
     */
    final public function hasPrivilege($privilege, Controller $rules = null, Controller $warden = null, $idUser = 0)
    {
        $scope = $this->makeScope($privilege, $rules, $warden);

        if($idUser)
            $this->wardenSetIdUser($idUser);
        $result = $this->checkPrivilege($scope, $privilege);
        $this->wardenSetIdUser(0);
        return $result;
    }

    /**
     * Verifies the Warden has the given Roles or throws an Exception
     * @param $privilege
     * @param Controller $rules
     * @param Controller $warden
     * @throws Exception
     */
    final public function verifyPrivilege($privilege, Controller $rules = null, Controller $warden = null)
    {
        $scope = $this->makeScope($privilege, $rules, $warden);
        if (!$this->checkPrivilege($scope, $privilege)) {
            throw new Exception("Unable to verify access for {$this->wardenGetUser()->getFullName()}.");
        }
    }

    /**
     * Helper function that creates the scope in which the Privileges are checked a against.
     * @param $privilege
     * @param Controller $rules
     * @param Controller $warden
     * @return Scope
     * @throws Exception
     */
    private function makeScope(&$privilege, &$rules, &$warden)
    {
        $scope = new Scope();
        if (!$warden) {
            $warden = $this;
        }
        if (!$rules) {
            $rules = $this;
        }
        /** @var Controller|Rules|Project|Warden $warden */
        /** @var Controller|Rules|Project|Warden $rules */
        $scope->setIdController($warden->getId());
        $state = null;
        if ($warden->uses('Project') && $warden->hasObjectId()) {
            $scope->setIdProject($warden->getObjectId());
            $state = $warden->getProjectState()->getName();
        }
        if ($rules->uses('Rules')) {
            $scope->setIdLocalRole($rules->getRules($privilege, $state, $warden));
        }
        if (!is_object($privilege)) {
            $privilege = new Privilege($privilege);
        }
        return $scope;
    }

    /**
     * Grants a Role to this User
     * @param Warden\Role[] ...$roles
     */
    public function grant(Role ...$roles)
    {
        $this->wardenGrantRole($this->wardenGetUser(), ...$roles);
    }

    /**
     * Revokes a Role from this User
     * @param Warden\Role[] ...$roles
     */
    public function revoke(Role ...$roles)
    {
        $this->wardenRevokeRole($this->wardenGetUser(), ...$roles);
    }

    /**
     * Grants a Role to the specified User
     * @param User $user
     * @param Warden\Role[] ...$roles
     * @return int
     * @throws ControllerException
     * @throws Exception
     */
    public function wardenGrantRole(User $user, Role ...$roles)
    {
        $idUser = $user->getIdUser();
        $values = [];
        $roleList = [];
        /** @var Controller $this */
        $projWithNull = $this->getObjectId()?:'null';
        foreach ($roles as $role) {
            $values[$role->getId()] = "({$idUser},{$role->getId()},{$this->getId()},{$projWithNull})";
            $roleList[] = $role->getId();
        }
        $roleList = "'".implode("','", $roleList)."'";
        $projWithEqNull = $this->getObjectId()?"= '{$this->getObjectId()}'":'IS NULL';
        $sql = "SELECT idLocalRole FROM UserRoles WHERE `idUser` = '{$idUser}' AND `idLocalRole` IN ($roleList) AND `idProject` {$projWithEqNull}";
        $existing = DB::sql($sql);
        foreach ($existing as $e) {
            $off = $e['idLocalRole'];
            if (isset($values[$off])) {
                unset($values[$off]);
            } else {
                // there are too many records in the database - we should clean them up
                $sql = "DELETE FROM UserRoles  WHERE `idUser` = '{$idUser}' AND `idLocalRole` = '{$off}' AND `idProject` {$projWithEqNull} LIMIT 1";
                DB::sql($sql);
            }
        }
        if (sizeof($values)) {
            $values = implode(',', $values);
            $sql = "INSERT INTO UserRoles (`idUser`,`idLocalRole`,`grantedBy`,`idProject`) VALUES {$values}";
            DB::sql($sql);
            global $pool;
            if($user->getIdUser() == $pool->User->getIdUser())
                $pool->User->addPermissions(NULL, NULL, $projWithNull, $roles);
            return 1;
        }
        return 0;
    }

    /**
     * Revokes a Role from a specified User
     * @param $idUser
     * @param Warden\Role[] ...$roles
     * @throws Exception
     */
    public function wardenRevokeRole($idUser, Role ...$roles)
    {
        //todo cleanup
        global $pool;
        $idUser = (int)$idUser;
        $values = [];
        foreach ($roles as $role) {
            /** @var Controller $this */
            $values[] = $role->getId();
        }
        $values = implode(',', $values);
        $sql = "DELETE FROM UserRoles WHERE 'idUser' = {$idUser} AND 'idLocalRole' IN ({$values}) AND 'grantedBy' = {$this->getId()})";
        DB::sql($sql);
    }

    /**
     * Returns a user's permission array for the scope of the current controller. This array is used for Front End and is only an indicator.
     * @param Controller|null $rules
     * @param Controller|null $warden
     * @return array
     * @throws ControllerException
     */
    public function getPermissions(Controller $rules = null, Controller $warden = null)
    {
        $user = $this->wardenGetUser();
        //['idController'=>Flags_0,...]
        $controllerPermissions = $user->getControllerPermissions();
        //['idProject'=>[Role,Role...],///]
        $projectPermissions = $user->getProjectPermissions();

        /** @var Project|Controller $warden */
        /** @var Project|Controller|Rules $rules */
        $scope = new Scope();
        if (!$warden) {
            $warden = $this;
        }
        if (!$rules) {
            $rules = $this;
        }
        /** @var Controller|Rules|Project|Warden $warden */
        /** @var Controller|Rules|Project|Warden $rules */
        $scope->setIdController($warden->getId());
        $state = null;
        if ($warden->uses('Project') && $warden->hasObjectId()) {
            $scope->setIdProject($warden->getObjectId());
            $state = $warden->getProjectState()->getName();
        }

        $state = null;
        if ($warden->uses('Project') && $warden->hasObjectId())
            $state = $warden->getProjectState()->getName();

        $flags = 0;
        if ($scope->getIdController()) {
            $idController = (int)$scope->getIdController();
            if(isset($controllerPermissions[$idController]))
                $flags = $controllerPermissions[$idController];
        }

        if ($scope->getIdProject()) {
            if(isset($projectPermissions[$scope->getIdProject()]))
                $flags = $flags | $rules->getRuleFlags($projectPermissions[$scope->getIdProject()], $state, $warden);
        }

        if(isset($controllerPermissions[0]))
            $flags = $flags | $controllerPermissions[0];
        return $this->flagsToArray($flags);
    }

    /**
     * Given a set of flags, returns a boolean permission array.
     * @param $flags
     * @return array
     */
    private function flagsToArray($flags)
    {
        return ['READ' =>   (bool)(Privilege::READ & $flags),
                'WRITE' =>  (bool)(Privilege::WRITE & $flags),
                'CREATE' => (bool)(Privilege::CREATE & $flags),
                'DELETE' => (bool)(Privilege::DELETE & $flags),
                'ADMIN' =>  (bool)(Privilege::ADMIN & $flags)];
    }

    /**
     * Returns a boolean permission array with false entities.
     * @return array
     */
    public static function wardenlessFlags()
    {
        return ['READ' =>   false,
            'WRITE' =>  false,
            'CREATE' => false,
            'DELETE' => false,
            'ADMIN' =>  false,];
    }

//    /**
//     * @param $idController
//     * @param $flags
//     * @throws Exception
//     */
//    final static public function verifyRoleByIdController($idController, $flags)
//    {
//        global $pool;
//        if (!self::hasRoleByIdController($idController, $flags)) {
//            $fullName = $pool->User->getFullName();
//            throw new Exception("Unable to verify access for {$fullName}.");
//        }
//    }
//
//    /**
//     * @param $idController
//     * @param $flags
//     * @return bool
//     * @throws Exception
//     */
//    final static public function hasRoleByIdController($idController, $flags)
//    {
//        global $pool;
//        $idUser = $pool->User->getIdUser();
//        $base = "SELECT * FROM UserRoles LEFT JOIN Roles ON Roles.idSystemRole = UserRoles.idSystemRole WHERE UserRoles.idUser = {$idUser} ";
//        $options = [];
//        /** We will use the flags and possibly the controller id to determine a match.  We only need one
//         * match, but all the Flags from a single role must match */
//        $controllerInclude = "IN (0, {$idController})";
//        $options[] = " (Roles.flags_0 & {$flags} AND Roles.idController {$controllerInclude}) ";
//
//        $results = DB::sql($sql = $base.'AND ('.implode('OR',$options).')');
//        if ($results) {
//            return true;
//        }
//        return false;
//    }
//
//    /**
//     * @return int
//     * @throws Exception
//     */
//    public function getIdUser()
//    {
//        global $pool;
//        $pool->User->getIdUser();
//    }
//
//    /**
//     * @return string
//     * @throws Exception
//     */
//    public function getFullName()
//    {
//        global $pool;
//        $pool->User->getFullName();
//    }
//
//    /**
//     * @return null
//     */
//    public function getLastFailure()
//    {
//        return $this->lastFailure;
//    }
//
//    /**
//     * @return int
//     */
//    public function getSecondsToVerify()
//    {
//        return $this->secondsToVerify;
//    }
}