<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 12/15/15
 * Time: 2:14 PM
 */

namespace eprocess360\v3controllers\Controller;

use eprocess360\v3controllers\Group\Model\GroupProjects;
use eprocess360\v3core\Controller\Auth;
use eprocess360\v3core\Controller\Children;
use eprocess360\v3core\Controller\Controller as ControllerTrait;
use eprocess360\v3core\Controller\Dashboard;
use eprocess360\v3core\Controller\Project;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Controller\Warden;
use eprocess360\v3core\Controller\Warden\Privilege;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model\Controllers;
use eprocess360\v3core\Model\ProjectControllers;
use eprocess360\v3core\Model\Projects;
use eprocess360\v3core\Request\Request;
use eprocess360\v3core\View\Column;
use eprocess360\v3core\View\StandardView;
use Exception;

/**
 * Class Controller
 * @package eprocess360\v3controllers\Controller
 */
class Controller extends ControllerTrait
{
    use Router, Auth, Children, Warden, Dashboard;

    private $objectType;


    /*********************************************   #ROUTING#  **********************************************/

    /**
     * Define the available routes for the module here. This function should not perform any other logic outside of
     * specifying available routes.
     * @throws Exception
     */
    public function routes()
    {
        $this->routes->map('GET|POST|PATCH|PUT|DELETE', '/[i:idController]/projects/[i:idProject]/[*:trailing]?', function ($idController, $idProject) {
            $this->getProjectControllerAPI($idController, $idProject);
        });
        $this->routes->map('POST', '/[i:idController]/projects', function ($idController) {
            $this->createProjectAPI($idController);
        });
        $this->routes->map('PUT', '/[i:idController]', function ($idController) {
            $this->editProjectControllersAPI($idController);
        });
        $this->routes->map('DELETE', '/[i:idController]', function ($idController) {
            $this->deleteProjectControllersAPI($idController);
        });
        $this->routes->map('GET|POST|PATCH|PUT|DELETE', '/[i:idController]/[*:trailing]?', function ($idController) {
            $this->getControllerAPI($idController);
        });
        $this->routes->map('GET', '', function () {
            $this->getProjectControllersAPI();
        });
        $this->routes->map('POST', '', function () {
            $this->createProjectControllerAPI();
        });
    }

    /**
     * Passer function that gets the specified Project Controller and passes the request.
     * @param $idController
     * @param $idProject
     * @throws Exception
     * @throws \eprocess360\v3core\Controller\ControllerException
     */
    public function getProjectControllerAPI($idController, $idProject)
    {
        global $pool;
        
        /** @var Controller $child*/
        if($child = Project::getProjectControllerByIdProject($idProject)){

            $child->setName('projects');
            $child->setObjectId($idProject);
            $pool->User->setPermissions(false, false, $idProject);
            $this->setObjectId($idController);
            $this->addController($child);
            $child->ready()->run();
        }
        else
            throw new Exception("Controller not found.");
    }

    /**
     * @param $idController
     * @throws Exception
     * @throws \eprocess360\v3core\Controller\ControllerException
     */
    public function getControllerAPI($idController)
    {
        global $pool;
        /** @var Controller $child*/
        if($child = Project::getProjectControllerByIdController($idController)){
            $child->setName('modules');
            $this->setObjectId($idController);
            $this->addController($child);
            $child->ready()->run();
        }
        else
            throw new Exception("Controller not found.");
    }

    /**
     * API Function to create a new ProjectController.
     */
    public function createProjectControllerAPI()
    {
        $this->verifyPrivilege(Privilege::CREATE);

        $data = Request::get()->getRequestBody();

        $class = isset($data['class'])? $data['class']:null;
        $title = isset($data['title'])? $data['title']:null;
        $description = isset($data['description'])? $data['description']:null;
        $isActive = isset($data['status']['isActive'])? $data['status']['isActive']:null;
        $isAllCreate = isset($data['status']['isAllCreate'])? $data['status']['isAllCreate']:null;
        $idGroups = isset($data['idGroups'])? $data['idGroups']:null;


        $workflowClasses = Project::getWorkflowClasses();
        if(!in_array($class, $workflowClasses))
            throw new Exception("The workflow class ".$class." was not found.");

        Project::createWorkflow($class, $title, $description, $isActive, $isAllCreate, $idGroups);

        $this->getProjectControllersAPI();
    }

