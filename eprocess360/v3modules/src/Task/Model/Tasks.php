<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 8/19/2015
 * Time: 7:16 PM
 */

namespace eprocess360\v3modules\Task\Model;

use Dompdf\Exception;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Module;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Entry\Bit;
use eprocess360\v3core\Keydict\Entry\Bits8;
use eprocess360\v3core\Keydict\Entry\Datetime;
use eprocess360\v3core\Keydict\Entry\FixedString128;
use eprocess360\v3core\Keydict\Entry\FixedString256;
use eprocess360\v3core\Keydict\Entry\FixedString32;
use eprocess360\v3core\Keydict\Entry\Flag;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\IdTinyInt;
use eprocess360\v3core\Keydict\Entry\JSONArrayFixed128;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\TinyInteger;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;
use eprocess360\v3core\DB;
use eprocess360\v3core\Controller\Warden\Privilege;
use eprocess360\v3modules\Review\Model\Reviews;
use eprocess360\v3modules\Task\Task;

/**
 * @package eprocess360\v3core\Model
 */
class Tasks extends Model
{
    const TASK_SQL_COLUMNS = " Tasks.idTask, Tasks.idUser, Tasks.title,
                Tasks.description, Tasks.idGroup, Tasks.status_0,Tasks.dateCreated,
                Tasks.dateDue, Tasks.dateCompleted, Tasks.url ";

    /**
     * @return $this
     * @throws Keydict\Exception\KeydictException
     * @throws \Exception
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idTask', 'Task ID'),
            IdInteger::build('idTaskComponent', 'Task Component ID'),
            IdInteger::build('idController', 'Controller ID'),
            IdInteger::build('idObjectComponent', 'Object Component ID'),
            IdInteger::build('idProject', 'Project ID'),
            IdInteger::build('idObject', 'Object ID'),
            IdInteger::build('idUser', 'User ID'),
            IdInteger::build('idGroup', 'Group ID'),
            FixedString128::build('title', 'Task Title'),
            FixedString128::build('description', 'Task Description'),
            FixedString128::build('url', 'Task URL'),
            Datetime::build('dateCreated', 'Date Created'),
            Datetime::build('dateDue', 'Date Due'),
            Datetime::build('dateCompleted', 'Date Completed'),
            Bits8::make('status',
                Bit::build(0, 'isComplete', 'Complete Task'),
                Bit::build(1, 'isRead', 'Read Task'),
                Bit::build(2, 'hasReview', 'Has Review'),
                Bit::build(3, 'allDay', 'All Day')
            )
        )->setName('Tasks')->setLabel('Tasks');
    }

    /**
     * @param int $idTaskComponent
     * @param int $idController
     * @param int $idObjectComponent
     * @param int $idProject
     * @param int $idObject
     * @param int $idUser
     * @param int $idGroup
     * @param string $title
     * @param string $description
     * @param string $url
     * @param string $dateCreated
     * @param string $dateDue
     * @param string $dateCompleted
     * @param array $status
     * @return Tasks
     */
    public static function make($idTaskComponent = 0, $idController = 0, $idObjectComponent = 0, $idProject = 0,
                                $idObject = 0, $idUser = 0, $idGroup = 0, $title = '', $description = '',
                                $url = '', $dateCreated = NULL,  $dateDue = NULL, $dateCompleted = NULL, $status = [0]) {

        $rowData = [
            'idTaskComponent'=>$idTaskComponent,
            'idController'=>$idController,
            'idObjectComponent'=>$idObjectComponent,
            'idProject'=>$idProject,
            'idObject'=>$idObject,
            'idUser'=>$idUser,
            'idGroup'=>$idGroup,
            'title'=>$title,
            'description'=>$description,
            'url'=>$url,
            'dateCreated'=>$dateCreated,
            'dateDue'=>$dateDue,
            'dateCompleted'=>$dateCompleted,
            'status'=>$status];

        return self::TaskConstruct($rowData);
    }

    /**
     * @param array $rowData
     * @return Tasks
     */
    public static function TaskConstruct($rowData = []) {
        $instance = new self();
        $instance->data = self::keydict();
        $instance->data->acceptArray($rowData);
        return $instance;
    }

