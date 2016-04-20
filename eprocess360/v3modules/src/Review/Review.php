<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 12/1/15
 * Time: 11:30 AM
 */

namespace eprocess360\v3modules\Review;


use eprocess360\v3core\Controller\Children;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Dashboard;
use eprocess360\v3core\Controller\Dashboard\Button;
use eprocess360\v3core\Controller\Dashboard\Buttons;
use eprocess360\v3core\Controller\DashboardToolbar;
use eprocess360\v3core\Controller\Module;
use eprocess360\v3core\Controller\Persistent;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Controller\Rules;
use eprocess360\v3core\Controller\Trigger\InterfaceTriggers;
use eprocess360\v3core\Controller\Triggers;
use eprocess360\v3core\Controller\Warden;
use eprocess360\v3core\DB;
use eprocess360\v3core\Files\Folder;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Entry;
use eprocess360\v3core\Keydict\Entry\Bit;
use eprocess360\v3core\Keydict\Entry\Bits;
use eprocess360\v3core\Request\Request;
use eprocess360\v3modules\ModuleRule\ModuleRule;
use eprocess360\v3modules\Review\Model\ReviewTypes;
use eprocess360\v3modules\Submittal\Model;
use eprocess360\v3core\Controller\Warden\Privilege;
use eprocess360\v3modules\FolderRoot\FolderRoot;
use eprocess360\v3core\Model\Files;
use eprocess360\v3modules\Review\Model\Reviews;
use eprocess360\v3modules\Task\Model\Tasks;
use eprocess360\v3modules\Task\Task;
use eprocess360\v3modules\Toolbar\Toolbar;
use Exception;

/**
 * Class Review
 * @package eprocess360\v3modules\Review
 */
class Review extends Controller implements InterfaceTriggers
{
    use Router, Module, Persistent, Triggers, Children, Rules, DashboardToolbar, Dashboard;
    private $groups;
    private $reviewers;
    private $reviewTypes;
    /** @var  FolderRoot */
    private $folderRoot;
    private $idObject;
    private $url;
    private $baseObject;
    private $task;
    private $objectIdFolder;
    private $objectTitle;
    private $objectIsComplete;
    private $objectType;
    /** @var  ModuleRule $moduleRule*/
    private $moduleRule;
    protected $staticPath;
    private $messages = [200 => false,
        404 => "404 Not Found - Resource not found",
        403 => "Permissions are not sufficient."];
    public $identified;


    /**
     * Used as a fail-safe in Controllers to make sure that their dependencies and initializations are met; If not, exception is thrown.
     */
    public function dependencyCheck()
    {
        if($this->folderRoot === NULL)
            throw new Exception("Review Module does not have a Folder Root set, please bindFolderRoot in the initialization function.");
        if($this->moduleRule === NULL)
            throw new Exception("Review Module does not have a ModuleRule set, please bindModuleRule in the initialization function.");

        $this->folderRoot->dependencyCheck();
    }


    /*********************************************   #ROUTING#  **********************************************/


    /**
     * Define the available routes for the module here. This function should not perform any other logic outside of
     * specifying available routes.
     */
    public function routes()
    {
        if($this->getParent()->hasObjectId()) {
            $this->routes->map('GET', '', function () {
                $this->getReviewsAPI();
            });
            $this->routes->map('POST', '', function () {
                $this->createReviewAPI();
            });
            $this->routes->map('GET', '/[i:idReview]?', function ($idReview) {
                $this->getReviewAPI($idReview);
            });
            $this->routes->map('PUT', '/[i:idReview]', function ($idReview) {
                $this->editReviewAPI($idReview);
            });
            $this->routes->map('DELETE', '/[i:idReview]', function ($idReview) {
                $this->deleteReviewAPI($idReview);
            });
            $this->routes->map('GET', '/types', function () {
                $this->getStoredReviewTypesAPI();
            });
            $this->routes->map('GET', '/groups', function () {
                $this->getGroupsAPI();
            });
            $this->routes->map('GET|POST|PATCH|PUT|DELETE', '/[i:idReview]/files/[*:trailing]?', function ($idReview) {
                $this->reviewFilesAPI($idReview);
            });
        }
        else{
            $this->routes->map('GET', '', function () {
                $this->buildSettings();
            });
            $this->routes->map('GET', '/types', function () {
                $this->getReviewTypesAPI();
            });
            $this->routes->map('POST', '/types', function () {
                $this->createReviewTypeAPI();
            });
            $this->routes->map('PUT', '/types/[i:idReviewType]', function ($idReviewType) {
                $this->editReviewTypeAPI($idReviewType);
            });
            $this->routes->map('DELETE', '/types/[i:idReviewType]', function ($idReviewType) {
                $this->deleteReviewTypeAPI($idReviewType);
            });

            $this->routes->map('GET|POST|PATCH|PUT|DELETE', '/rules/[*:trailing]?', function () {
                $this->reviewModuleRulesAPI();
            });
        }
    }

