<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 1/25/16
 * Time: 1:53 PM
 */

namespace eprocess360\v3modules\ProjectUser;


use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Module;
use eprocess360\v3core\Controller\Persistent;
use eprocess360\v3core\Controller\Roles;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Controller\Rules;
use eprocess360\v3core\Controller\Trigger\InterfaceTriggers;
use eprocess360\v3core\Controller\Triggers;
use eprocess360\v3core\Controller\Warden\Privilege;
use eprocess360\v3core\Model\UserRoles;
use eprocess360\v3core\Request\Request;

/**
 * Class ProjectUser
 * @package eprocess360\v3modules\ProjectUser
 */
class ProjectUser extends Controller implements InterfaceTriggers
{
    use Router, Module, Persistent, Triggers, Rules;
    protected $staticPath;
    private $messages = [200 => false,
        404 => "404 Not Found - Resource not found",
        403 => "Permissions are not sufficient."];
    public $identified;


    /*********************************************   #ROUTING#  **********************************************/


    /**
     * Define the available routes for the module here. This function should not perform any other logic outside of
     * specifying available routes.
     */
    public function routes()
    {
        $this->routes->map('GET', '', function () {
            $this->getProjectUsers();
        });
        $this->routes->map('PUT', '/[i:idUser]', function ($idUser) {
            $this->editProjectUser($idUser);
        });
    }

    /**
     * API Function to get the Project Users of the current idProject.
     * @Required_Privilege: Read
     */
    public function getProjectUsers()
    {
        $this->verifyPrivilege(Privilege::READ);

        $rawProjectUsers = UserRoles::getProjectUsers($this->getParent()->getId(), $this->getParent()->getObjectId());
        $data = [];
        foreach($rawProjectUsers as $rawProjectUser){
            $id = $rawProjectUser['idUser'];
            $idLocalRole = $rawProjectUser['idLocalRole'];
            unset($rawProjectUser['idLocalRole']);
            $grantedBy = $rawProjectUser['grantedBy'];
            unset($rawProjectUser['grantedBy']);
            $globalRoles = $rawProjectUser['flags'];
            unset($rawProjectUser['flags']);

            $rawProjectUser['localRoles'][] = ["idLocalRole"=>$idLocalRole, "grantedBy" => $grantedBy];
            $rawProjectUser['globalRoles'] = $globalRoles;


            if(!isset($data[$rawProjectUser['idUser']]))
                $data[$id] = $rawProjectUser;
            else{
                $data[$id]['userName'] = $rawProjectUser['userName'];
                $data[$id]['localRoles'][] = ["idLocalRole"=>$idLocalRole, "grantedBy" => $grantedBy];
                foreach($data[$id]['globalRoles'] as $key => $value)
                    $data[$id]['globalRoles'][$key] = $data[$id]['globalRoles'][$key] || $idLocalRole['globalRoles'][$key];
            }
        }

        $dataArray = [];
        foreach($data as $d) {
            $dataArray[] = $d;
        }

        $this->standardResponse($dataArray);
    }

    /**
     * API Function to create a new User Role for this project given idUser, and idLocalRole.
     * @Required_Privilege: Create
     */
    public function createProjectUser()
    {
        $this->verifyPrivilege(Privilege::CREATE);

        $data = Request::get()->getRequestBody();

        $idUser = isset($data['idUser'])? $data['idUser']:null;
        $idLocalRole = isset($data['idLocalRole'])? $data['idLocalRole']:null;
        $idProject = $this->getParent()->getObjectId();
        $grantedBy = $this->getParent()->getid();

        $data = UserRoles::createProjectUser($idUser, NULL, $idProject, $idLocalRole, $grantedBy);
        //$data = UserRoles::getProjectUser($idUser, $this->getParent()->getId(), $idProject);

        $this->standardResponse($data);
    }