    /**
     * @param Task $taskController
     * @param $idUser
     * @param $idGroup
     * @param $title
     * @param $description
     * @param $dateDue
     * @param bool|false $hasReview
     * @return array|string
     */
    public static function create(Task $taskController, $idUser, $idGroup, $title, $description, $dateDue, $hasReview = false, $allDay = false) {

        $idObject = $taskController->getIdObject();
        /** @var Module|Controller $baseObject */
        $baseObject = $taskController->getBaseObject();
        $url = $taskController->getUrl();
        $idTaskComponent = $taskController->getId();

        $controller = $baseObject->getClosest('Project');
        $idObjectComponent = $baseObject->getId();
        $idController = $controller->getId();
        $idProject = $controller->getIdProject();
        $status = ['isComplete'=>0, 'isRead'=>0, 'hasReview'=>$hasReview, 'allDay' => $allDay];
        $dateCreated = Datetime::timestamps();

        $f = static::make($idTaskComponent, $idController, $idObjectComponent, $idProject, $idObject,
            $idUser, $idGroup, $title, $description, $url, $dateCreated,  $dateDue, $dateCompleted = NULL, $status);
        $f->insert();

        $result = $f->data->toArray();


        return $result;
    }

    /**
     * @param string $startDate
     * @param string $endDate
     * @param bool|false $showPastDue
     * @return array|null
     * @throws \Exception
     */
    public static function getTasks($startDate = '', $endDate = '', $showPastDue = false)
    {
        global $pool;

        if(!$startDate)
            $startDate = Datetime::timestamps();
        $startDate = Datetime::validate($startDate);
        $endDate = Datetime::validate($endDate);
        $showPastDue = (int)$showPastDue;
        $idUser = (int)$pool->User->getIdUser();
        $currentDate = Datetime::timestamps();
        $allDayDate = date("Y-m-d H:i:s", strtotime(date("Y-m-d", strtotime($currentDate))));

        $columns = self::TASK_SQL_COLUMNS.", (NOT (Tasks.status_0 & 0b1) AND (CASE WHEN Tasks.status_0 & 0b1000 THEN Tasks.dateDue < '{$allDayDate}' ELSE Tasks.dateDue <= '{$currentDate}' END)) as pastDue ";

        $sql = "SELECT DISTINCT {$columns}
                FROM Tasks
                WHERE
                  Tasks.idUser = {$idUser}
                  AND ((DATE(Tasks.dateDue) >= DATE('{$startDate}')
                    AND DATE(Tasks.dateDue) <= DATE('{$endDate}'))
                  OR ({$showPastDue}
                    AND (CASE WHEN Tasks.status_0 & 0b1000
                         THEN Tasks.dateDue < '{$allDayDate}'
                         ELSE Tasks.dateDue <= '{$currentDate}'
                         END)))
                ORDER BY Tasks.dateCreated DESC";

        $tasks = DB::sql($sql);

        foreach ($tasks as &$task) {
            $keydict = self::keydict();
            self::wakeupAndTranslateStatus($keydict, $task);
            $task['pastDue'] = (boolean)$task['pastDue'];
        }

        return $tasks;
    }

    /**
     * @param $idTask
     * @return mixed
     * @throws Exception
     * @throws \Exception
     */
    public static function getTask($idTask)
    {
        $currentDate = Datetime::timestamps();
        $allDayDate = date("Y-m-d H:i:s", strtotime(date("Y-m-d", strtotime($currentDate))));
        $columns = self::TASK_SQL_COLUMNS.", (NOT (Tasks.status_0 & 0b1) AND (CASE WHEN Tasks.status_0 & 0b1000 THEN Tasks.dateDue < '{$allDayDate}' ELSE Tasks.dateDue <= '{$currentDate}' END)) as pastDue ";

        $sql = "SELECT {$columns} FROM Tasks WHERE Tasks.idTask = {$idTask}";

        $tasks = DB::sql($sql);
        foreach ($tasks as &$task) {
            $keydict = self::keydict();
            self::wakeupAndTranslateStatus($keydict, $task);
            $task['pastDue'] = (boolean)$task['pastDue'];
            return $task;
        }

        throw new Exception("Invalid Task ID");
    }

