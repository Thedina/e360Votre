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
use eprocess360\v3controllers\Inspection\Model\InspectionLimitations;

use eprocess360\v3modules\Toolbar\Toolbar;

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
        $this->routes->map('GET', '/[i:idInspector]', function ($idInspUser) {
            $this->getInspectorAPI($idInspUser);
        });
        $this->routes->map('POST', '/[i:idInspector]', function ($idInspector) {
            $this->postInspectorAPI($idInspector);
        });
        $this->routes->map('POST', '/getskills/[i:idInspector]', function ($idInspector) {
            $this->getInspectorSkillsAPI($idInspector);
        });
        $this->routes->map('POST', '/skills/[i:idInspUser]', function ($idInspector) {
            $this->postInspectorSkillsAPI($idInspector);
        });
        $this->routes->map('POST', '/getlimitations/[i:idInspector]', function ($idInspector) {
            $this->getInspectorLimitationsAPI($idInspector);
        });
        $this->routes->map('POST', '/limitations/[i:idInspUser]', function ($idInspector) {
            $this->postInspectorLimitationsAPI($idInspector);
        });
        $this->routes->map('DELETE', '/[i:idInspUser]', function ($idInspector) {
            $this->deleteInspectorAPI($idInspector);
        });
        
    }
    
    
    private function standardResponse($data = [], $responseCode = 200, $apiType = "", $error = false)
    {
        if($error == false)
            $error = $this->messages[$responseCode];

        $responseData = [
            'data' => $data
        ];
        
        $response = $this->getResponseHandler();
        $toolbar = $this->buildDashboardToolbar();
        $response->addResponseMeta('dashboardBar', $toolbar);

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
    
    
    public function getInspectorAPI($idInspUser){
        
        $inspector = Inspectors::getInspector($idInspUser);
        $idInspector = $inspector['idInspector'];
        
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
        $data['idUser']  = $idInspUser;
        $data['idInspector'] = $idInspector;
        
        $responseData = [
            'data' => $data
        ];
        
        $response = $this->getResponseHandler();
        $toolbar = $this->buildDashboardToolbar();
        $response->addResponseMeta('dashboardBar', $toolbar);
        $response->setTemplate('Inspector.create.html.twig', 'server');
        $response->setTemplate('module.inspector.handlebars.html', 'client', $this);
        $response->setResponse($responseData);

    }
    
    public function postInspectorAPI($idInspUser){
        
        $inspector = Inspectors::getInspector($idInspUser);
        $idInspector = $inspector['idInspector'];

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
        $data['idInspector'] = $idInspector;
        
        $responseData = [
            'data' => $data
        ];
        
        $response = $this->getResponseHandler();
        $toolbar = $this->buildDashboardToolbar();
        $response->addResponseMeta('dashboardBar', $toolbar);
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
    
    public function getInspectorSkillsAPI($idInspector){
        
        $allSkills = InspectionSkills::allSkills();
        
        if(empty($allSkills))
            throw new Exception("Skills not found. Add skills before assign");
        
        $inspectorSkills = Inspectors::getSkills($idInspector);
        $assignedSkills = array();
        $allSkillsData = array();
        
        foreach($inspectorSkills as $inspSkill){
            $skillId = $inspSkill['idInspSkill'];
            $assignedSkills[$skillId] = $skillId;
        }
        
        foreach($allSkills as $skill){
            $skillData = array();
            $idSkill = $skill['idInspSkill'];
            $skillData['idInspSkill'] = $idSkill;
            $skillData['title'] = $skill['title'];
            $skillData['assigned'] = (!empty($assignedSkills[$idSkill])) ? true : false;
            $allSkillsData[] = $skillData;
        }
        
        $responseData = [
            'data' => $allSkillsData
        ];
        $response = $this->getResponseHandler();
        $response->setResponse($responseData);
    }
    
    public function postInspectorSkillsAPI($idInspector){
        
        
        $data       = Request::get()->getRequestBody();
        $postData   = $data['skills'];
        $data       = Inspectors::editSkills($idInspector, $postData);
        
        
    }
    
    public function getInspectorLimitationsAPI($idInspector){
        
        $allLimitations = InspectionLimitations::allLimitations();
        
        if(empty($allLimitations))
            throw new Exception("Limitations not found. Add limitations before assign");
        
        $inspectorLimitations = Inspectors::getLimitations($idInspector);
        $assignedLimitations = array();
        $allLimitationsData = array();
        
        foreach($inspectorLimitations as $inspLimitation){
            $limitationId = $inspLimitation['idInspLimitation'];
            $assignedLimitations[$limitationId] = $limitationId;
        }
        
        foreach($allLimitations as $limitation){
            $limitationData = array();
            $idLimitation = $limitation['idInspLimitation'];
            $limitationData['idInspLimitation'] = $idLimitation;
            $limitationData['title'] = $limitation['title'];
            $limitationData['assigned'] = (!empty($assignedLimitations[$idLimitation])) ? true : false;
            $allLimitationsData[] = $limitationData;
        }
        
        $responseData = [
            'data' => $allLimitationsData
        ];
        $response = $this->getResponseHandler();
        $response->setResponse($responseData);
    }
    
    public function postInspectorLimitationsAPI($idInspector){
        
        $data       = Request::get()->getRequestBody();
        $postData   = $data['limitations'];
        $data       = Inspectors::editLimitations($idInspector, $postData);
        
        
    }
    
    public function deleteInspectorAPI($idInspector){
        
        $this->verifyPrivilege(Privilege::DELETE);
        
        $data = Inspectors::deleteInspector($idInspector);

    }
    
    private function buildDashboardToolbar() {
        
        $controller = Request::get()->getResponder();
        $toolbar    = Toolbar::buildDashboardBar($controller);
        
        $toolbar->addToolbarMore('Inspection Utils', '/inspection/categories', false);
        $toolbar->addToolbarMore('Manage Inspectors', '/inspector', true);
        
        $toolbarArr = $toolbar->getToolbar();
            
        return $toolbarArr;
    }
    
}
