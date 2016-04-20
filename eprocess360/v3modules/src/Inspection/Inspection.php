<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 3/2/16
 * Time: 3:12 PM
 */
namespace eprocess360\v3modules\Inspection;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Module;
use eprocess360\v3core\Controller\Persistent;
use eprocess360\v3core\Controller\ProjectToolbar;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Controller\Rules;
use eprocess360\v3core\Controller\Trigger\InterfaceTriggers;
use eprocess360\v3core\Controller\Triggers;
use eprocess360\v3core\Controller\Warden\Privilege;
use eprocess360\v3core\Request\Request;
use eprocess360\v3modules\Inspection\Model\Inspections;
/**
 * Class Inspection
 * @package eprocess360\v3modules\Inspection
 */
class Inspection extends Controller implements InterfaceTriggers
{
    use Router, Module, Persistent, Triggers, Rules, ProjectToolbar;
    /*********************************************   #ROUTING#  **********************************************/
    /**
     * Define the available routes for the module here. This function should not perform any other logic outside of
     * specifying available routes.
     */
    public function routes()
    {
        $this->routes->map('GET', '', function () {
            $this->getInspectionsAPI();
        });
        $this->routes->map('POST', '', function () {
            $this->createInspectionAPI();
        });
//        $this->routes->map('GET', '/[i:idInspection]', function ($idInspection) {
//            $this->getInspectionAPI($idInspection);
//        });
//        $this->routes->map('PUT', '/[i:idInspection]', function ($idInspection) {
//            $this->editInspectionAPI($idInspection);
//        });
//        $this->routes->map('DELETE', '/[i:idInspection]', function ($idInspection) {
//            $this->deleteInspectionAPI($idInspection);
//        });
    }
    /**
     * API Function that builds and returns Inspections on this Controller.
     * @Required_Privilege: Read
     */
    public function getInspectionsAPI()
    {
        $idController = $this->getId();
        $data = Inspections::getInspections($idController);
        $this->standardResponse($data);
    }
    /**
     * API Function that creates a new Inspection given POST data.
     * @Required_Privilege: Write
     */
    public function createInspectionAPI()
    {
        //Gets the data from POST
        $data = Request::get()->getRequestBody();
        $title = isset($data['title'])? $data['title']:null;
        $description = isset($data['description'])? $data['description']:null;
        $idController = $this->getId();
        $idProject = $this->getParent()->getObjectId();
        $data = Inspections::createInspection($idController, $idProject, $title, $description);
        $this->standardResponse($data);
    }
    /**********************************************   #HELPER#  **********************************************/
    /**
     * Helper function that sets the Response's template, data, response code, and errors.
     * @param array $data
     * @param int $responseCode
     * @param bool|false $error
     */
    private function standardResponse($data = [], $responseCode = 200, $error = false)
    {
        $responseData = [
            'error' => $error,
            'data' => $data
        ];
        $response = $this->getResponseHandler();
        //TODO This is where you will specify your server and client side templates.
        //$response->setTemplate('module.inspections.base.html.twig', 'server');
        //$response->setTemplate('module.inspections.handlebars.html', 'client');
        $response->setResponse($responseData, $responseCode, false);
        if($error)
            $response->setErrorResponse(new \Exception($error));
    }
    /*********************************************   #WORKFLOW#  *********************************************/
    /******************************************   #INITIALIZATION#  ******************************************/
    /*********************************************   #TRIGGERS#  *********************************************/
}