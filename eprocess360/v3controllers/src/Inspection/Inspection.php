<?php

namespace eprocess360\v3controllers\Inspection;


use eprocess360\v3core\Controller\Auth;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Controller\Warden;
use eprocess360\v3core\Controller\Warden\Privilege;
use eprocess360\v3core\Request\Request;

use eprocess360\v3controllers\Inspection\Model\InspectionType;
use eprocess360\v3controllers\Inspection\Model\Limitation;
use eprocess360\v3controllers\Inspection\Model\InspectionCategories;

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
            $this->getInspectionCategoryAPI();
        });
        $this->routes->map('GET', '/skills', function () {
            $this->getInspectionSkillAPI();
        });
        $this->routes->map('POST', '/categories', function () {
            $this->createInspectionCategoryAPI();
        });
        $this->routes->map('PUT', '/categories/[i:idInspCategory]', function ($idInspCategory) {
            $this->editInspectionCategoryAPI($idInspCategory);
        });
        $this->routes->map('GET', '/types', function () {
            $this->getInspectionTypesAPI();
        });
        $this->routes->map('PUT', '/types/[i:idInspType]', function ($idInspType) {
            $this->editInspectionTypesAPI($idInspType);
        });
        
        
        
        $this->routes->map('GET', '/limitation', function () {
            $this->getLimitationAPI();
        });
        $this->routes->map('GET', '/inspection', function () {
            $this->editGroupUserAPI();
        });
        
    }

     public function getInspectionTypesAPI()
    {

         
        $data = InspectionType::allInspectionTypes($this->hasPrivilege(Privilege::ADMIN));
       
        $responseData = [
            'data' => $data
        ];
        
        $response       = $this->getResponseHandler();
        $responseMeta   = $response->getResponseMeta();
        $currentApi     = $responseMeta["Inspection"]["api"];
        $newApi         = $currentApi . '/types';
        
        $currentApiPath = $responseMeta["Inspection"]["apiPath"];
        $newApiPath     = $currentApiPath . '/types';

        $response->extendResponseMeta('Inspection', array('api' => $newApi));
        $response->extendResponseMeta('Inspection', array('apiPath' => $newApiPath));

        $response->setResponse($responseData);
    
        
//        $responseData= ['data' => $data];
//        
//        $response = $this->getResponseHandler();
        
        $response->setResponse($responseData);
        $response->setTemplate('Inspection.types.html.twig', 'server');
        $response->setTemplate('module.inspection.types.handlebars.html', 'client', $this);
    }
    
    public function getInspectionAPI()
    {

    }
    
    public function getInspectionCategoryAPI()
    {
        
        $data = InspectionCategories::allCategories();
        
        $responseData = [
            'data' => $data
        ];
        
        $response       = $this->getResponseHandler();
        $responseMeta   = $response->getResponseMeta();
        $currentApi     = $responseMeta["Inspection"]["api"];
        $newApi         = $currentApi . '/categories';
        
        $currentApiPath = $responseMeta["Inspection"]["apiPath"];
        $newApiPath     = $currentApiPath . '/categories';

        $response->extendResponseMeta('Inspection', array('api' => $newApi));
        $response->extendResponseMeta('Inspection', array('apiPath' => $newApiPath));

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

    public function getInspectionSkillAPI()
    {

        $data = array(
            array(
                'idSkill' => 9,
                'title' => 'skill 1',
                'status' => 1
            ),
            array(
                'idSkill' => 19,
                'title' => 'skill 2',
                'status' => 1
            ),
        );

//        $data = InspectionCategories::allCategories();
//        echo "<pre>";
//        print_r($data);
//        echo "</pre>";

        $responseData = [
            'data' => $data,$data2
        ];

//        $this->hasPrivilege(Privilege::ADMIN);

        $response = $this->getResponseHandler();

        $response->setResponse($responseData);
        $response->setTemplate('Inspection.skills.html.twig', 'server');
        $response->setTemplate('module.inspection.skills.handlebars.html', 'client', $this);


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
    
    public function editInspectionTypesAPI($idInspType){
  
        $this->verifyPrivilege(Privilege::WRITE);
        
        $category    = InspectionType::sqlFetch($idInspType);
        $data        = Request::get()->getRequestBody();
        
       // print_r("ID :"+$idInspType);
        
        $title       = $data['title'];
        $description = $data['description'];
        
        if($title !== null)
            $category->title->set($title);
        if($description !== null)
            $category->description->set($description);

        $category->update();
        $data = $category->toArray();        
        
    }


    
    public function editInspectionCategoryAPI($idInspCategory){
       
        $this->verifyPrivilege(Privilege::WRITE);
        
        $category    = InspectionCategories::sqlFetch($idInspCategory);
        $data        = Request::get()->getRequestBody();
        $title       = $data['title'];
        $description = $data['description'];
//        print_r($data);
        if($title !== null)
            $category->title->set($title);
        if($description !== null)
            $category->description->set($description);

        $category->update();
        $data = $category->toArray();        
        
    }
    
    public function createInspectionCategoryAPI(){
        
        $this->verifyPrivilege(Privilege::CREATE);

        $data = Request::get()->getRequestBody();
        $title = $data['title'];
        $description = $data['description'];

        $data = InspectionCategories::create($title, $description);
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

      





//        $data = InspectionCategories::allCategories();
//        echo "<pre>";
//        print_r($data);
//        echo "</pre>";
        $data = Limitation::allLimitation($this->hasPrivilege(Privilege::ADMIN));
        $responseData = [
            'data' => $data
        ];

//        $this->hasPrivilege(Privilege::ADMIN);

        $response = $this->getResponseHandler();

        $response->setResponse($responseData);
        $response->setTemplate('Inspection.lim.html.twig', 'server');
        $response->setTemplate('module.inspection.limitation.handlebars.html', 'client', $this);




    }




}