    /**
     * @param $idTask
     * @return bool
     * @throws Keydict\Exception\KeydictException
     * @throws \Exception
     */
    public static function deleteTask($idTask)
    {
        $task = self::sqlFetch($idTask);

        if($task->status->hasReview->get()){
            $sql = "SELECT Reviews.idReview FROM Reviews WHERE Reviews.idTask = {$idTask}";
            $reviews = DB::sql($sql);
            foreach($reviews as $review)
                Reviews::deleteReview($review['idReview']);
        }
        else
            self::deleteById($idTask);

        return true;
    }

    /**
     * @param $idTask
     * @param $title
     * @param $description
     * @param $idGroup
     * @param $idUser
     * @param $dateDue
     * @param $isComplete
     * @return array|string
     * @throws Keydict\Exception\InvalidValueException
     * @throws Keydict\Exception\KeydictException
     * @throws \Exception
     */
    public static function editTask($idTask, $title, $description, $idGroup, $idUser, $dateDue, $isComplete, $isRead= null, $allDay = null)
    {
        global $pool;
        $task = self::sqlFetch($idTask);

        if($title !== null)
            $task->title->set($title);
        if($description !== null)
            $task->description->set($description);
        if($idGroup !== null)
            $task->idGroup->set($idGroup);
        if($idUser !== null)
            $task->idUser->set($idUser);
        if($dateDue !== null)
            $task->dateDue->set($dateDue);
        if(!$task->status->isComplete->get() && $isComplete)
            $task->dateCompleted->set(Datetime::timestamps());
        if($isComplete !== null)
            $task->status->isComplete->set($isComplete);
        if($isRead !== null)
            $task->status->isRead->set($isRead);
        if($isRead !== null)
            $task->status->allDay->set($allDay);

        $task->update();

        $result = $task->toArray();

        return $result;
    }

    /**
     * @param Task $taskController
     * @return array|null
     * @throws \Exception
     */
    public static function findTask(Task $taskController)
    {
        $idObject = $taskController->getIdObject();
        $baseObject = $taskController->getBaseObject();
        $idObjectComponent = $baseObject->getId();
        $idTaskComponent = $taskController->getId();
        $idProject = $baseObject->getClosest('Project')->getIdProject();

        $idController = $baseObject->getClosest('Project')->getIdController();

        $hasReview = Tasks::keydict()->status->hasReview->getInteger();
        $currentDate = Datetime::timestamps();
        $columns = self::TASK_SQL_COLUMNS.", Tasks.dateDue <= '{$currentDate}' as pastDue ";

        $sql = "SELECT {$columns} FROM Tasks WHERE Tasks.idController = {$idController}
                AND Tasks.idObjectComponent = {$idObjectComponent} AND Tasks.idTaskComponent = {$idTaskComponent} AND
                Tasks.idProject = {$idProject} AND Tasks.idObject = {$idObject}";

        $tasks = DB::sql($sql);
        foreach ($tasks as &$task) {
            $keydict = self::sqlFetch($task['idTask']);
            self::translateStatus($keydict, $task);
            $task['pastDue'] = (boolean)$task['pastDue'];
        }

        return $tasks;
    }

    /**
     * @param $idTask
     * @return array
     * @throws Exception
     * @throws \Exception
     */
    public static function getCount()
    {
        global $pool;
        $currentDate = Datetime::timestamps();
        $idUser = $pool->User->getIdUser();

        $sql = "SELECT COUNT(Tasks.idTask) as taskCount, COUNT(CASE WHEN Tasks.dateDue <=
                '{$currentDate}' THEN 1 END) as pastDue FROM Tasks WHERE Tasks.idUser = {$idUser} AND NOT Tasks.status_0 & 0b1";

        $tasks = DB::sql($sql);