    /**
     * API Function to get the Reviews of a given Controller and Object.
     * @Required_Privilege: Read
     */
    public function getReviewsAPI()
    {
        $this->verifyPrivilege(Privilege::READ);

        $data = Reviews::getReviews($this);

        $this->standardResponse($data);
    }

    /**
     * API Function to get a specified Review
     * @param $idReview
     * @Required_Privilege: Read
     */
    public function getReviewAPI($idReview)
    {
        $this->verifyPrivilege(Privilege::READ);

        $data = Reviews::getReview($this, $idReview);

        $this->standardResponse($data);
    }

    /**
     * API Function to create a Review given $idReviewType, description, idGroup, idUser, and dateDue
     * @Required_Privilege: Create
     * @Triggers: onReviewCreate
     */
    public function createReviewAPI()
    {
        $this->verifyPrivilege(Privilege::CREATE);

        $data = Request::get()->getRequestBody();
        $idReviewType = $data['idReviewType'];
        $description = $data['description'];
        $idGroup = $data['idGroup'];
        $idUser = $data['idUser'];
        $dateDue = $data['dateDue'];

        $data =  Reviews::create($this, $idReviewType, $description, $idGroup, $idUser, $dateDue);
        $this->trigger('onReviewCreate');

        $this->standardResponse($data);
    }

    /**
     * API Function to edit a review. specifically: idReviewType, description, idGroup, idUser, dateDue, status->isAccepted, status->isCompleted
     * @param $idReview
     * @Required_Privilege: Write
     * @Triggers: onReviewComplete
     */
    public function editReviewAPI($idReview)
    {
        $this->verifyPrivilege(Privilege::WRITE);

        $data = Request::get()->getRequestBody();
        $idReviewType = isset($data['idReviewType'])? $data['idReviewType']:null;
        $description = isset($data['description'])? $data['description']:null;
        $idGroup = isset($data['idGroup'])? $data['idGroup']:null;
        $idUser = isset($data['idUser'])? $data['idUser']:null;
        $dateDue = isset($data['dateDue'])? $data['dateDue']:null;
        $isAccepted = isset($data['status']['isAccepted'])? $data['status']['isAccepted']:null;
        $isComplete = isset($data['status']['isComplete'])? $data['status']['isComplete']:null;

        $idTask = Reviews::sqlFetch($idReview)->idTask->get();
        $oldIsComplete = Tasks::sqlFetch($idTask)->status->isComplete->get();

        $data = Reviews::editReview($this, $idReview, $idReviewType, $description, $idGroup, $idUser, $dateDue, $isAccepted, $isComplete);

        if ($oldIsComplete != $isComplete && $isComplete==true) {
            $this->trigger('onReviewComplete');
        }

        if ($oldIsComplete != $isComplete && $isComplete==false) {
            $this->trigger('onReviewReopen');
        }

        $this->standardResponse($data);
    }

    /**
     * API Function to delete a specified Review.
     * @param $idReview
     * @Required_Privilege: Delete
     * @Triggers: onReviewDelete
     */
    public function deleteReviewAPI($idReview)
    {
        $this->verifyPrivilege(Privilege::DELETE);

        $data = Reviews::deleteReview($idReview);
        $this->trigger('onReviewDelete');

        $this->standardResponse($data);
    }

    /**
     * TODO KILL THIS FUNCTION
     * Returns the array of Review Types on the Review Controller.
     * @return array|null
     */
    public function getStoredReviewTypesAPI()
    {
        $this->standardResponse($this->getReviewTypes());
    }

