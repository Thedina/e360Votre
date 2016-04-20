<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 12/1/15
 * Time: 11:29 AM
 */

namespace eprocess360\v3modules\Review\Model;
use Dompdf\Exception;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Module;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Entry;
use eprocess360\v3core\Keydict\Entry\Bits8;
use eprocess360\v3core\Keydict\Entry\Datetime;
use eprocess360\v3core\Keydict\Entry\FixedString128;
use eprocess360\v3core\Keydict\Entry\FixedString256;
use eprocess360\v3core\Keydict\Entry\FixedString32;
use eprocess360\v3core\Keydict\Entry\Flag;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\JSONArrayFixed128;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\String;
use eprocess360\v3core\Keydict\Entry\TinyInteger;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;
use eprocess360\v3core\DB;
use eprocess360\v3modules\Review\Review;
use eprocess360\v3modules\Task\Model\Tasks;
use eprocess360\v3modules\Task\Task;
use eprocess360\v3core\Files\Folder;
use eprocess360\v3core\Model\Files;
use eprocess360\v3core\Model\Folders;
use eprocess360\v3core\Files\File;

/**
 * Class Reviews
 * @package eprocess360\v3modules\Review\Model
 */
class Reviews extends Model
{

    const REVIEW_SQL_COLUMNS = " Reviews.idReview, ReviewTypes.idReviewType, Tasks.idTask, Tasks.idUser, Tasks.title,
        Tasks.description, Tasks.idGroup, Reviews.status_0, Tasks.status_0 as taskStatus,Tasks.dateCreated,
        Tasks.dateDue, Tasks.dateCompleted, Reviews.idFolder";

    /**
     * @return $this
     * @throws Keydict\Exception\KeydictException
     * @throws \Exception
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idReview', 'Review ID'),
            IdInteger::build('idTask', 'Task ID'),
            IdInteger::build('idFolder', 'Folder ID'),
            IdInteger::build('idReviewType', 'ID Review Type'),
            Bits8::make('status',
                Keydict\Entry\Bit::build(0, 'isAccepted'),
                Keydict\Entry\Bit::build(1, 'isComplete') //TODO REMOVE isComplete From Reviews, it already exists in Tasks
            )
        )->setName('Reviews')->setLabel('Reviews');
    }

    /**
     * @param int $idTask
     * @param int $idFolder
     * @param string $idReviewType
     * @param array $status
     * @return Reviews
     */
    public static function make($idTask = 0, $idFolder = 0, $idReviewType = '', $status = [0])
    {

        $rowData = [
            'idTask' => $idTask,
            'idFolder' => $idFolder,
            'idReviewType' => $idReviewType,
            'status' => $status];

        return self::ReviewConstruct($rowData);
    }

    /**
     * @param array $rowData
     * @return Reviews
     */
    public static function ReviewConstruct($rowData = [])
    {
        $instance = new self();
        $instance->data = self::keydict();
        $instance->data->acceptArray($rowData);
        return $instance;
    }

    /**
     * @param Review $review
     * @param String $idReviewType
     * @param String $description
     * @param Integer $idGroup
     * @param Integer $idUser
     * @param String $dateDue
     * @return array|string
     */
    public static function create(Review $review, $idReviewType, $description, $idGroup, $idUser, $dateDue)
    {
        $reviewTypeTitle = ReviewTypes::sqlFetch($idReviewType)->title->get();
        $title = $reviewTypeTitle." Review";
        $idObject = $review->getIdObject();
        /** @var Module|Controller $baseObject */
        $baseObject = $review->getBaseObject();
        $url = $review->getUrl();
        $controller = $baseObject->getClosest('Project');

        $idProject = $controller->getIdProject();

        $idFolder = $review->getFolderRoot()->addNewFolder($idProject, $reviewTypeTitle, ['locked'=>0])->getIDFolder();
        $status = ['isAccepted'=>0, 'isComplete'=>0];

        $hasReview = true;
        $allDay = true;
        /** @var Task $taskController */
        $taskController = $review->getTask();
        $taskController->identify($baseObject, $idObject, $url);


        $task = Tasks::create($taskController, $idUser, $idGroup, $title, $description, $dateDue, $hasReview, $allDay);

        $idTask = $task['idTask'];

        $f = static::make($idTask, $idFolder, $idReviewType, $status);
        $f->insert();

        /** @var Table $taskTable */
        $url = $url.'/'.$review->getName().'/'.$f->data->idReview->get();
        $taskTable = Tasks::sqlFetch($idTask);
        $taskTable->url->set($url);
        $taskTable->update();

        $result = ["idReview" => $f->data->idReview->get(),
            "idUser" => $idUser,
            'idGroup' => $idGroup,
            "idReviewType" => $idReviewType,
            "title" => $title,
            "description" => $description,
            "dateCreated" => $task['dateCreated'],
            "dateDue" => $dateDue,
            "dateCompleted" => NULL,
            "status" => ["isAccepted"=> false, "isComplete" => false]];

        return $result;
    }

