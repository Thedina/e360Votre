<?php

namespace eprocess360\v3controllers\Inspection;

//use eprocess360\v3core\Controller\Router;
//use eprocess360\v3core\Controller\Controller;
//
//use eprocess360\v3core\Controller\Dashboard;
//
//use eprocess360\v3core\Form;
//
//use eprocess360\v3core\Keydict\Entry\Email;
//use eprocess360\v3core\Keydict\Entry\Password;
//use eprocess360\v3core\Keydict\Entry\PhoneNumber;
//use eprocess360\v3core\Keydict\Entry\String;

use eprocess360\v3controllers\Inspection\Model\InspectionCategories;

use eprocess360\v3core\Controller\Auth;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Controller\Warden;
use eprocess360\v3core\Controller\Warden\Privilege;
use eprocess360\v3controllers\Inspection\Model\InspectionType;

use Exception;

/**
 * Class Dashboard
 * @package eprocess360\v3controllers\Dashboard
 */
class Inspection extends Controller
{
    use Router, Auth, Warden;
   

    /*********************************************   #ROUTING#  **********************************************/

    /**
     * Define the available routes for the module here. This function should not perform any other logic outside of
     * specifying available routes.
     * @throws \Exception
     */
    public function routes()
    {
        $this->routes->map('GET', '', function () {
            $this->getInspectionAPI();
        });
        
        $this->routes->map('GET', '/categories', function () {
            $this->getInspectionCatAPI();
        });
        
        $this->routes->map('GET', '/types', function () {
            $this->getInspectionTypesAPI();
        });
        $this->routes->map('GET', '/limitation', function () {
            $this->getLimitationAPI();
        });
        
    }

     public function getInspectionTypesAPI()
    {
         
        $data = InspectionType::allInspectionTypes($this->hasPrivilege(Privilege::ADMIN));
        
        $responseData= ['data' => $data];
        
        $response = $this->getResponseHandler();
        
        $response->setResponse($responseData);
        $response->setTemplate('Inspection.types.html.twig', 'server');
        $response->setTemplate('module.inspection.types.handlebars.html', 'client', $this);
    }
    
    public function getInspectionAPI()
    {
        
    }
    
    public function getInspectionCatAPI()
    {
        
        $data = array(
            array(
                'idCategory' => 8,
                'title' => 'Cagtegory 1',
                'status' => 1
            ),
            array(
                'idCategory' => 18,
                'title' => 'Cagtegory 2',
                'status' => 1
            )
        );
       
//        $data = InspectionCategories::allCategories();
//        echo "<pre>";
//        print_r($data);
//        echo "</pre>";
        
        $responseData = [
            'data' => $data
        ];
        
//        $this->hasPrivilege(Privilege::ADMIN);
        
        $response = $this->getResponseHandler();
        
        $response->setResponse($responseData);
        $response->setTemplate('Inspection.category.html.twig', 'server');
        $response->setTemplate('module.inspection.categories.handlebars.html', 'client', $this);
        
        
//        $response->setResponse($responseData);
//        if($error == false)
//            $error = $this->messages[$responseCode];
//
//        $responseData = [
//            'data' => $data
//        ];
//
//        $response = $this->getResponseHandler();
//        $response->setResponse($responseData, $responseCode, false);
//        $response->setTemplate('inspection.category.html', 'server');
//        $response->setTemplate('module.groups.handlebars.html', 'client', $this);
//        if($error)
//            $response->setErrorResponse(new Exception($error));
        
    }
    
//    public function testFunc(){
//        $form = Form::build(0, 'checkList', 'Check List')->setPublic(true);
//// Declare what values the form can accept
//
//        $form->accepts(
//            String::build('cheklistName', 'Name')->setRequired(),
//            String::build('checklistDesc', 'Description')->setRequired()
//        )->setLabel('Checklist')->setDescription('Cheklist form.');
//
//        $data['form'] = $form;
//        $data['keydict'] = $form->getKeydict();
//        
//        $responseData = [
//            'data' => $data
//        ];
//        
//        $response = $this->getResponseHandler();
//        $response->setTemplate('Inspection.main.html.twig');
//        $response->setResponse($responseData);
//    }
    public function getLimitationAPI()
    {

        $data = array(
            array(
                'idCategory' => 'leg dis able',
                'title' => 'Cagtegory 1',
                'Pcat' => 'construction,piling'
            ),
            array(
                'idCategory' => 'hraring disable',
                'title' => 'Cagtegory 2',
                'Pcat' => "piling,slab"
            )
        );



//        $data = InspectionCategories::allCategories();
//        echo "<pre>";
//        print_r($data);
//        echo "</pre>";

        $responseData = [
            'data' => $data
        ];

//        $this->hasPrivilege(Privilege::ADMIN);

        $response = $this->getResponseHandler();

        $response->setResponse($responseData);
        $response->setTemplate('Inspection.limitation.html.twig', 'server');
        $response->setTemplate('module.inspection.limitation.handlebars.html', 'client', $this);




    }
    



}
