<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 2/16/16
 * Time: 10:27 AM
 */

namespace eprocess360\v3controllers\IncrementGenerator;


use eprocess360\v3controllers\IncrementGenerator\Model\IncrementGenerators;
use eprocess360\v3core\Controller\Auth;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Controller\Warden;
use eprocess360\v3core\Controller\Warden\Privilege;
use eprocess360\v3core\Request\Request;
use Exception;

/**
 * Class IncrementGenerator
 * @package eprocess360\v3controllers\IncrementGenerator
 */
class IncrementGenerator extends Controller
{
    use Router, Auth, Warden;


    private $messages = [200 => false,
        404 => "404 Not Found - Resource not found",
        403 => "Permissions are not sufficient."];


    /*********************************************   #ROUTING#  **********************************************/


    /**
     * Define the available routes for the module here. This function should not perform any other logic outside of
     * specifying available routes.
     * @throws \Exception
     */
    public function routes()
    {
        $this->routes->map('GET', '', function () {
            $this->getIncrementGeneratorsAPI();
        });
        $this->routes->map('POST', '', function () {
            $this->createIncrementGeneratorAPI();
        });
        $this->routes->map('GET', '/[i:idIncrementGenerator]', function ($idIncrementGenerator) {
            $this->getIncrementGeneratorAPI($idIncrementGenerator);
        });
        $this->routes->map('PUT', '/[i:idIncrementGenerator]', function ($idIncrementGenerator) {
            $this->editIncrementGeneratorAPI($idIncrementGenerator);
        });
        $this->routes->map('DELETE', '/[i:idIncrementGenerator]', function ($idIncrementGenerator) {
            $this->deleteIncrementGeneratorAPI($idIncrementGenerator);
        });
    }

    /**
     * API Function that gets all IncrementGenerators for this Controller.
     * @Required_Privilege: Read
     */
    public function getIncrementGeneratorsAPI()
    {
        $this->verifyPrivilege(Privilege::READ);

        $data = IncrementGenerators::getIncrementGenerators();

        $this->standardResponse($data);
    }

    /**
     * API Function that Creates a IncrementGenerator for this Controller.
     * @Required_Privilege: Create
     */
    public function createIncrementGeneratorAPI()
    {
        $this->verifyPrivilege(Privilege::CREATE);

        $data = Request::get()->getRequestBody();

        $baseIncrement = isset($data['baseIncrement'])? $data['baseIncrement']:null;
        $currentIncrement = isset($data['currentIncrement'])? $data['currentIncrement']:null;
        $key = isset($data['key'])? $data['key']:null;
        $stringGenerator = isset($data['stringGenerator'])? $data['stringGenerator']:null;

        $data =  IncrementGenerators::create($baseIncrement, $currentIncrement, $key, $stringGenerator);

        $this->standardResponse($data);
    }

    /**
     * API Function that gets a specified IncrementGenerator.
     * @param $idIncrementGenerator
     * @Required_Privilege: Read
     */
    public function getIncrementGeneratorAPI($idIncrementGenerator)
    {
        $this->verifyPrivilege(Privilege::READ);

        $data = IncrementGenerators::getIncrementGenerator($idIncrementGenerator);

        $this->standardResponse($data);
    }

    /**
     * API Function edits the specified IncrementGenerator.
     * @param $idIncrementGenerator
     * @Required_Privilege: Write
     */
    public function editIncrementGeneratorAPI($idIncrementGenerator)
    {
        $this->verifyPrivilege(Privilege::WRITE);

        $data = Request::get()->getRequestBody();

        $baseIncrement = isset($data['baseIncrement'])? $data['baseIncrement']:null;
        $currentIncrement = isset($data['currentIncrement'])? $data['currentIncrement']:null;
        $key = isset($data['key'])? $data['key']:null;
        $stringGenerator = isset($data['stringGenerator'])? $data['stringGenerator']:null;

        $data = IncrementGenerators::editIncrementGenerator($idIncrementGenerator, $baseIncrement, $currentIncrement, $key, $stringGenerator);

        $this->standardResponse($data);
    }

    /**
     * API Function that deletes a specified IncrementGenerator.
     * @param $idIncrementGenerator
     * @Required_Privilege: Delete
     */
    public function deleteIncrementGeneratorAPI($idIncrementGenerator)
    {
        $this->verifyPrivilege(Privilege::DELETE);

        $data = IncrementGenerators::deleteIncrementGenerator($idIncrementGenerator);

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
        //TODO Add a front end for this Controller.
//        $response->setTemplate('groups.base.html.twig', 'server');
//        $response->setTemplate('module.groups.handlebars.html', 'client', $this);
        if($error)
            $response->setErrorResponse(new Exception($error));
    }


    /*********************************************   #WORKFLOW#  *********************************************/

}