<?php

namespace eprocess360\v3controllers\Inspector;


use eprocess360\v3core\Controller\Auth;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Controller\Warden;
use eprocess360\v3core\Controller\Warden\Privilege;
use eprocess360\v3core\Request\Request;
use eprocess360\v3controllers\Inspector\Model\Inspectors;


use eprocess360\v3core\Form;
use eprocess360\v3core\Keydict\Entry\Email;
use eprocess360\v3core\Keydict\Entry\PhoneNumber;
use eprocess360\v3core\Keydict\Entry\String;

use eprocess360\v3controllers\Inspection\Model\InspectionSkills;


use Exception;

/**
 * Class Dashboard
 * @package eprocess360\v3controllers\Dashboard
 */
class Inspector extends Controller
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
            $this->getInspectorsAPI();
        });
        $this->routes->map('POST', '', function () {
            $this->createInspectorAPI();
        });
        $this->routes->map('GET', '/[i:idInspector]', function ($idInspector) {
            $this->getInspectorAPI($idInspector);
        });
        $this->routes->map('POST', '/[i:idInspector]', function ($idInspector) {
            $this->postInspectorAPI($idInspector);
        });
        $this->routes->map('POST', '/skills', function () {
            $this->getInspectorSkillsAPI();
        });
//         $this->routes->map('PUT', '/categories/[i:idInspCategory]', function ($idInspCategory) {
//            $this->editInspectionCategoryAPI($idInspCategory);
//        });
//        $this->routes->map('DELETE', '/categories/[i:idInspCategory]', function ($idInspCategory) {
//            $this->deleteInspectionCategoryAPI($idInspCategory);
//        });
//        $this->routes->map('GET', '/types', function () {
//            $this->getInspectionTypesAPI();
//        });
//        $this->routes->map('POST', '/types', function () {
//            $this->createInspectionTypeAPI();
//        });
//        $this->routes->map('PUT', '/types/[i:idInspType]', function ($idInspType) {
//            $this->editInspectionTypesAPI($idInspType);
//        });
//        $this->routes->map('DELETE', '/types/[i:idInspType]', function ($idInspType) {
//            $this->deleteInspectionTypeAPI($idInspType);
//        });
//        $this->routes->map('GET', '/skills', function () {
//            $this->getInspectionSkillAPI();
//        });
//        $this->routes->map('POST', '/skills', function () {
//            $this->createInspectionSkillAPI();
//        });
//        $this->routes->map('PUT', '/skills/[i:idInspSkill]', function ($idInspSkill) {
//            $this->editInspectionSkillsAPI($idInspSkill);
//        });
//        $this->routes->map('GET', '/limitation', function () {
//            $this->getLimitationAPI();
//        });
//        $this->routes->map('POST', '/limitation/', function ($idlimitation) {
//            $this->createLimitationAPI($idlimitation);
//        });
//        $this->routes->map('PUT', '/limitation/[i:idInspLimiattion]', function ($idlimitation) {
//            $this->editLimitationAPI($idlimitation);
//        });
//        $this->routes->map('DELETE', '/limitation/[i:idInspLimiattion]', function ($idlimitation) {
//            $this->deleteLimitation($idlimitation);
//        });
//        $this->routes->map('GET', '/inspectors', function () {
//            $this->getInspectorAPI();
//        });
    }
    
    
    private function standardResponse($data = [], $responseCode = 200, $apiType = "", $error = false)
    {
        if($error == false)
            $error = $this->messages[$responseCode];

        $responseData = [
            'data' => $data
        ];

        $response = $this->getResponseHandler();
        $response->setResponse($responseData, $responseCode, false);
        $response->setTemplate('Inspector.base.html.twig', 'server');
        $response->setTemplate('module.inspector.handlebars.html', 'client', $this);
        
        if($error)
            $response->setErrorResponse(new Exception($error));
    }
    
    
    public function getInspectorsAPI()
    {
        $data = Inspectors::allInspectors();
        $this->standardResponse($data);

    }
    
    public function createInspectorAPI(){
        
        $this->verifyPrivilege(Privilege::CREATE);

        $data = Request::get()->getRequestBody();
        $idUser = $data['idUser'];
        
        $data = Inspectors::create($idUser);
    }
    
    
    public function getInspectorAPI($idInspector){
        
        $inspector = Inspectors::getInspector($idInspector);
        
        if(empty($inspector['idInspector'])){
            throw new Exception("Inspector not found.");
            exit;
        }
        
        $form       = $this->generateInspectorForm();
        $keydictArr = $form->getKeydict()->toArray();
        
        foreach($inspector as $key => $value){
            
            if(isset($keydictArr[$key]) && !empty($value))
                $form->getKeydict()->getField($key)->set($value);    
        }

        $data['form']    = $form;
        $data['keydict'] = $form->getKeydict();
        $data['idUser']  = $idInspector;
        
        $responseData = [
            'data' => $data
        ];
        
        $response = $this->getResponseHandler();
        $response->setTemplate('Inspector.create.html.twig', 'server');
        $response->setTemplate('module.inspector.handlebars.html', 'client', $this);
        $response->setResponse($responseData);

    }
    
    public function postInspectorAPI($idInspUser){
        
        global $pool;

        $form = $this->generateInspectorForm();

        $form->acceptPost();
        if (!$form->getKeydict()->hasException()) {
            try {
   
                $address = $form->getKeydict()->getField('address')->sleep();
                $fax     = $form->getKeydict()->getField('fax')->sleep();

                Inspectors::editInspector($idInspUser, $address, $fax);

            } catch (\Exception $e) {
                $data['errors'][] = $e;
            }
        } else {
            $data['errors'] = $form->getKeydict()->getException();
        }

        $data['form']    = $form;
        $data['keydict'] = $form->getKeydict();
        $data['idUser']  = $idInspUser;
        
        $responseData = [
            'data' => $data
        ];
        
        $response = $this->getResponseHandler();
        $response->setTemplate('Inspector.create.html.twig', 'server');
        $response->setTemplate('module.inspector.handlebars.html', 'client', $this);
        $response->setResponse($responseData);
    }
    
    private function generateInspectorForm(){
        
        $form = Form::build(0, 'inspectorCreation', 'Inspector Creation')->setPublic(true);
        
        $form->accepts(
            String::build('firstName', 'First Name')->setMeta('readonly', "true"),
            String::build('lastName', 'Last Name')->setMeta('readonly', "true"),
            Email::build('email', 'Email Address')->setMeta('readonly', "true"),
            PhoneNumber::build('phone', 'Phone Number')->setMeta('readonly', "true"),
            String::build('address', 'Address'),
            String::build('fax', 'Fax')
            
        )->setLabel('inspectorCreation')->setDescription('Inspector Creation form.');
        
        return $form;
        
    }
    
    public function getInspectorSkillsAPI(){
        
        $allSkills = InspectionSkills::allSkills();
        
        $responseData = [
            'data' => $allSkills
        ];
        $response = $this->getResponseHandler();
        $response->setResponse($responseData);
    }
}