    /**
     * @param Review $reviewController
     * @return array|null
     * @throws \Exception
     */
    public static function getReviews(Review $reviewController)
    {
        $idObject = $reviewController->getIdObject();
        /** @var Module|Controller $baseObject */
        $baseObject = $reviewController->getBaseObject();
        $idObjectComponent = $baseObject->getId();
        /** @var Task $task */
        $task = $reviewController->getTask();
        $idTaskComponent = $task->getId();
        $idProject = $baseObject->getClosest('Project')->getIdProject();


        $idController = $baseObject->getClosest('Project')->getId();

        $hasReview = Tasks::keydict()->status->hasReview->getInteger();
        $sql = "SELECT *, CAST(Reviews.status_0 AS UNSIGNED) AS reviewStatus, CAST(Tasks.status_0 AS UNSIGNED) AS taskStatus
                FROM Reviews
                LEFT JOIN Tasks ON Reviews.idTask = Tasks.idTask
                WHERE Tasks.idController = {$idController}
                  AND Tasks.idObjectComponent = {$idObjectComponent}
                  AND Tasks.idTaskComponent = {$idTaskComponent}
                  AND Tasks.idProject = {$idProject}
                  AND Tasks.idObject = {$idObject}
                  AND Tasks.status_0 & {$hasReview}";

        $reviews = DB::sql($sql);
        foreach ($reviews as &$review) {
            $review['status_0'] = $review['reviewStatus'];
            unset($review['reviewStatus']);
            Keydict::wakeupAndTranslateStatus(self::keydict(), $review);

            $review['status_0'] = $review['taskStatus'];
            unset($review['taskStatus']);
            Keydict::wakeupAndTranslateStatus(Tasks::keydict(), $review);
        }

        $result = ['reviews' => $reviews];
        self::createLinks($result, $reviewController);
        $result['groups'] = $reviewController->getGroups();
        $result['reviewers'] = $reviewController->getReviewers();
        $result['reviewTypes'] = $reviewController->getReviewTypes();
        $result['objectIsComplete'] = $reviewController->getObjectIsComplete();
        $result['objectTitle'] = $reviewController->getObjectTitle();

        return $result;
    }


    /**
     * @param Review $reviewController
     * @param $idReview
     * @return mixed
     * @throws Exception
     * @throws \Exception
     */
    public static function getReview(Review $reviewController, $idReview)
    {
        $sql = "SELECT *, CAST(Reviews.status_0 AS UNSIGNED) AS reviewStatus, CAST(Tasks.status_0 AS UNSIGNED) AS taskStatus
                FROM Reviews
                LEFT JOIN Tasks ON Reviews.idTask = Tasks.idTask
                WHERE Reviews.idReview = {$idReview}";

        $reviews = DB::sql($sql);
        if(!empty($reviews)) {
            $review = $reviews[0];

            $review['status_0'] = $review['reviewStatus'];
            unset($review['reviewStatus']);
            Keydict::wakeupAndTranslateStatus(self::keydict(), $review);

            $review['status_0'] = $review['taskStatus'];
            unset($review['taskStatus']);
            Keydict::wakeupAndTranslateStatus(Tasks::keydict(), $review);

            self::createLinks($review, $reviewController);
            $review['groups'] = $reviewController->getGroups();
            $review['reviewers'] = $reviewController->getReviewers();
            $review['reviewTypes'] = $reviewController->getReviewTypes();
            $review['objectIsComplete'] = $reviewController->getObjectIsComplete();
            $review['objectTitle'] = $reviewController->getObjectTitle();
            self::addFiles($review['idFolder'], 'files', $review);

            if ($reviewController->getObjectFolder())
                self::addFiles($reviewController->getObjectFolder(), 'reviewableFiles', $review);

            return $review;
        }

        throw new Exception("Invalid Review ID");
    }

    /**
     * @param $idReview
     * @return bool
     * @throws \Exception
     */
    public static function deleteReview($idReview)
    {
        $idReview = (int)$idReview;
        $review = self::sqlFetch($idReview);

        $sql = "SELECT Files.idFile FROM Files WHERE idFolder = {$review->idFolder->get()}";
        $files = DB::sql($sql);

        foreach ($files as $file) {
            if (isset($file['idFile']))
                Files::deleteById((int)$file['idFile']);
        }

        Folders::deleteById($review->idFolder->get());
        Tasks::deleteById($review->idTask->get());
        self::deleteById($idReview);

        return true;
    }

