<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 11/24/15
 * Time: 9:53 AM
 */
namespace eprocess360\v3controllers\Group;

use eprocess360\v3controllers\Group\Model\GroupRoles;
use eprocess360\v3controllers\Group\Model\Groups;
use eprocess360\v3controllers\Group\Model\GroupUsers;
use eprocess360\v3core\Controller\Auth;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Dashboard;
use eprocess360\v3core\Controller\Persistent;
use eprocess360\v3core\Controller\Roles;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Controller\Rules;
use eprocess360\v3core\Controller\Warden;
use eprocess360\v3core\Request\Request;
use eprocess360\v3core\Controller\Warden\Privilege;
use Exception;

/**
 * Class Group
 * @package eprocess360\v3controllers\Group
 */
Class Group extends Controller
{
    use Router, Auth, Persistent, Roles, Warden, Rules, Dashboard;

    protected $staticPath;
    private $messages = [200 => false,
        404 => "404 Not Found - Resource not found",
        403 => "Permissions are not sufficient."];


    /*********************************************   #ROUTING#  **********************************************/


    /**
     * Define the available routes for the module here. This function should not perform any other logic outside of
     * specifying available routes.
     * @throws Exception
     */
    public function routes()
    {
        $this->routes->map('GET', '', function () {
            $this->getGroupsAPI();
        });
        $this->routes->map('POST', '', function () {
            $this->createGroupAPI();
        });
        $this->routes->map('GET', '/[i:idGroup]', function ($idGroup) {
            $this->getGroupAPI($idGroup);
        });
        $this->routes->map('PUT', '/[i:idGroup]', function ($idGroup) {
            $this->editGroupAPI($idGroup);
        });
        $this->routes->map('DELETE', '/[i:idGroup]', function ($idGroup) {
            $this->deleteGroupAPI($idGroup);
        });
        $this->routes->map('GET', '/[i:idGroup]/users', function ($idGroup) {
            $this->getGroupAPI($idGroup);
        });
        $this->routes->map('POST', '/[i:idGroup]/users', function ($idGroup) {
            $this->createGroupUserAPI($idGroup);
        });
        $this->routes->map('PUT', '/[i:idGroup]/users/[i:idUser]', function ($idGroup, $idUser) {
            $this->editGroupUserAPI($idGroup, $idUser);
        });
        $this->routes->map('DELETE', '/[i:idGroup]/users/[i:idUser]', function ($idGroup, $idUser) {
            $this->deleteGroupUserAPI($idGroup, $idUser);
        });
        $this->routes->map('GET', '/[i:idGroup]/roles', function ($idGroup) {
            $this->getGroupRolesAPI($idGroup);
        });
        $this->routes->map('POST', '/[i:idGroup]/roles', function ($idGroup) {
            $this->addGroupRoleAPI($idGroup);
        });
        $this->routes->map('DELETE', '/[i:idGroup]/roles/[i:idGroupRole]', function ($idGroup, $idGroupRole) {
            $this->deleteGroupRoleAPI($idGroup, $idGroupRole);
        });
    }

    /**
     * API Function to get all Groups a user is currently participating within. If ADMIN, return all groups
     */
    public function getGroupsAPI()
    {
        
//        print_r($this->hasPrivilege(Privilege::ADMIN));
        //If the current user is an admin, let them see all groups, else only load the groups the User is currently in.
        $data = Groups::allGroups($this->hasPrivilege(Privilege::ADMIN));
        $this->standardResponse($data);

    }

    /**
     * API Function to create a new group given title and status->isActive.
     * @throws Exception
     * @Required_Privilege: Read
     */
    public function createGroupAPI()
    {
        $this->verifyPrivilege(Privilege::CREATE);

        $data = Request::get()->getRequestBody();
        $title = $data['title'];
        $isActive = $data['status']['isActive'];

        $data = Groups::create($title, $isActive);

        $this->standardResponse($data);
    }

    /**
     * API Function to get a specified group.
     * @param $idGroup
     * @Required_Privilege: Group specific Read
     */
    public function getGroupAPI($idGroup)
    {
        $this->verifyGroupRole($idGroup, Privilege::READ);

        $data = Groups::allGroupUsers($idGroup);

        $this->standardResponse($data);
    }

    /**
     * API Function to edit a specified group.
     * @param $idGroup
     * @Required_Privilege: Write
     */
    public function editGroupAPI($idGroup)
    {
        $this->verifyPrivilege(Privilege::WRITE);
        $group = Groups::sqlFetch($idGroup);

        $data = Request::get()->getRequestBody();
        $title = $data['title'];
        $isActive = $data['status']['isActive'];

        if($title !== null)
            $group->title->set($title);
        if($isActive !== null)
            $group->status->isActive->set($isActive);


        $group->update();
        $data = $group->toArray();

        //$data['roles'] = Groups::getRoles($idGroup);
        $this->standardResponse($data);
    }

    /**
     * API Function to delete a specified Group.
     * @param $idGroup
     * @Required_Privilege: Delete
     */
    public function deleteGroupAPI($idGroup)
    {
        $this->verifyPrivilege(Privilege::DELETE);

        $data = Groups::deleteGroup($idGroup);

        $this->standardResponse($data);
    }

    /**
     * API Function to create a new User within a specified group. If not admin of group, cannot create a new member with higher permissions.
     * @param $idGroup
     * @throws Exception
     * @Required_Privilege: Group specific Create
     */
    public function createGroupUserAPI($idGroup)
    {
        $this->verifyGroupRole($idGroup, Privilege::CREATE);

        $data = Request::get()->getRequestBody();
        $idUser = $data['idUser'];
        $idRole = $data['idRole'];
        $isActive = $data['status']['isActive'];
        $role = Groups::getRole($idRole);

        if(!$this->hasGroupRole($idGroup, Privilege::ADMIN) && $role['flags_0'] & Privilege::CREATE)
            throw new Exception("Cannot grant a role that has the same or higher permissions than your own.");

        $data = GroupUsers::create($idUser, $idRole, $idGroup, $isActive);

        $this->standardResponse($data);
    }

    /**
     * API Function to edit a group user, changing either their isActive status or their grou permissions. Only admin can give any permission with Create.
     * @param $idGroup
     * @param $idUser
     * @throws Exception
     * @Required_Privilege: Group specific Write
     */
    public function editGroupUserAPI($idGroup, $idUser)
    {
        $this->verifyGroupRole($idGroup, Privilege::WRITE);
        
        $data = Request::get()->getRequestBody();
        $isActive = $data['status']['isActive'];
        $idRole = $data['idRole'];

        if(!$this->hasGroupRole($idGroup, Privilege::ADMIN) && $this->hasGroupRole($idGroup, Privilege::CREATE, $idUser))
            throw new Exception("Cannot edit a user who has the same or higher permissions than your own.");

        if(!$this->hasGroupRole($idGroup, Privilege::ADMIN) && $idRole && $this->hasGroupRole($idGroup, Privilege::CREATE, $idUser))
            throw new Exception("Cannot grant a role that has the same or higher permissions than your own.");

        $data = GroupUsers::editGroupUser($idGroup, $idUser, $idRole, $isActive);

        //$data['roles'] = Groups::getRoles($idGroup);
        $this->standardResponse($data);
    }

    /**
     * API Function to delete a specified group user. If not Admin, can only delete someone with lesser permissions.
     * @param $idGroup
     * @param $idUser
     * @throws Exception
     * @Required_Privilege: Group specific Delete
     */
    public function deleteGroupUserAPI($idGroup, $idUser)
    {
        $this->verifyGroupRole($idGroup, Privilege::DELETE);

        if(!$this->hasGroupRole($idGroup, Privilege::ADMIN) && $this->hasGroupRole($idGroup, Privilege::CREATE, $idUser))
            throw new Exception("Cannot remove a user who has the same or higher permissions than your own.");

        $data = GroupUsers::deleteGroupUser($idGroup, $idUser);

        $this->standardResponse($data);
    }

    /**
     * API Function to return the list of Permission granted by being a part of a certain group (not group specific permissions, but like able to review a project).
     * @param $idGroup
     * @Required_Privilege: Admin
     */
    public function getGroupRolesAPI($idGroup)
    {
        $this->verifyPrivilege(Privilege::ADMIN);

        $data = Groups::getGroupRoles($idGroup);

        $this->standardResponse($data);
    }

    /**
     * API Function to add a new permission allocated to all members of a specified group.
     * @param $idGroup
     * @Required_Privilege: Admin
     */
    public function addGroupRoleAPI($idGroup)
    {
        $this->verifyPrivilege(Privilege::ADMIN);

        $data = Request::get()->getRequestBody();

        $idSystemRole = isset($data['idSystemRole'])? $data['idSystemRole']:NULL;
        $idProject = isset($data['idProject'])? $data['idProject']:NULL;
        $idLocalRole = isset($data['idLocalRole'])? $data['idLocalRole']:NULL;

        $data = Groups::addGroupRole($idGroup, $idSystemRole, $idProject, $idLocalRole);

        $this->standardResponse($data);
    }

    /**
     * API Function to remove a permission that has been granted to all members of a specified group.
     * @param $idGroup
     * @param $idGroupRole
     * @Required_Privilege: Admin
     */
    public function deleteGroupRoleAPI($idGroup, $idGroupRole)
    {
        $this->verifyPrivilege(Privilege::ROLE_ADMIN);

        $GroupRoles = GroupRoles::sqlFetch($idGroupRole);

        $data = Groups::deleteGroupRole($idGroup, NULL, NULL, NULL, $idGroupRole);

        $this->standardResponse($data);
    }


    /**********************************************   #HELPER#  **********************************************/


    /**
     * Helper function that sets the Response's template, data, response code, and errors.
     * @param array $data
     * @param bool|false $error
     * @param int $responseCode
     */
    private function standardResponse($data = [], $responseCode = 200, $error = false)
    {
        if($error == false)
            $error = $this->messages[$responseCode];

        $responseData = [
            'data' => $data
        ];

        $response = $this->getResponseHandler();
        $response->setResponse($responseData, $responseCode, false);
        $response->setTemplate('groups.base.html.twig', 'server');
        $response->setTemplate('module.groups.handlebars.html', 'client', $this);
        if($error)
            $response->setErrorResponse(new Exception($error));
    }

    /**
     * Helper function that verifies a Role under a specific Group using that groups Controller Id.
     * @param $idGroup
     * @param $role
     * @throws \Exception
     */
    private function verifyGroupRole($idGroup, $role){
        $group = Groups::sqlFetch($idGroup);
        $idController = $group->idController->get();
        $idControllerOriginal = $this->getId();

        $this->setId($idController);
        $this->verifyPrivilege($role);
        $this->setId($idControllerOriginal);
    }

    /**
     * Helper Function that returns a boolean if the user has the specified group specific permissions.
     * @param $idGroup
     * @param $role
     * @param int $idUser
     * @return bool
     * @throws Exception
     * @throws \eprocess360\v3core\Controller\ControllerException
     */
    private function hasGroupRole($idGroup, $role, $idUser = 0){
        $group = Groups::sqlFetch($idGroup);
        $idController = $group->idController->get();
        $idControllerOriginal = $this->getId();

        $this->setId($idController);
        $result = $this->hasPrivilege($role, null, null, $idUser);
        $this->setId($idControllerOriginal);

        return $result;
    }


    /*********************************************   #WORKFLOW#  *********************************************/





    /******************************************   #INITIALIZATION#  ******************************************/
    public function dashboardInit()
    {
        $this->setDashboardIcon('blank fa fa-users');
    }




    /*********************************************   #TRIGGERS#  *********************************************/
}