        return $tasks[0];
    }

    /**
     * @param $idGroup
     * @param string $startDate
     * @param string $endDate
     * @param bool|false $showPastDue
     * @return array|null
     * @throws \Exception
     */
    public static function getGroupTasks($idGroup ,$startDate = '', $endDate = '', $showPastDue = false)
    {
        if(!$endDate)
            $endDate = Datetime::timestamps();
        $startDate = Datetime::validate($startDate);
        $endDate = Datetime::validate($endDate);
        $showPastDue = (int)$showPastDue;
        $currentDate = Datetime::timestamps();
        $allDayDate = date("Y-m-d H:i:s", strtotime(date("Y-m-d", strtotime($currentDate))));

        $columns = self::TASK_SQL_COLUMNS.", (NOT (Tasks.status_0 & 0b1) AND (CASE WHEN Tasks.status_0 & 0b1000 THEN Tasks.dateDue < '{$allDayDate}' ELSE Tasks.dateDue <= '{$currentDate}' END)) as pastDue, CONCAT(Users.firstName, ' ', Users.lastName) AS userName ";

        $sql = "SELECT DISTINCT {$columns}
                FROM Tasks
                  LEFT JOIN Users ON Tasks.idUser = Users.idUser
                  LEFT JOIN Groups ON Tasks.idGroup = Groups.idGroup
                WHERE Groups.idGroup = {$idGroup}
                  AND (DATE(Tasks.dateDue) >= DATE('{$startDate}')
                    AND DATE(Tasks.dateDue) <= DATE('{$endDate}')
                    OR ({$showPastDue}
                      AND (CASE WHEN Tasks.status_0 & 0b1000
                         THEN Tasks.dateDue < '{$allDayDate}'
                         ELSE Tasks.dateDue <= '{$currentDate}'
                         END)))
                ORDER BY Tasks.dateCreated DESC";

        $tasks = DB::sql($sql);

        foreach ($tasks as &$task) {
            $keydict = self::keydict();
            self::wakeupAndTranslateStatus($keydict, $task);
            $task['pastDue'] = (boolean)$task['pastDue'];
        }

        return $tasks;
    }

    /**
     * @return array|null
     * @throws \Exception
     */
    public static function getGroupCount()
    {
        global $pool;
        $currentDate = Datetime::timestamps();
        $idUser = $pool->User->getIdUser();
        $sql = "SELECT
                  DISTINCT Groups.idGroup,
                  Groups.title,
                  COUNT(Tasks.idTask) as taskCount,
                  COUNT(CASE WHEN Tasks.dateDue <= '{$currentDate}' AND NOT Tasks.status_0 & 0b1 THEN 1 END) as pastDue
                  FROM Tasks
                  LEFT JOIN GroupUsers
                    ON Tasks.idGroup = GroupUsers.idGroup
                  LEFT JOIN Groups
                    ON GroupUsers.idGroup = Groups.idGroup
                  WHERE
                    GroupUsers.idUser = {$idUser}
                    AND NOT Tasks.status_0 & 0b1
                  GROUP BY Groups.idGroup
                  ORDER BY Tasks.dateCreated DESC";

        $tasks = DB::sql($sql);

        foreach($tasks as &$task) {
            $task['idGroup'] = (int)$task['idGroup'];
            $task['taskCount'] = (int)$task['taskCount'];
            $task['pastDue'] = (boolean)$task['pastDue'];
        }

        return $tasks;
    }

//*Get a Task From an Object
//Front End Tasks needs: SELECT Tasks.idUser, Tasks.title, Tasks.description, Tasks.url, Tasks.idGroup, Tasks.status_0, Tasks.dateCreated, Tasks.dateDue, Tasks.dateCompleted
//Reviews LEFT JOIN
//$sql = "SELECT Tasks.idUser, Tasks.title, Tasks.description, Tasks.url,
//        Tasks.idGroup, Tasks.status_0, Tasks.dateCreated, Tasks.dateDue,
//        Tasks.dateCompleted FROM Tasks WHERE Tasks.idController = {}
//        AND Tasks.idObjectComponent = {} AND Tasks.idTaskComponent = {} AND
//        Tasks.idProject = {} AND Tasks.idObject = {}"
//$tasks = DB::sql($sql);
//
//*Get ALl Tasks for a User
//$sql = "SELECT Tasks.idUser, Tasks.title, Tasks.description, Tasks.url,
//        Tasks.idGroup, Tasks.status_0, Tasks.dateCreated, Tasks.dateDue,
//        Tasks.dateCompleted FROM Tasks WHERE Tasks.idController = {}
//        AND Tasks.idObjectComponent = {} AND Tasks.idTaskComponent = {} AND
//        Tasks.idProject = {} AND Tasks.idObject = {}"
//$tasks = DB::sql($sql);
//
//Non Completed: AND Tasks.status_0 & Task::IS_COMPLETE
//Non Completed and Over Due: AND Tasks.status_0 & Task::IS_COMPLETE AND Tasks.dateDue < Task::CurrentDate

}