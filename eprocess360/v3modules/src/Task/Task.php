<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 12/1/15
 * Time: 11:30 AM
 */

namespace eprocess360\v3modules\Task;

use eprocess360\v3controllers\Group\Model\Groups;
use eprocess360\v3core\Controller\Auth;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Dashboard;
use eprocess360\v3core\Controller\Persistent;
use eprocess360\v3core\Controller\Roles;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Controller\Trigger\InterfaceTriggers;
use eprocess360\v3core\Controller\Triggers;
use eprocess360\v3core\Controller\Warden;
use eprocess360\v3core\Keydict\Entry\Datetime;
use eprocess360\v3core\Request\Request;
use eprocess360\v3modules\Submittal\Model;
use eprocess360\v3core\Controller\Warden\Privilege;
use eprocess360\v3modules\Task\Model\Tasks;
use Exception;

/**
 * Class Task
 * @package eprocess360\v3modules\Task
 */
class Task extends Controller implements InterfaceTriggers
{
    use Router, Persistent, Triggers, Roles, Warden, Auth, Dashboard;
    private $idObject;
    private $url;
    private $baseObject;
    protected $staticPath;
    private $messages = [200 => false,
        404 => "404 Not Found - Resource not found",
        403 => "Permissions are not sufficient."];
    const READ_ALL = true;


    /*********************************************   #ROUTING#  **********************************************/

    /**
     * Define the available routes for the module here. This function should not perform any other logic outside of
     * specifying available routes.
     * @throws Exception
     */
    public function routes()
    {
        $this->routes->map('GET', '', function () {
            $this->getTasksAPI();
        });
        $this->routes->map('GET', '/[i:idTask]', function ($idTask) {
            $this->getTaskAPI($idTask);
        });
        $this->routes->map('PUT', '/[i:idTask]', function ($idTask) {
            $this->editTaskAPI($idTask);
        });
        $this->routes->map('DELETE', '/[i:idTask]', function ($idTask) {
            $this->deleteTaskAPI($idTask);
        });
        $this->routes->map('GET', '/count', function () {
            $this->getCountAPI();
        });
        $this->routes->map('GET', '/groups/[i:idGroup]', function ($idGroup) {
            $this->getGroupTasksAPI($idGroup);
        });
    }

    /**
     * API Function to get all task that belongs to a User with specified endDate and startDate. The default is a week range.
     */
    public function getTasksAPI()
    {
        $data = $_GET;
        $endDate = isset($data['endDate'])? $data['endDate']:date('Y-m-d H:i:s', strtotime("+6 days",strtotime('last monday', strtotime('tomorrow'))));
        $startDate = isset($data['startDate'])? $data['startDate']:date('Y-m-d H:i:s', strtotime('last monday', strtotime('tomorrow')));

        if(isset($data['showPastDue'])) {
            $showPastDue = (!$data['showPastDue'] || $data['showPastDue'] === 'false') ? false : true;
        }
        else {
            $showPastDue = (boolean)($this->getResponseHandler()->getResponseType() === "html");
        }

        $data = Tasks::getTasks($startDate, $endDate, $showPastDue);

        $this->standardResponse($data);
    }

    /**
     * API Function to get a specified Task.
     * @param $idTask
     * @Required_Privilege: isUser of Task or has Read permission on Tasks's Group
     */
    public function getTaskAPI($idTask)
    {
        $this->verifyTask($idTask);

        $data = Tasks::getTask($idTask);

        $this->standardResponse($data);
    }

    /**
     * API Function that Creates a task on top of an already Identified Task Controller.
     * @throws Exception
     * @Required_Privilege: Create
     */
    public function createTaskAPI()
    {
        $this->verifyPrivilege(Privilege::CREATE);

        $data = Request::get()->getRequestBody();
        $title = $data['title'];
        $description = $data['description'];
        $idGroup = $data['idGroup'];
        $idUser = $data['idUser'];
        $dateDue = $data['dueDate'];

        $data =  Tasks::create($this, $idUser, $idGroup, $title, $description, $dateDue);

        $this->standardResponse($data);

        $this->trigger('onTaskCreate');
    }