    /**
     * Returns the array of Groups on the Review Controller.
     * @return array|null
     */
    public function getGroupsAPI()
    {
        $this->standardResponse($this->groups);
    }

    /**
     * Passer Function that continues to the FolderRoot Module
     * @param $idReview
     * @throws Exception
     * @throws \eprocess360\v3core\Controller\ControllerException
     * @Required_Privilege: GET: Read, POST/PUT/PATCH: Write, DELETE: Delete
     */
    public function reviewFilesAPI($idReview)
    {
        //TODO VERIFY that the File is within this Review, for all file functions
        $requestType = Request::get()->getRequestMethod();
        switch ($requestType) {
            case 'GET':
                $this->verifyPrivilege(Privilege::READ);
                break;
            case 'POST':
            case 'PUT':
            case 'PATCH':
                $this->verifyPrivilege(Privilege::WRITE);
                if(Reviews::isComplete($idReview))
                    throw new Exception("Unable to edit a file off a completed review.");
                break;
            case 'DELETE':
                $this->verifyPrivilege(Privilege::DELETE);
                if(Reviews::isComplete($idReview))
                    throw new Exception("Unable to delete a file off a completed review.");
                break;
        }

        if($this->folderRoot){
            /** @var FolderRoot $child */
            $child= $this->folderRoot;
            $this->setObjectId($idReview);
            $this->addController($child);

            $data = Reviews::sqlFetch($idReview);
            $idFolder = $data->idFolder->get();
            $child->setVerified(true);
            $child->attachFolder(Folder::getByID($idFolder));

            $child->ready()->run();
        }
        else
            throw new Exception("No Folder Root configured for this Module.");
    }

    public function buildSettings()
    {
        $buttons = Buttons::build('Review Admin Panel');

        $ruleButton = Button::build("Rules", "Review Rules", "wrench", $this->getPath(true, false)."/rules", null);
        $typeButton = Button::build("Types", "Review Types", "list", $this->getPath(true, false)."/types", null);
        $buttons->addButton($ruleButton, $typeButton);

        $this->addDashBlocks($buttons);

        $this->buildDashboard();
    }

    /**
     * API Function to get the available ReviewTypes
     * @Required_Privilege: Read
     */
    public function getReviewTypesAPI()
    {
        $this->verifyPrivilege(Privilege::READ);

        $data = ReviewTypes::getReviewTypes($this->getId());

        $this->standardResponse($data, 'reviewType');
    }

    /**
     * API Function to create a ReviewType given idGroup, and title
     * @Required_Privilege: Create
     */
    public function createReviewTypeAPI()
    {
        $this->verifyPrivilege(Privilege::CREATE);

        $data = Request::get()->getRequestBody();

        $title = isset($data['title'])? $data['title']:null;
        $idGroup = isset($data['idGroup'])? $data['idGroup']:null;

        $data =  ReviewTypes::create($this->getId(), $idGroup, $title);

        $this->standardResponse($data, 'reviewType');
    }

    /**
     * API Function to edit a ReviewType. specifically: idGroup, title
     * @param $idReviewType
     * @Required_Privilege: Write
     */
    public function editReviewTypeAPI($idReviewType)
    {
        $this->verifyPrivilege(Privilege::WRITE);

        $data = Request::get()->getRequestBody();

        $title = isset($data['title'])? $data['title']:null;
        $idGroup = isset($data['idGroup'])? $data['idGroup']:null;

        $data = ReviewTypes::editReviewType($idReviewType, $idGroup, $title);

        $this->standardResponse($data, 'reviewType');
    }

    /**
     * API Function to delete a specified ReviewType.
     * @param $idReviewType
     * @Required_Privilege: Delete
     */
    public function deleteReviewTypeAPI($idReviewType)
    {
        $this->verifyPrivilege(Privilege::DELETE);

        $data = ReviewTypes::deleteReviewType($idReviewType);

        $this->standardResponse($data, 'reviewType');
    }