    /**
     * Returns a list of creatable ProjectControllers.
     * @throws Exception
     * @throws \eprocess360\v3core\Controller\ControllerException
     */
    public function getProjectControllersAPI()
    {
        Project::getWorkflowClasses();
        //TODO Send Groups.
        //TODO Temporary Controllers page. Impliment a real front end. Shows list of project controllers.
        $view = self::viewAll();
        $view->response($this);
        $data = $view->json(false);

        $this->standardResponse($data, 'workflow');
    }

    /**
     * Returns a list of creatable ProjectControllers.
     * @throws Exception
     * @throws \eprocess360\v3core\Controller\ControllerException
     */
    public function editProjectControllersAPI($idController)
    {
        $this->verifyPrivilege(Privilege::WRITE);

        $data = Request::get()->getRequestBody();

        $title = isset($data['title'])? $data['title']:null;
        $description = isset($data['description'])? $data['description']:null;
        $isActive = isset($data['status']['isActive'])? $data['status']['isActive']:null;
        $isAllCreate = isset($data['status']['isAllCreate'])? $data['status']['isAllCreate']:null;
        $idGroups = isset($data['idGroups'])? $data['idGroups']:null;

        Project::editWorkflow($idController, $title, $description, $isActive, $isAllCreate, $idGroups);

        $this->getProjectControllersAPI();
    }

    /**
     * API Function to Delete a specified Workflow Controller.
     * @param $idController
     */
    public function deleteProjectControllersAPI($idController)
    {
        $this->verifyPrivilege(Privilege::DELETE);

        Project::deleteWorkflow($idController);

        $this->getProjectControllersAPI();
    }

    /**
     * API Function to create a new Project under the specified Project Controller.
     * @param $idController
     */
    public function createProjectAPI($idController)
    {
        Project::checkPermission($idController);

        $project = Project::createProject($idController);
        $result = $project->toArray();

        $response = $this->getResponseHandler();
        $response->setResponse(['data' => $result]);
    }

    /**********************************************   #HELPER#  **********************************************/


    /**
     * Helper function that sets the Response's template, data, response code, and errors.
     * @param array $data
     * @param int $responseCode
     * @param bool|false $error
     */
    private function standardResponse($data = [], $objectType = '', $responseCode = 200, $error = false)
    {
        $this->objectType = $objectType;

        $responseData = [
            'error' => $error,
            'data' => $data
        ];

        $response = $this->getResponseHandler();
        $response->setTemplate('controllers.base.html.twig', 'server');
        $response->setTemplate('module.controllers.handlebars.html', 'client', $this);
        $response->setTemplate(APP_PATH.'/eprocess360/v3controllers/src/SystemController/static/handlebars/global.multiview.handlebars.html', 'client', $this);

        $response->extendResponseMeta('Controller', ['objectType'=>$objectType]);

        $response->setResponse($responseData, $responseCode, false);
        if($error)
            $response->setErrorResponse(new \Exception($error));
    }

    /**
     * @return StandardView
     * @throws Exception
     */
    public static function viewAll()
    {
        
        $select = "*";
        $join = "LEFT JOIN Controllers ON ProjectControllers.idController = Controllers.idController";
        $from = "ProjectControllers";

        $sql = ['select'=>$select,'join'=>$join, 'where'=>'', "from"=> $from];

        $table = Controllers::join(ProjectControllers::keydict());

        $template =  "<a href=\"{{ SysVar.get('siteUrl') }}/controllers/{{ this.idController.get() }}/modules\">{{ this.title.get() }}</a>";
        //->setTemplate($template, false)

        /** @var StandardView $view*/
        $view = StandardView::build('ProjectControllers.All', 'Workflows', $table, $sql);
        $view->add(
            Column::import($table->title)->setSort(true)->setIsLink(true),
            Column::import($table->description)
        );
        return $view;
    }

    public function dashboardInit()
    {
        $this->setDashboardIcon('blank fa fa-cogs');
    }
}