    /**
     * API Function that edits a specified task.
     * @param $idTask
     * @Required_Privilege: Write
     */
    public function editTaskAPI($idTask)
    {
        $task = Tasks::sqlFetch($idTask);
        $idGroup = $task->idGroup->get();
        try {
            if($idGroup)
                $this->verifyGroupRole($idGroup, Privilege::WRITE);
            else
                throw new Exception();
        }
        catch(Exception $e){
            $this->verifyPrivilege(Privilege::WRITE);
        }

        $data = Request::get()->getRequestBody();
        $title = isset($data['title'])?$data['title']:NULL;
        $description = isset($data['description'])?$data['description']:NULL;
        $idGroup = isset($data['idGroup'])?$data['idGroup']:NULL;
        $idUser = isset($data['idUser'])?$data['idUser']:NULL;
        $dateDue = isset($data['dateDue'])?$data['dateDue']:NULL;
        $isComplete = isset($data['status']['isComplete'])?$data['status']['isComplete']:NULL;
        $isRead = isset($data['status']['isRead'])?$data['status']['isRead']:NULL;
        $allDay = isset($data['status']['allDay'])?$data['status']['allDay']:NULL;

        $oldIsComplete = Tasks::sqlFetch($idTask)->status->isComplete->get();

        $data = Tasks::editTask($idTask, $title, $description, $idGroup, $idUser, $dateDue, $isComplete, $isRead, $allDay);

        $this->standardResponse($data);

        if ($oldIsComplete != $isComplete && $isComplete==true) {
            $this->trigger('onTaskComplete');
        }
    }

    /**
     * API Function to delete a specified Task.
     * @param $idTask
     * @Required_Privilege: Delete
     */
    public function deleteTaskAPI($idTask)
    {
        $task = Tasks::sqlFetch($idTask);
        $idGroup = $task->idGroup->get();
        try {
            if($idGroup)
                $this->verifyGroupRole($idGroup, Privilege::DELETE);
            else
                throw new Exception();
        }
        catch(Exception $e){
            $this->verifyPrivilege(Privilege::DELETE);
        }

        $data = Tasks::deleteTask($idTask);

        $this->standardResponse($data);
    }

    /**
     * API Function that returns the logged in user's task count, and an array of group name, id,
     * and task count in each group user belongs to.
     */
    public function getCountAPI()
    {
        $data = Tasks::getCount();
        $groups = Tasks::getGroupCount();
        $data['groupsCount'] = $groups;

        $this->standardResponse($data);
    }

    /**
     * API Fucntion that returns the Tasks of a specified Group.
     * @param $idGroup
     * @Required_Privilege: Group specific Read
     */
    public function getGroupTasksAPI($idGroup)
    {
        $this->verifyGroupRole($idGroup, Privilege::READ);

        $data = $_GET;
        $endDate = isset($data['endDate'])? $data['endDate']:date('Y-m-d H:i:s', strtotime("+6 days",strtotime('last monday', strtotime('tomorrow'))));
        $startDate = isset($data['startDate'])? $data['startDate']:date('Y-m-d H:i:s', strtotime('last monday', strtotime('tomorrow')));

        if(isset($data['showPastDue'])) {
            $showPastDue = (!$data['showPastDue'] || $data['showPastDue'] === 'false') ? false : true;
        }
        else {
            $showPastDue = (boolean)($this->getResponseHandler()->getResponseType() === "html");
        }

        $data = Tasks::getGroupTasks($idGroup ,$startDate, $endDate, $showPastDue);

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
        global $pool;

        if($error == false)
            $error = $this->messages[$responseCode];

        $responseData = [
            'data' => $data
        ];

        $response = $this->getResponseHandler();

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

        $response->setResponse($responseData, $responseCode, false);
        $response->setTemplate('tasks.base.html.twig', 'server');
        $response->setTemplate('module.tasks.handlebars.html', 'client', $this);
        if($error)
            $response->setErrorResponse(new Exception($error));

    }