    /**
     * Passer Function that continues to the ModuleRule Module
     * @throws Exception
     */
    public function reviewModuleRulesAPI()
    {
        if($this->moduleRule){
            /** @var ModuleRule $child */
            $child= $this->moduleRule;
            $reviewTypes = $this->getReviewTypes();
            $objectActions = [];
            foreach($reviewTypes as $reviewType)
                $objectActions[] = ["idObjectAction"=> $reviewType['idReviewType'],
                    "objectActionTitle" =>$reviewType['title']];
            $child->bindObjectActions($objectActions);
            $this->addController($child);
            $child->ready()->run();
        }
        else
            throw new Exception("No Reviews configured for this Module.");
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
        global $pool;
        $this->objectType = $objectType;
        if($error == false)
            $error = $this->messages[$responseCode];

        $responseData = [
            'error' => $error,
            'data' => $data
        ];

        //$responseData['data']['reviewTypes'] = $this->getReviewTypes();

        $response = $this->getResponseHandler();

        if($this->getParent()->hasObjectId()) {
            /** @var FolderRoot $folderRoot */
            $folderRoot = $this->folderRoot;
            $response->addResponseMeta('fileCategories', $folderRoot->getFileCategories());

            $response->setTemplate('reviews.base.html.twig', 'server');
            $response->setTemplate('module.reviews.handlebars.html', 'client', $this);
            $response->setTemplate(APP_PATH.'/eprocess360/v3controllers/src/SystemController/static/handlebars/global.files.handlebars.html', 'client', $this);
        }
        else {
            // Manually add groups metadata until we have a better way to handle this
            $groupsMeta = [
                'siteUrl'=>$pool->SysVar->siteUrl(),
                'apiPrefix'=>'/api/v'.API_VERSION,
                'static'=>$pool->SysVar->siteUrl().'/eprocess360/v3controllers/src/Group/static',
                'name'=>'groups',
                'description'=>NULL,
                'api'=>'/groups',
                'path'=>$pool->SysVar->siteUrl().'/groups',
                'apiPath'=>$pool->SysVar->siteUrl().'/api/v'.API_VERSION.'/groups'
            ];

            $response->addResponseMeta('Group', $groupsMeta);
            $response->setTemplate('reviews.settings.html.twig', 'server');
            $response->setTemplate('settings.reviews.handlebars.html', 'client', $this);
        }

        $response->extendResponseMeta('Review', ['objectType'=>$objectType]);
        $response->setResponse($responseData, $responseCode, false);
        if($error)
            $response->setErrorResponse(new \Exception($error));
    }

    /**
     * Sets the ReviewTypes usable by this Review Controller.
     * @return $this
     */
    private function setReviewTypes()
    {
        $baseReviewTypes = ReviewTypes::getReviewTypes($this->getId());

        $reviewTypes= [];
        foreach($baseReviewTypes as $baseReviewType)
            $reviewTypes[$baseReviewType['idReviewType']] = $baseReviewType;

        $this->reviewTypes = $reviewTypes;
        return $this;
    }

    /**
     * @param Toolbar $toolbar
     * @param bool|true $isActive
     * @param bool|true $isAvailable
     */
    public function buildLinks(Toolbar $toolbar, $isActive = true, $isAvailable = true)
    {
        /** @var Controller|Review $this */
        $title = $this->getDescription()?:$this->getStaticClass();
        $toolbar->addToolbarLink($title, $this->getPath(true, false), !$this->objectType, $isAvailable);
        $toolbar->addToolbarLink("Rules", $this->getPath(true, false)."/rules", $this->objectType === "reviewRule", $isAvailable);
        $toolbar->addToolbarLink("Types", $this->getPath(true, false)."/types", $this->objectType === "reviewType", $isAvailable);
    }

    /**
     * @param Toolbar $toolbar
     * @param Controller $child
     * @return bool
     */
    public function buildToolbarChildren(Toolbar $toolbar, Controller $child)
    {
        $toolbar->buildParentLinks($this->getParent());

        $isAvailable = true;
        /** @var Controller|Review $this */
        $title = $this->getDescription()?:$this->getStaticClass();
        $toolbar->addToolbarLink($title, $this->getPath(true, false), false, $isAvailable);
        $toolbar->addToolbarLink("Rules", $this->getPath(true, false)."/rules", $child->getClass() === "ModuleRule", $isAvailable);
        $toolbar->addToolbarLink("Types", $this->getPath(true, false)."/types", false, $isAvailable);

        return true;
    }

