<?php

namespace eprocess360\v3controllers\Inspection;


use eprocess360\v3controllers\Inspection\Model\InspectionSkills;
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
            $this->getInspectionAPI();
        });
        $this->routes->map('GET', '/categories', function () {
            $this->getInspectionCategoryAPI();
        });
        $this->routes->map('POST', '/categories', function () {
            $this->createInspectionCategoryAPI();
        });
         $this->routes->map('PUT', '/categories/[i:idInspCategory]', function ($idInspCategory) {
            $this->editInspectionCategoryAPI($idInspCategory);
        });
        $this->routes->map('DELETE', '/categories/[i:idInspCategory]', function ($idInspCategory) {
            $this->deleteInspectionCategoryAPI($idInspCategory);
        });
        $this->routes->map('GET', '/types', function () {
            $this->getInspectionTypesAPI();
        });
        $this->routes->map('PUT', '/types/[i:idInspType]', function ($idInspType) {
            $this->editInspectionTypesAPI($idInspType);
        });
        $this->routes->map('DELETE', '/types/[i:idInspType]', function ($idInspType) {
            $this->deleteInspectionTypeAPI($idInspType);
        });
        $this->routes->map('GET', '/skills', function () {
            $this->getInspectionSkillAPI();
        });
        $this->routes->map('POST', '/skills', function () {
            $this->createInspectionSkillAPI();
        });
        $this->routes->map('PUT', '/skills/[i:idInspSkill]', function ($idInspSkill) {
            $this->editInspectionSkillsAPI($idInspSkill);
        });
        $this->routes->map('GET', '/limitation', function () {
            $this->getLimitationAPI();
        });
        $this->routes->map('POST', '/limitation/', function ($idlimitation) {
            $this->createLimitationAPI($idlimitation);
        });
        $this->routes->map('PUT', '/limitation/[i:idInspLimiattion]', function ($idlimitation) {
            $this->editLimitationAPI($idlimitation);
        });
        $this->routes->map('DELETE', '/limitation/[i:idInspLimiattion]', function ($idlimitation) {
            $this->deleteLimitation($idlimitation);
        });
        
    }
    
    public function getInspectionAPI()
    {

    }
    
    private function standardResponse($data = [], $responseCode = 200, $apiType = "", $error = false)
    {
        if($error == false)
            $error = $this->messages[$responseCode];

        $responseData = [
            'data' => $data
        ];

        $response = $this->getResponseHandler();
        $this->changeApiPaths($response, $apiType);
        $response->setResponse($responseData, $responseCode, false);
        $this->setRenderingTemplates($response, $apiType);
        
        if($error)
            $response->setErrorResponse(new Exception($error));
    }
    
    
    private function changeApiPaths($response, $apiType){
        
        if(!$apiType){
            return false;
        }
        
        $responseMeta   = $response->getResponseMeta();
        $currentApi     = $responseMeta["Inspection"]["api"];
        $currentApiPath = $responseMeta["Inspection"]["apiPath"];
        
        if($apiType == "categories"){
            $newApi     = $currentApi . '/categories';
            $newApiPath = $currentApiPath . '/categories';
        }
        else if($apiType == "types"){
            $newApi     = $currentApi . '/types';
            $newApiPath = $currentApiPath . '/types';
        }
        else if($apiType == "skills"){
            $newApi     = $currentApi . '/skills';
            $newApiPath = $currentApiPath . '/skills';
        }
        else if($apiType == "limitation"){
            $newApi     = $currentApi . '/limitation';
            $newApiPath = $currentApiPath . '/limitation';
        }
            
        $response->extendResponseMeta('Inspection', array('api' => $newApi));
        $response->extendResponseMeta('Inspection', array('apiPath' => $newApiPath));
        
    }
    
    private function setRenderingTemplates($response, $apiType){
        
        if($apiType == "categories"){
            $response->setTemplate('Inspection.category.html.twig', 'server');
            $response->setTemplate('module.inspection.categories.handlebars.html', 'client', $this);
        }
        else if($apiType == "types"){
            $response->setTemplate('Inspection.types.html.twig', 'server');
            $response->setTemplate('module.inspection.types.handlebars.html', 'client', $this);
        }
        else if($apiType == "skills"){
            $response->setTemplate('Inspection.skills.html.twig', 'server');
            $response->setTemplate('module.inspection.skills.handlebars.html', 'client', $this);
        }
        else if($apiType == "limitation"){
            $response->setTemplate('Inspection.lim.html.twig', 'server');
            $response->setTemplate('module.inspection.limitation.handlebars.html', 'client', $this);
        }
        else{
            $response->setTemplate('Inspection.main.html', 'server');
        }
        
    }
    
    public function getInspectionCategoryAPI()
    {
        $data = InspectionCategories::allCategories();
        $this->standardResponse($data, 200, "categories");

    }
    
    public function createInspectionCategoryAPI(){
        
        $this->verifyPrivilege(Privilege::CREATE);

        $data = Request::get()->getRequestBody();
        $title = $data['title'];
        $description = $data['description'];

        $data = InspectionCategories::create($title, $description);
    }
    
    public function editInspectionCategoryAPI($idInspCategory){
        
        $this->verifyPrivilege(Privilege::WRITE);
        
        $category    = InspectionCategories::sqlFetch($idInspCategory);
        $data        = Request::get()->getRequestBody();
        $title       = $data['title'];
        $description = $data['description'];
        
        if($title !== null)
            $category->title->set($title);
        if($description !== null)
            $category->description->set($description);

        $category->update();
        $data = $category->toArray();        
        
    }
    
    public function deleteInspectionCategoryAPI($idInspCategory){
        
        $this->verifyPrivilege(Privilege::DELETE);

        $data = InspectionCategories::deleteCategory($idInspCategory);

        $this->standardResponse($data, 200, "categories");
        
    }

    public function getInspectionTypesAPI()
    {
       
        $data = InspectionType::allInspectionTypes($this->hasPrivilege(Privilege::ADMIN));
       
        $this->standardResponse($data, 200, "types");
        
    }
    
    /**
     * 
     * @param type $idInspType
     */
    public function editInspectionTypesAPI($idInspType){
  
        $this->verifyPrivilege(Privilege::WRITE);
        
        $category    = InspectionType::sqlFetch($idInspType);
        $data        = Request::get()->getRequestBody();
        
        $title       = $data['title'];
        $description = $data['description'];
        
        if($title !== null)
            $category->title->set($title);
        if($description !== null)
            $category->description->set($description);

        $category->update();
        $data = $category->toArray();        
        
    }

    public function deleteInspectionTypeAPI($idInspType)
    {
        $this->verifyPrivilege(Privilege::DELETE);

        $data = InspectionType::deleteById($idInspType);
        
        $this->standardResponse($data, 200, "types");

    }

    public function getInspectionSkillAPI()
    {

        $data = InspectionSkills::allSkills($this->hasPrivilege(Privilege::ADMIN));

        $this->standardResponse($data, 200, "skills");

    }

    public function createInspectionSkillAPI(){

        $this->verifyPrivilege(Privilege::CREATE);

        $data = Request::get()->getRequestBody();
        $title = $data['title'];
        $description = $data['description'];

        $data = InspectionSkills::create($title, $description);
    }

    public function editInspectionSkillsAPI($idInspSkill){

        $this->verifyPrivilege(Privilege::WRITE);

        $skill   = InspectionSkills::sqlFetch($idInspSkill);
        $data        = Request::get()->getRequestBody();
        $title       = $data['title'];
        $description = $data['description'];

        if($title !== null)
            $skill->title->set($title);
        if($description !== null)
            $skill->description->set($description);

        $skill->update();
        $data = $skill->toArray();

    }

    public function getLimitationAPI()
    {

        $data = Limitation::allLimitation($this->hasPrivilege(Privilege::ADMIN));

        $this->standardResponse($data, 200, "limitation");

    }

    public function createLimitationAPI($idInspLimitation){

        $this->verifyPrivilege(Privilege::WRITE);

        $limitation    = Limitation::sqlFetch($idInspLimitation);
        $data        = Request::get()->getRequestBody();
        $title       = $data['title'];
        $description = $data['description'];

        if($title !== null)
            $limitation->title->set($title);
        if($description !== null)
            $limitation->description->set($description);

        $limitation->update();
        $data = $limitation->toArray();

    }

    public function editLimitationAPI($idInspLimitation){

        $this->verifyPrivilege(Privilege::WRITE);

        $limitation    = Limitation::sqlFetch($idInspLimitation);
        $data        = Request::get()->getRequestBody();
        $title       = $data['title'];
        $description = $data['description'];

        if($title !== null)
            $limitation->title->set($title);
        if($description !== null)
            $limitation->description->set($description);

        $limitation->update();

        $data = $limitation->toArray();

    }

    public function deleteLimitation($idLimitation)
    {
        $this->verifyPrivilege(Privilege::DELETE);

        $data = Limitation::deletelimitation($idLimitation);

        $this->standardResponse($data, 200, "limitation");
    }



}
