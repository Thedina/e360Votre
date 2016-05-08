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
use eprocess360\v3core\Controller\Dashboard;
use eprocess360\v3core\Controller\DashboardToolbar;
use eprocess360\v3modules\Toolbar\Toolbar;

use eprocess360\v3modules\Inspection\Model\InspectionCategories;
use eprocess360\v3modules\Inspection\Model\InspectionCategoryTypes;
use eprocess360\v3modules\Inspection\Model\InspectionCategorySkills;
use eprocess360\v3modules\Inspection\Model\InspectionCategoryLimitations;
use eprocess360\v3modules\Inspection\Model\InspectionType;
use eprocess360\v3modules\Inspection\Model\InspectionSkills;
use eprocess360\v3modules\Inspection\Model\InspectionLimitations;
use eprocess360\v3core\View\StandardView;
use eprocess360\v3core\View\Column;
use Exception;

/**
 * Class Inspection
 * @package eprocess360\v3modules\Inspection
 */
class Inspection extends Controller implements InterfaceTriggers
{
    use Router, Module, Persistent, Triggers, Rules, ProjectToolbar, DashboardToolbar, Dashboard;
    /*********************************************   #ROUTING#  **********************************************/
    
    private $objectType;
    private $messages = [200 => false,
        404 => "404 Not Found - Resource not found",
        403 => "Permissions are not sufficient."];
    