    /**
     * @param $dateDue
     * @param $actions
     */
    private function reviewActions($dateDue, $actions)
    {
        foreach($actions as $action){
            //We assume here there are only ADDs, nothing else should get to this point
            $actionType = $action['actionType'];
            $idReviewType = $action['idObjectAction'];
            if($actionType == "Add") {
                $reviewType = $this->getReviewTypes()[$idReviewType];
                $description = $reviewType['title'] . " Review";
                $this->createReview($idReviewType, $description, NULL, NULL, $dateDue);
            }
        }
    }

    /*********************************************   #WORKFLOW#  *********************************************/


    /**
     * Returns the FolderRoot that was binded to this Review
     * @return FolderRoot
     */
    public function getFolderRoot(){
        return $this->folderRoot;
    }

    /**
     * Identify function used to connect a Module that uses Reviews, to this Review Module.
     * @param $baseObject; baseObject is the connected Module Controller, ie Submittal.
     * @param $idObject; idObject is the specified Object that a created Review would be connected to
     * @param $url; url that the review will link to.
     * @param $objectTitle; Name of the object the review is for.
     * @param $objectIdFolder; The Folder for the Object so the review can see the object's files.
     * @return $this;
     */
    public function identify($baseObject, $idObject, $url, $objectTitle, $objectIdFolder, $objectIsComplete)
    {
        $this->baseObject = $baseObject;
        $this->idObject = $idObject;
        $this->url = $url;
        $this->objectTitle = $objectTitle;
        $this->objectIdFolder = $objectIdFolder;
        $this->objectIsComplete = $objectIsComplete;
        $callable = function ($controller) {
            return get_class($controller)==="App\\System" ? $controller : null;
        };
        $this->task = $this->getClosest($callable)->getChild('tasks');

        return $this;
    }

    /**
     * Workflow function to create a new review ALREADY IDENTIFIED Review Controller
     * @param $idReviewType
     * @param $description
     * @param $idGroup
     * @param $idUser
     * @param $dateDue
     * @return array|string
     * @Triggers: onReviewCreate
     */
    public function createReview($idReviewType, $description, $idGroup, $idUser, $dateDue)
    {
        if($idReviewType && !$idGroup) {
            $reviewType = $this->getReviewTypeById($idReviewType);
            $idGroup = (int)$reviewType['idGroup'];
        }
        $review = Reviews::create($this, $idReviewType, $description, $idGroup, $idUser, $dateDue);
        $this->trigger('onReviewCreate');
        return $review;
    }

    /**
     * Returns the IdObject of an identified Review Controller.
     * @return mixed
     */
    public function getIdObject()
    {
        return $this->idObject;
    }

    /**
     * Returns the baseObject, which is the Controller the Review is connected to (like Submittal), of an identified Review Controller.
     * @return Module
     */
    public function getBaseObject()
    {
        return $this->baseObject;
    }

    /**
     * Returns the URL link of an identified Review Controller.
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Returns the Task Controller of an identified Review Controller.
     * @return Task
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * Returns the Object Folder of an identified Review Controller.
     * @return mixed
     */
    public function getObjectFolder()
    {
        return $this->objectIdFolder;
    }

    /**
     * Returns the Object Title of an identified Review Controller.
     * @return mixed
     */
    public function getObjectTitle()
    {
        return $this->objectTitle;
    }

    /**
     * Returns a boolean stating whether the object this Review is attached to is complete.
     * @return mixed
     */
    public function getObjectIsComplete()
    {
        return $this->objectIsComplete;
    }

    /**
     * Returns the array of Reviewers on the Review Controller.
     * @return array|null
     */
    public function getReviewers()
    {
        if(!$this->reviewers)
            return $this->bindGroups()->reviewers;
        return $this->reviewers;
    }

    /**
     * Returns the array of Groups on the Review Controller.
     * @return array|null
     */
    public function getGroups()
    {
        if(!$this->groups)
            return $this->bindGroups()->groups;
        return $this->groups;
    }

    /**
     * Returns the array of Review Types on the Review Controller.
     * @return array|null
     */
    public function getReviewTypes()
    {
        if($this->reviewTypes)
            return $this->reviewTypes;
        else
            return $this->setReviewTypes()->reviewTypes;
    }