    /**
     * API Function to edit an existing UserRole. Specifically the idLocalRole. DO EVERYTHING BECAUSE APPARENTLY FUCK API
     * @param $idUser
     * @Required_Privilege: CREATE, DELETE, WRITE
     */
    public function editProjectUser($idUser)
    {
        //because fuck API standards apparently
        $this->verifyPrivilege(Privilege::CREATE);
        $this->verifyPrivilege(Privilege::DELETE);
        $this->verifyPrivilege(Privilege::WRITE);

        $data = Request::get()->getRequestBody();

        $idProject = $this->getParent()->getObjectId();
        $localRoles = isset($data['localRoles'])? $data['localRoles']:null;

        $newLocalRoles = [];
        foreach($localRoles as $role) {
            if($role['grantedBy'] === 'self') {
                $newLocalRoles[$role['idLocalRole']] = $role['idLocalRole'];
            }
        }

        $userRoles = UserRoles::getSpecifiedUserRoles($this->getParent()->getId(), $idUser, $idProject);

        foreach($userRoles as $key=>$value) {
            if(!isset($newLocalRoles[$key])) {
                foreach($userRoles[$key] as $userRole)
                    UserRoles::deleteProjectUser($userRole['idUserRole']);
            }
        }

        foreach($newLocalRoles as $key=>$value) {
            if(!isset($userRoles[$key])) {
                UserRoles::createProjectUser($idUser, NULL, $idProject, $key, $this->getParent()->getId());
            }
        }

        $rawProjectUsers = UserRoles::getProjectUser($idUser, $this->getParent()->getId(), $idProject);

        foreach($rawProjectUsers as $rawProjectUser){
            $id = $rawProjectUser['idUser'];
            $idLocalRole = $rawProjectUser['idLocalRole'];
            unset($rawProjectUser['idLocalRole']);
            $grantedBy = $rawProjectUser['grantedBy'];
            unset($rawProjectUser['grantedBy']);
            $globalRoles = $rawProjectUser['flags'];
            unset($rawProjectUser['flags']);

            $rawProjectUser['localRoles'][] = ["idLocalRole"=>$idLocalRole, "grantedBy" => $grantedBy];
            $rawProjectUser['globalRoles'] = $globalRoles;


            if(!isset($data[$rawProjectUser['idUser']]))
                $data[$id] = $rawProjectUser;
            else{
                $data[$id]['userName'] = $rawProjectUser['userName'];
                $data[$id]['localRoles'][] = ["idLocalRole"=>$idLocalRole, "grantedBy" => $grantedBy];
                foreach($data[$id]['globalRoles'] as $key => $value)
                    $data[$id]['globalRoles'][$key] = $data[$id]['globalRoles'][$key] || $idLocalRole['globalRoles'][$key];
            }
        }

        $dataSingle = array_pop($data);

        $this->standardResponse($dataSingle);
    }


    /**********************************************   #HELPER#  **********************************************/


    private function standardResponse($data = [], $responseCode = 200, $error = false)
    {
        global $pool;
        if($error == false)
            $error = $this->messages[$responseCode];

        $responseData = [
            'error' => $error,
            'data' => $data
        ];

        $response = $this->getResponseHandler();

        /** @var Roles|Controller $parent */
        $parent = $this->getParent();
        $roles = $parent->getRolesById();

        $response->addResponseMeta('Roles', $roles);
        // TODO Manually add groups metadata until we have a better way to handle this
        $groupsMeta = [
            'siteUrl'=>$pool->SysVar->siteUrl(),
            'apiPrefix'=>'/api/v'.API_VERSION,
            'static'=>$pool->SysVar->siteUrl().'/eprocess360/v3controllers/src/Group/static',
            'name'=>'groups',
            'description'=>NULL,
            'api'=>'/groups',
            'path'=>$pool->SysVar->siteUrl().'/groups',
            'apiPath'=>$pool->SysVar->siteUrl().'/api/v'.API_VERSION.'/groups'
        ];

        $response->addResponseMeta('Group', $groupsMeta);
        $response->setTemplate('projectusers.base.html.twig', 'server');
        $response->setTemplate('module.projectusers.handlebars.html', 'client', $this);

        $response->setResponse($responseData, $responseCode, false);
        if($error)
            $response->setErrorResponse(new \Exception($error));
    }

}