    /**
     * @param Review $reviewController
     * @param $idReview
     * @param $idReviewType
     * @param $description
     * @param $idGroup
     * @param $idUser
     * @param $dateDue
     * @param $isAccepted
     * @param $isComplete
     * @return Table
     * @throws Exception
     * @throws Keydict\Exception\InvalidValueException
     * @throws Keydict\Exception\KeydictException
     * @throws \Exception
     */
    public static function editReview(Review $reviewController, $idReview, $idReviewType, $description, $idGroup, $idUser, $dateDue, $isAccepted, $isComplete)
    {
        global $pool;
        $review = self::sqlFetch($idReview);

        if($idReviewType !== null)
            $review->idReviewType->set($idReviewType);
        if($isAccepted !== null)
            $review->status->isAccepted->set($isAccepted);

        if($idReviewType) {
            $reviewTypeTitle = ReviewTypes::sqlFetch($idReviewType)->title->get();
            $title = $reviewTypeTitle . " Review";
        }
        else
            $title = null;
        $idTask = $review->idTask->get();
        Tasks::editTask($idTask, $title, $description, $idGroup, $idUser, $dateDue, $isComplete);

        $review->update();

        $sql = "SELECT *, CAST(Reviews.status_0 AS UNSIGNED) AS reviewStatus, CAST(Tasks.status_0 AS UNSIGNED) AS taskStatus
                FROM Reviews LEFT JOIN  Tasks ON Reviews.idTask = Tasks.idTask
                WHERE Reviews.idReview = {$idReview}";

        $reviews = DB::sql($sql);
        if(!empty($reviews)) {
            $review = $reviews[0];

            $review['status_0'] = $review['reviewStatus'];
            unset($review['reviewStatus']);
            Keydict::wakeupAndTranslateStatus(self::keydict(), $review);

            $review['status_0'] = $review['taskStatus'];
            unset($review['taskStatus']);
            Keydict::wakeupAndTranslateStatus(Tasks::keydict(), $review);

            self::createLinks($review, $reviewController);

            return $review;
        }

        throw new Exception("Invalid Review ID");
    }

    /**
     * @param $review
     * @param Review $ReviewController
     */
    public static function createLinks(&$review, Review $ReviewController)
    {
        global $pool;
        $ref = array();
        $basePath = $pool->SysVar->siteUrl().$ReviewController->getUrl();

        if(isset($review['idReview'])){
            $reviewTypeTile = '';
            $reviewTypes = $ReviewController->getReviewTypes();
            foreach($reviewTypes as $reviewType) {
                if ((int)$reviewType['idReviewType'] == $review['idReviewType']){
                    $reviewTypeTile = $reviewType['title'];
                    break;
                }
            }
            $tempRef = ['rel' => 'self', 'title' =>$reviewTypeTile, 'href' => $basePath.'/'.$ReviewController->getName().'/'.$review['idReview']];
            $ref[] = $tempRef;

            $tempRef = ['rel' => 'parent', 'title' => 'Reviews', 'href' => $basePath.'/'.$ReviewController->getName()];
            $ref[] = $tempRef;

            $tempRef = ['rel' => 'grandparent', 'title' => $ReviewController->getObjectTitle(), 'href' => $basePath];
            $ref[] = $tempRef;
        }
        else{
            $tempRef = ['rel' => 'self', 'title' => 'Reviews', 'href' => $basePath.'/'.$ReviewController->getName()];
            $ref[] = $tempRef;

            $tempRef = ['rel' => 'parent', 'title' => $ReviewController->getObjectTitle(), 'href' => $basePath];
            $ref[] = $tempRef;
        }
        $review['links'] = $ref;
    }

    /**
     * @param $idFolder
     * @param $variableName
     * @param $object
     */
    public static function addFiles($idFolder, $variableName, &$object)
    {
        $files = array();
        foreach (Files::each("SELECT * FROM `Files` WHERE `idFolder` = '{$idFolder}' ORDER BY `idFile` ASC")
                 as $sqlFiles){
            $resultFilesArray = $sqlFiles->toArray();
            Files::downloadUrl($resultFilesArray);

            array_push($files, $resultFilesArray);
        }
        if($files !== []){
            $object[$variableName] = $files;
        }
    }

    /**
     * @param $idReview
     * @return Boolean
     * @throws \Exception
     */
    public static function isComplete($idReview)
    {
        return self::sqlFetch($idReview)->status->isComplete->get();
    }

}