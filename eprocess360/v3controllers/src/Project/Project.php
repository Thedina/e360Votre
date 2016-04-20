<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 12/9/15
 * Time: 10:30 AM
 */

namespace eprocess360\v3controllers\Project;


use Dompdf\Exception;
use eprocess360\v3core\Controller\Auth;
use eprocess360\v3core\Controller\Children;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Project as ProjectTrait;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Controller\Warden;
use eprocess360\v3core\Controller\Warden\Privilege;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model\Projects;
use eprocess360\v3core\View\Column;
use eprocess360\v3core\View\StandardView;

/**
 * Class ProjectView
 * @package eprocess360\v3controllers\Project
 */
class Project extends Controller
{
    use Router, Auth, Children, Warden;

    private $objectType;
    const READ_ALL = true;


    /*********************************************   #ROUTING#  **********************************************/


    /**
     * Define the available routes for the module here. This function should not perform any other logic outside of
     * specifying available routes.
     */
    public function routes()
    {
        $this->routes->map('GET', '', function () {
            $this->getProjectsAPI();
        });
        $this->routes->map('GET', '/[i:idProject]/[*:trailing]?', function ($idProject, $trailing = "") {
            $this->getProjectAPI($idProject,$trailing);
        });
        $this->routes->map('GET', '/controller/[i:idController]', function ($idController) {
            $this->getControllerProjectsAPI($idController);
        });
    }

    /**
     * API Function to get a view of all Projects available to this user (Projects in which this user has a Role in)
     */
    public function getProjectsAPI()
    {
        //TODO THIS IS FOR CALBO, REMOVE IT
        $view = $this->viewAllByController(2058);
        //$view = $this->viewAll();
        $view->response($this);
        $data = $view->json(false);

        $this->standardResponse($data, 'project');
    }

    /**
     * Passer function that redirects the response to one including the controller and ID of this project.
     * @param $idProject
     * @param $trailing
     * @throws Exception
     * @throws \Exception
     */
    public function getProjectAPI($idProject, $trailing)
    {
        global $pool;

        try{
            $idController = Projects::sqlFetch($idProject)->idController->get();

            header('Location: ' . $pool->SysVar->get('siteUrl') . '/controllers/'.$idController."/projects/".$idProject."/".$trailing);
            die();
        }
        catch(Exception $e) {
            throw new Exception("Project not found.");
        }

    }

    /**
     * API Function that returns a list of Projects this user is associated with on a specified Project Controller. The view is defined by the Project Controller in question.
     * @param $idController
     */
    public function getControllerProjectsAPI($idController)
    {
        $view = $this->viewAllByController($idController);

        $view->response($this);
        $data = $view->json(false);

        $this->standardResponse($data, 'project');
    }

    /**********************************************   #HELPER#  **********************************************/


    /**
     * Helper function that sets the Response's template, data, response code, and errors.
     * @param array $data
     * @param string $objectType
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
        $response->setTemplate('projects.base.html.twig', 'server');
        $response->setTemplate('module.projects.handlebars.html', 'client', $this);
        $response->setTemplate(APP_PATH.'/eprocess360/v3controllers/src/SystemController/static/handlebars/global.multiview.handlebars.html', 'client', $this);

        $response->extendResponseMeta('Project', ['objectType'=>$objectType]);

        $response->setResponse($responseData, $responseCode, false);
        if($error)
            $response->setErrorResponse(new \Exception($error));
    }


    /**
     * Helper function that makes the view for all Projects the User is currently associated with.
     * @return StandardView
     */
    public function viewAll()
    {
        /** @var Table $keydict Defines the data source, which could be a joined table */
        $result = \eprocess360\v3core\Projects::getMyProjects($this->hasPrivilege(Privilege::ADMIN));
        $table = $result['keydict'];

        /** @var StandardView $test Assign the namespace and keydict to the view */
        $view = StandardView::build('Projects.All', 'Projects', $table, $result);
        $view->add(
            Column::import($table->title)->filterBySearch()->setSort(true)->setIsLink(true),
            Column::import($table->description)->setSort(false),
            Column::import($table->state)->bucketBy("DESC")
        );

        return $view;
    }

    /**
     * Helper function that makes the view for all Projects the User is currently associated with under this Project Controller.
     * @return StandardView
     */
    public function viewAllByController($idController)
    {
        /** @var Table $keydict Defines the data source, which could be a joined table */
        $table = \eprocess360\v3core\Projects::getMyProjectsByControllerId($idController, $this->hasPrivilege(Privilege::ADMIN));

        /** @var Controller|ProjectTrait $controller */
        $controller = ProjectTrait::getProjectControllerByIdController($idController);

        return $controller->view($table);
    }
}