    /**
     * Define the available routes for the module here. This function should not perform any other logic outside of
     * specifying available routes.
     */
    public function routes()
    {
        
        if($this->getParent()->hasObjectId()) {
            
        }
        else{
            $this->routes->map('GET', '', function () {
                $this->getInspectionsAPI();
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
            $this->routes->map('GET', '/categories/skills/[i:idInspCategory]', function ($idInspCategory) {
                $this->getInspectionCategorySkillsAPI($idInspCategory);
            });
            $this->routes->map('PUT', '/categories/skills/[i:idInspCategory]', function ($idInspCategory) {
                $this->postInspectionCategorySkillsAPI($idInspCategory);
            });
            $this->routes->map('GET', '/categories/types/[i:idInspCategory]', function ($idInspCategory) {
                $this->getInspectionCategoryTypeAPI($idInspCategory);
            });
            $this->routes->map('PUT', '/categories/types/[i:idInspCategory]', function ($idInspCategory) {
                $this->postInspectionCategoryTypesAPI($idInspCategory);
            });
            $this->routes->map('GET', '/categories/limitations/[i:idInspCategory]', function ($idInspCategory) {
                $this->getInspectionCategoryLimitationsAPI($idInspCategory);
            });
            $this->routes->map('PUT', '/categories/limitations/[i:idInspCategory]', function ($idInspCategory) {
                $this->postInspectionCategoryLimitationsAPI($idInspCategory);
            });
            $this->routes->map('GET', '/types', function () {
                $this->getInspectionTypesAPI();
            });
            $this->routes->map('POST', '/types', function () {
                $this->createInspectionTypeAPI();
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
            $this->routes->map('DELETE', '/skills/[i:idInspSkill]', function ($idInspSkill) {
                $this->deleteInspectionSkillsAPI($idInspSkill);
            });
            $this->routes->map('GET', '/limitations', function () {
                $this->getLimitationAPI();
            });
            $this->routes->map('POST', '/limitations', function () {
                $this->createLimitationAPI();
            });
            $this->routes->map('PUT', '/limitations/[i:idInspLimiattion]', function ($idlimitation) {
                $this->editLimitationAPI($idlimitation);
            });
            $this->routes->map('DELETE', '/limitations/[i:idInspLimiattion]', function ($idlimitation) {
                $this->deleteLimitationAPI($idlimitation);
            });
            $this->routes->map('GET', '/projectconfig', function () {
                $this->getProjectConfigAPI();
            });
        }
            
    }
    /**
     * API Function that builds and returns Inspections on this Controller.
     * @Required_Privilege: Read
     */
    public function getInspectionsAPI()
    {
        $this->objectType = "inpsections";
        $response = $this->getResponseHandler();
        $response->setTemplate('Inspection.base.html.twig', 'server');
        
    }
    
    /**
     * API Function to get all categories
     */
    public function getInspectionCategoryAPI()
    {   
        
        $this->verifyPrivilege(Privilege::READ);
        $result = InspectionCategories::allCategories(true);
        
        $table = $result['keydict'];
        
        $view = StandardView::build('Categories.All', 'Categories', $result['keydict'], $result);
        
        $view->add(
            Column::import($table->idInspCategory)->setEnabled(false)->bucketBy(),
            Column::import($table->title, "Name")->filterBySearch()->bucketBy()->setSort(true),
            Column::import($table->description, "Description"),
            Column::build("inspection-type", "Inspection type")->setTemplate("mvCustomColumnAssignType"),
                Column::build("skill", "Skills")->setTemplate("mvCustomColumnAssignSkill"),
                Column::build("limitation", "Limitations")->setTemplate("mvCustomColumnAssignLimitation"),
            Column::build("options", "")->setTemplate("mvCustomColumnEdit")
        );

        $view->response($this);
        $data = $view->json(false);
        $this->standardResponse($data, 200, "categories");

    }
    
    /**
     * API Function to create inspection category
     */
    public function createInspectionCategoryAPI()
    {
        $this->verifyPrivilege(Privilege::CREATE);
        $data           = Request::get()->getRequestBody();
        $title          = $data['title'];
        $description    = $data['description'];
        $responseData   = InspectionCategories::create($title, $description);
        
        $this->standardResponse($responseData);
    }
    
    /**
     * API Function to edit inspection category
     * @param $idInspCategory
     */
    public function editInspectionCategoryAPI($idInspCategory)
    {
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
        $responseData = $category->toArray();        
        
        $this->standardResponse($responseData);
        
    }
    
    /**
     * API Function to delete inspection category
     * @param $idInspCategory
     */
    public function deleteInspectionCategoryAPI($idInspCategory)
    {
        $this->verifyPrivilege(Privilege::DELETE);
        $data = InspectionCategories::deleteCategory($idInspCategory);
        $this->standardResponse($data, 200, "categories");
        
    }
    
    
    /**
     * API Function to get skills assigned to category
     * @param $idInspCategory
     * @throws Exception
     */
    public function getInspectionCategorySkillsAPI($idInspCategory)
    {   
        $allSkills = InspectionSkills::allSkills();
        
        if(empty($allSkills))
            throw new Exception("Skills not found. Add skills before assign");
        
        $categorySkills = InspectionCategorySkills::getSkills($idInspCategory);
        $assignedSkills = array();
        $allSkillsData  = array();
        
        foreach($categorySkills as $categorySkill){
            $skillId = $categorySkill['idInspSkill'];
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
    
    /**
     * API Function to assign / remove skills to / from category
     * @param $idInspCategory
     */
    public function postInspectionCategorySkillsAPI($idInspCategory)
    {      
        $data     = Request::get()->getRequestBody();
        $postData = $data['skills'];
        InspectionCategorySkills::editSkills($idInspCategory, $postData);
    }
    
    /**
     * API Function to get types assigned to category
     * @param $idInspCategory
     * @throws Exception
     */
    public function getInspectionCategoryTypeAPI($idInspCategory)
    {   
        
        $allTypes = InspectionType::allInspectionTypes();

        if(empty($allTypes))
            throw new Exception("Types not found. Add Types before assign");
        
        $categoryTypes = InspectionCategoryTypes::getTypes($idInspCategory);
        $assignedTypes = array();
        $allTypessData = array();
        

        foreach($categoryTypes as $categoryType){
            $typeId = $categoryType['idInspType'];
            $assignedTypes[$typeId] = $typeId;
        }
        
        foreach($allTypes as $type){
            $typeData = array();
            $idType = $type['idInspType'];
            $typeData['idInspType'] = $idType;
            $typeData['title'] = $type['title'];
            $typeData['assigned'] = (!empty($assignedTypes[$idType])) ? true : false;
            $allTypessData[] = $typeData;
        }
        
        $responseData = [
            'data' => $allTypessData
        ];
        $response = $this->getResponseHandler();
        $response->setResponse($responseData);
    }
    
    /**
     * API Function to assign / remove types to / from category
     * @param type $idInspCategory
     */
    public function postInspectionCategoryTypesAPI($idInspCategory)
    {
        $data         = Request::get()->getRequestBody();
        $postData     = $data['types'];
        $responseData = InspectionCategoryTypes::editTypes($idInspCategory, $postData);
        $this->standardResponse($responseData);
    }
    
    /**
     * API Function to get limitations assigned to category
     * @param $idInspCategory
     * @throws Exception
     */
    public function getInspectionCategoryLimitationsAPI($idInspCategory)
    {
        $allLimitations = InspectionLimitations::allLimitations();
        
        if(empty($allLimitations))
            throw new Exception("Limitations not found. Add limitations before assign");
        
        $categoryLimitations = InspectionCategoryLimitations::getLimitations($idInspCategory);
        
        $assignedLimitations = array();
        $allLimitationsData = array();
        
        foreach($categoryLimitations as $categoryLimitation){
            $limitationId = $categoryLimitation['idInspLimitation'];
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
    
    /**
     * API Function to assign / remove limitations to / from category
     * @param $idInspCategory
     */
    public function postInspectionCategoryLimitationsAPI($idInspCategory)
    {
        $data       = Request::get()->getRequestBody();
        $postData   = $data['limitations'];
        InspectionCategoryLimitations::editLimitations($idInspCategory, $postData);
    }
    
    /**
     * API Function to get all inspection types
     */
    public function getInspectionTypesAPI()
    {  
        
        $this->verifyPrivilege(Privilege::READ);
        $result = InspectionType::allInspectionTypes(true);
        
        $table = $result['keydict'];
        
        $view = StandardView::build('Types.All', 'Types', $result['keydict'], $result);
        
        $view->add(
            Column::import($table->idInspType)->setEnabled(false)->bucketBy(),
            Column::import($table->title, "Name")->filterBySearch()->bucketBy()->setSort(true),
            Column::import($table->description, "Description"),
            Column::build("options", "")->setTemplate("mvCustomColumnEdit")
        );
        $view->response($this);
        $data = $view->json(false);
        $this->standardResponse($data, 200, "types");
        
    }
    
    /**
     * API Function to create inpsection type
     */
    public function createInspectionTypeAPI()
    {   
        $this->verifyPrivilege(Privilege::CREATE);
        $data         = Request::get()->getRequestBody();
        $title        = $data['title'];
        $description  = $data['description'];
        $responseData = InspectionType::create($title, $description);
        $this->standardResponse($responseData);
    }
    
    /**
     * API Function to edit inpsection type
     * @param type $idInspType
     */
    public function editInspectionTypesAPI($idInspType)
    {
        $this->verifyPrivilege(Privilege::WRITE);
        $types       = InspectionType::sqlFetch($idInspType);
        $data        = Request::get()->getRequestBody();
        $title       = $data['title'];
        $description = $data['description'];
        
        if($title !== null)
            $types->title->set($title);
        if($description !== null)
            $types->description->set($description);

        $types->update();
        $responseData = $types->toArray();

        $this->standardResponse($responseData);       
        
    }

    /**
     * API Function to delete inpsection type
     * @param $idInspType
     */
    public function deleteInspectionTypeAPI($idInspType)
    {
        $this->verifyPrivilege(Privilege::DELETE);
        $data = InspectionType::deleteTypes($idInspType);
        $this->standardResponse($data, 200, "types");
        
    }
    
    /**
     * API Function to get all inspection skills
     */
    public function getInspectionSkillAPI()
    {
        
        $this->verifyPrivilege(Privilege::READ);
        
        $result = InspectionSkills::allSkills(true);
        $table = $result['keydict'];
        
        $view = StandardView::build('Skills.All', 'Skills', $result['keydict'], $result);
        
        $view->add(
            Column::import($table->idInspSkill)->setEnabled(false)->bucketBy(),
            Column::import($table->title, "Name")->filterBySearch()->bucketBy()->setSort(true),
            Column::import($table->description, "Description"),
            Column::build("options", "")->setTemplate("mvCustomColumnEdit")
        );
        $view->response($this);
        $data = $view->json(false);
        $this->standardResponse($data, 200, "skills");

    }

    /**
     * API Function to create skill
     */
    public function createInspectionSkillAPI()
    {
        $this->verifyPrivilege(Privilege::CREATE);
        $data         = Request::get()->getRequestBody();
        $title        = $data['title'];
        $description  = $data['description'];
        $responseData = InspectionSkills::create($title, $description);
        
        $this->standardResponse($responseData);
    }

    /**
     * API Function to edit skill
     * @param $idInspSkill
     */
    public function editInspectionSkillsAPI($idInspSkill)
    {
        $this->verifyPrivilege(Privilege::WRITE);
        $skill       = InspectionSkills::sqlFetch($idInspSkill);
        $data        = Request::get()->getRequestBody();
        $title       = $data['title'];
        $description = $data['description'];

        if($title !== null)
            $skill->title->set($title);
        if($description !== null)
            $skill->description->set($description);

        $skill->update();
        $responseData = $skill->toArray();
        $this->standardResponse($responseData);

    }
    
    /**
     * API Function to delete skill
     * @param type $idInspSkill
     */
    public function deleteInspectionSkillsAPI($idInspSkill)
    {
        $this->verifyPrivilege(Privilege::DELETE);
        $data = InspectionSkills::deleteSkill($idInspSkill);
        $this->standardResponse($data, 200, "skills");
    }
    
    /**
     * API Function to get all limitation
     */
    public function getLimitationAPI()
    {
        
        $this->verifyPrivilege(Privilege::READ);
        $result = InspectionLimitations::allLimitations(true);
        
        $table = $result['keydict'];
        
        $view = StandardView::build('Skills.All', 'Skills', $result['keydict'], $result);
        
        $view->add(
            Column::import($table->idInspLimitation)->setEnabled(false)->bucketBy(),
            Column::import($table->title, "Name")->filterBySearch()->bucketBy()->setSort(true),
            Column::import($table->description, "Description"),
            Column::build("options", "")->setTemplate("mvCustomColumnEdit")
        );
        $view->response($this);
        $data = $view->json(false);
        $this->standardResponse($data, 200, "limitations");

    }

    /**
     * API Function to create limitation
     */
    public function createLimitationAPI()
    {
        $this->verifyPrivilege(Privilege::CREATE);
        $data         = Request::get()->getRequestBody();
        $title        = $data['title'];
        $description  = $data['description'];
        $responseData = InspectionLimitations::create($title, $description);
        
        $this->standardResponse($responseData);

    }

    /**
     * API Function to edit limitation
     * @param $idInspLimitation
     */
    public function editLimitationAPI($idInspLimitation)
    {
        $this->verifyPrivilege(Privilege::WRITE);
        $limitation  = InspectionLimitations::sqlFetch($idInspLimitation);
        $data        = Request::get()->getRequestBody();
        $title       = $data['title'];
        $description = $data['description'];

        if($title !== null)
            $limitation->title->set($title);
        if($description !== null)
            $limitation->description->set($description);

        $limitation->update();

        $responseData = $limitation->toArray();
        $this->standardResponse($responseData);
    }

    /**
     * API Function to delete limitation
     * @param $idLimitation
     */
    public function deleteLimitationAPI($idLimitation)
    {
        $this->verifyPrivilege(Privilege::DELETE);
        $data = InspectionLimitations::deletelimitation($idLimitation);
        $this->standardResponse($data, 200, "limitations");
    }
    
    /**
     * API Function to gell all project inspection  config
     */
    public function getProjectConfigAPI()
    {

    }
    
    /**********************************************   #HELPER#  **********************************************/
    
    private function standardResponse($data = [], $responseCode = 200, $objectType = "", $error = false)
    {
        if($error == false)
            $error = $this->messages[$responseCode];
        
        
        $this->objectType = $objectType;

        $responseData = [
            'data' => $data
        ];

        $response = $this->getResponseHandler();        
        $this->changeApiPaths($response, $objectType);
        $response->setResponse($responseData, $responseCode, false);
        $this->setRenderingTemplates($response, $objectType);
        $response->extendResponseMeta('Inspection', ['objectType'=>$objectType]);
        
        if($error)
            $response->setErrorResponse(new Exception($error));
    }
    
    /**
     * Change default api path according to the active section (ie. categories,types)
     * @param $response
     * @param $apiType
     */
    private function changeApiPaths($response, $apiType)
    {
        if(!$apiType){
            return false;
        }

        $responseMeta   = $response->getResponseMeta();
        $currentApi     = $responseMeta["Inspection"]["api"];
        $currentApiPath = $responseMeta["Inspection"]["apiPath"];
        $newApi         = "";
        $newApiPath     = "";
        
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
        else if($apiType == "limitations"){
            $newApi     = $currentApi . '/limitations';
            $newApiPath = $currentApiPath . '/limitations';
        }

        if(!empty($newApi) && !empty($newApiPath)){
            $response->extendResponseMeta('Inspection', array('api' => $newApi));
            $response->extendResponseMeta('Inspection', array('apiPath' => $newApiPath));
        }
    }
    
    /**
     * Set rendering template for the active section
     * @param $response
     * @param $apiType
     */
    private function setRenderingTemplates($response, $objectType)
    {
        
        if($this->getParent()->hasObjectId()) {
            
        }
        else{
            
            $response->setTemplate('inspections.settings.html.twig', 'server');
            $response->setTemplate(APP_PATH.'/eprocess360/v3controllers/src/SystemController/static/handlebars/global.multiview.handlebars.html', 'client', $this);
            
            if($objectType == "categories"){
                $response->setTemplate('module.inspection.categories.handlebars.html', 'client', $this);
            }
            else if($objectType == "types"){
                $response->setTemplate('module.inspection.types.handlebars.html', 'client', $this);
            }
            else if($objectType == "skills"){
                $response->setTemplate('module.inspection.skills.handlebars.html', 'client', $this);
            }
            else if($objectType == "limitations"){
                $response->setTemplate('module.inspection.limitation.handlebars.html', 'client', $this);
            }   
        }
        
    }
    
    /**
     * @param Toolbar $toolbar
     * @param bool|true $isActive
     * @param bool|true $isAvailable
     */
    public function buildLinks(Toolbar $toolbar, $isActive = true, $isAvailable = true)
    {
        /** @var Controller|Fee $this */
        $title = $this->getDescription()?:$this->getStaticClass();
        $toolbar->addToolbarLink($title, $this->getPath(true, false), $this->objectType === "inpsections", $isAvailable);
        $toolbar->addToolbarLink("Categories", $this->getPath(true, false)."/categories", $this->objectType === "categories", $isAvailable);
        $toolbar->addToolbarLink("Inspection Types", $this->getPath(true, false)."/types", $this->objectType === "types", $isAvailable);
        $toolbar->addToolbarLink("Skills", $this->getPath(true, false)."/skills", $this->objectType === "skills", $isAvailable);
        $toolbar->addToolbarLink("Limitations", $this->getPath(true, false)."/limitations", $this->objectType === "limitations", $isAvailable);

    }
    
    /*********************************************   #WORKFLOW#  *********************************************/
    /******************************************   #INITIALIZATION#  ******************************************/
    /*********************************************   #TRIGGERS#  *********************************************/
}