    /**
     * Gets a Review Type by its idReviewType
     * @param $idReviewType
     * @return null
     */
    public function getReviewTypeById($idReviewType)
    {
        $result = null;
        $reviewTypes = $this->getReviewTypes();
        foreach($reviewTypes as $reviewType) {
            if ((int)$reviewType['idReviewType'] == $idReviewType){
                $result = $reviewType;
                break;
            }
        }
        return $result;
    }


    /**
     * @param $dateDue
     */
    public function createProjectReview($dateDue)
    {
        $moduleRule = $this->moduleRule;
        $actions = $moduleRule->evaluateRulesByController($this->getId());
        $this->reviewActions($dateDue, $actions);
    }

    /******************************************   #INITIALIZATION#  ******************************************/


    /**
     * Binds the FolderRoot used for this Review Controller.
     * @param FolderRoot $folderRoot
     * @return $this
     */
    public function bindFolderRoot(FolderRoot $folderRoot)
    {
        $this->folderRoot = $folderRoot;
        return $this;
    }

    /**
     * Binds an array of Reviewers usable by this Review Controller.
     * @param array $reviewers
     * @return $this
     */
    public function bindReviewers(array $reviewers)
    {
        $this->reviewers = $reviewers;
        return $this;
    }

    /**
     * Binds an array of Groups usable by this Review Controller.
     * @return $this
     */
    public function bindGroups()
    {
        $sql = "SELECT g.idGroup, g.title, GROUP_CONCAT(gu.idUser) AS users
                FROM Groups g
                LEFT JOIN GroupUsers gu ON gu.idGroup = g.idGroup
                WHERE NOT g.status_0 & 0b10
                GROUP BY g.idGroup
                ORDER BY g.idGroup DESC";
        $groups = DB::sql($sql);

        foreach($groups as &$g) {
            $g['users'] = explode(',', $g['users']);
        }

        unset($g); //cleanup $g 'cause PHP is PHP

        $this->groups = $groups;

        $sql = "SELECT DISTINCT Users.idUser, Users.firstName, Users.lastName FROM GroupUsers LEFT JOIN Users
            ON GroupUsers.idUser = Users.idUser";
        $users = DB::sql($sql);
        $this->reviewers = $users;

        return $this;
    }

    /**
     * @param array $reviewTypes
     * @return $this
     */
    public function bindReviewTypes(...$reviewTypes)
    {
        //TODO Optimie these Inserts
        $idController = $this->getId();
        $reviewTypeArray = [];
        foreach($reviewTypes as $reviewType) {
            $reviewTypeArray[] = ReviewTypes::create($idController, $reviewType['idGroup'], $reviewType['name']);
        }
        return $reviewTypeArray;
    }

    /**
     * Binds corresponding ModuleRule Module to the Review.
     * @param ModuleRule $moduleRule
     * @return $this
     */
    public function bindModuleRule(ModuleRule $moduleRule)
    {
        $this->moduleRule = $moduleRule;
        return $this;
    }


    /*********************************************   #TRIGGERS#  *********************************************/


    /**
     * Trigger when a Review is created
     * @param \Closure $closure
     * @return \eprocess360\v3core\Controller\Trigger\Trigger
     */
    public function onReviewCreate($closure)
    {
        return $this->addTrigger(__FUNCTION__, $closure);
    }

    /**
     * Trigger when a Review is deleted
     * @param \Closure $closure
     * @return \eprocess360\v3core\Controller\Trigger\Trigger
     */
    public function onReviewDelete($closure)
    {
        return $this->addTrigger(__FUNCTION__, $closure);
    }

    /**
     * Trigger when a Review is marked isCompleted from a non completed state.
     * @param \Closure $closure
     * @return \eprocess360\v3core\Controller\Trigger\Trigger
     */
    public function onReviewComplete(\Closure $closure)
    {
        return $this->addTrigger(__FUNCTION__, $closure);
    }

    public function onReviewReopen(\Closure $closure)
    {
        return $this->addTrigger(__FUNCTION__, $closure);
    }


    public function dashboardInit()
    {
        $this->setDashboardIcon('blank fa fa-comments-o');
    }
}