    /**
     * Helper function that verifies a Role under a specific Group using that groups Controller Id.
     * @param $idGroup
     * @param $role
     * @throws \Exception
     */
    private function verifyGroupRole($idGroup, $role){
        $group = Groups::sqlFetch($idGroup);
        $idController = $group->idController->get();
        $idControllerOriginal = $this->getId();

        $this->setId($idController);
        $this->verifyPrivilege($role);
        $this->setId($idControllerOriginal);
    }

    /**
     * Helper Function that returns a boolean if the user has the specified group specific permissions.
     * @param $idGroup
     * @param $role
     * @param int $idUser
     * @return bool
     * @throws Exception
     * @throws \eprocess360\v3core\Controller\ControllerException
     */
    private function hasGroupRole($idGroup, $role, $idUser = 0){
        $group = Groups::sqlFetch($idGroup);
        $idController = $group->idController->get();
        $idControllerOriginal = $this->getId();

        $this->setId($idController);
        $result = $this->hasPrivilege($role, null, null, $idUser);
        $this->setId($idControllerOriginal);

        return $result;
    }

    /**
     * Helper function that verifies the user is attempting to look at a task that belongs to them or one of their groups.
     * @param $idTask
     * @throws Exception
     */
    private function verifyTask($idTask){
        global $pool;
        $task = Tasks::sqlFetch($idTask);
        $idGroup = $task->idGroup->get();
        $idUser = $task->idUser->get();

        if(!($this->hasGroupRole($idGroup, Privilege::READ) || $idUser==$pool->User->getIdUser())){
            throw new Exception("Cannot interact with a Task that does not belong to yourself or your groups.");
        }
    }


    /*********************************************   #WORKFLOW#  *********************************************/


    /**
     * Identify function used to define the scope within this Task Controller will create task upon.
     * @param $baseObject
     * @param $idObject
     * @param $url
     * @return $this
     */
    public function identify($baseObject, $idObject, $url)
    {
        $this->baseObject = $baseObject;
        $this->idObject = $idObject;
        $this->url = $url;

        return $this;
    }

    /**
     * Creation function for a task on top of an already Identified Task Controller.
     * @param $title
     * @param $description
     * @param $idGroup
     * @param $idUser
     * @param $dateDue
     * @return $this
     */
    public function create($title, $description, $idGroup, $idUser, $dateDue)
    {
        Tasks::create($this, $idUser, $idGroup, $title, $description, $dateDue);

        return $this;
    }

    /**
     * Getter function that returns the idObject, specified on Identify.
     * @return mixed
     */
    public function getIdObject()
    {
        return $this->idObject;
    }

    /**
     * Getter function that returns the baseObject (which is the Controller/Module), specified on Identify.
     * @return mixed
     */
    public function getBaseObject()
    {
        return $this->baseObject;
    }

    /**
     * Getter function that returns the url, specified on Identify.
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }


    /******************************************   #INITIALIZATION#  ******************************************/





    /*********************************************   #TRIGGERS#  *********************************************/


    /**
     * Trigger that fires when a task is created.
     * @param \Closure $closure
     * @return \eprocess360\v3core\Controller\Trigger\Trigger
     */
    public function onTaskCreate($closure)
    {
        return $this->addTrigger(__FUNCTION__, $closure);
    }

    /**
     * Trigger that fires when a Task is completed
     * @param \Closure $closure
     * @return \eprocess360\v3core\Controller\Trigger\Trigger
     */
    public function onTaskComplete($closure)
    {
        return $this->addTrigger(__FUNCTION__, $closure);
    }
}