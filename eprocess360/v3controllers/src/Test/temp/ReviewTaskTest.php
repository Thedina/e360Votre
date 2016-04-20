<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 12/4/15
 * Time: 12:32 PM
 */

namespace eprocess360\v3core;

use eprocess360\v3core\Controller\Project;
use eprocess360\v3core\Keydict\Entry\Bits;
use eprocess360\v3modules\Submittal\Submittal;
use eprocess360\v3core\Scheduler;
use eprocess360\v3core\Controller\Group;
use eprocess360\v3core\Model\Groups;
use eprocess360\v3core\Model\GroupUsers;
use eprocess360\v3modules\Review\Review;
use eprocess360\v3modules\Task\Task;

$idProject = 1;
$folderName = 'TestingFolder';
$controller = Project::getProjectControllerByIdProject($idProject);
$controller->setObjectId($idProject);
/** @var Dummy $controller*/
$controller->buildChildren();
/** @var Submittal $submittals */
$submittals = $controller->getChild('submittals');
$folderRoot = $submittals->getFolderRoot();
$categories = $folderRoot->getFileCategories();
$categoryOptions = "";
foreach($categories as $category){
    $string = "<option value='".$category."'>".$category."</option>";
    $categoryOptions = $categoryOptions.$string;
}

$type = "Random Type 2";
$description = "Cool Review 2 Desu";
$idGroup = 1;
$idUser = 1;
$dateDue = "neva";
/** @var Review $reviewController */
$reviewController =  Review::build('reviews', $controller)->setDescription('Reviews');
/** @var Task $taskController */
$taskController = $controller->getChild('tasks');
$baseObject = $controller->getChild('submittals');
$idObject = 1;
$url = "/submittals/submittal/1";
$objectTitle = "#1 Submittal";
$objectIdFolder = 1;
$reviewController->bindFolderRoot($controller->getChild('folderRoot'));
$taskController->identify($baseObject, $idObject, $url);
$reviewController->identify($baseObject, $idObject, $url, $objectTitle, $objectIdFolder, $taskController);
$idReview = 1;
$isAccepted = true;
$isComplete = true;
$idFile = 1;
$title = "Random Task";
$idTask= 1;
$submittals->addController($reviewController);
//*********************************************************************************************

$data = $reviewController->create($type, $description, $idGroup, $idUser, $dateDue);
//$idReview = $data['idReview'];
//var_dump(json_encode($data));
//
//$data = $reviewController->getReviews();
//var_dump(json_encode($data));
//
//$data = Reviews::getReview($reviewController, $idReview);
//var_dump(json_encode($data));
//
//$data = Reviews::editReview($reviewController, $idReview, "Edited Type", "desu", $idGroup, $idUser, $dateDue, $isAccepted, $isComplete);
//var_dump(json_encode($data));
//
//$data = Reviews::deleteReview($idReview);
//var_dump(json_encode($data));
//
//$data = Tasks::create($taskController, $idUser, $idGroup, $title, $description, $dateDue);
//$idTask = $data['idTask'];
//var_dump(json_encode($data));
//
//$data = Tasks::getTasks();
//var_dump(json_encode($data));
//
//$data = Tasks::getTask($idTask);
//var_dump(json_encode($data));
//
//$data = Tasks::editTask($idTask, "MWHAHAHAHAHAHAHA", $description, $idGroup, $idUser, $dateDue, $isComplete);
//var_dump(json_encode($data));
//
//$data = Tasks::findTask($taskController);
//var_dump(json_encode($data));
//
//$data = Tasks::deleteTask($idTask);
//var_dump(json_encode($data));

//*********************************************************************************************



//if($_SERVER['REQUEST_METHOD'] == 'POST') {
//    $uploadFilesResult = Reviews::uploadReviewFiles($idReview);
//    var_dump(json_encode($uploadFilesResult));
//    if($uploadFilesResult['data'] !== NULL) {
//        echo "You're File Has Been Uploaded~";
//        $data = Reviews::getReviewFile($idReview, $idFile);
//        var_dump(json_encode($data));
//    }
//    else
//        var_dump($uploadFilesResult['errors']);
//}
//else {
//    echo make_upload_form($categoryOptions);
//}
//
///**
// * @return string
// * @throws \eprocess360\v3core\SysVar\Exception\SysVarException
// */
//function make_upload_form($categoryOptions) {
//    global $pool;
//    $site_url = $pool->SysVar->get('siteUrl');
//    $html = "";
//    $html .= "<form action='{$site_url}/ReviewTaskTest' method='post' enctype='multipart/form-data'>";
//    $html .= "<select name='category[]'>".$categoryOptions."</select>";
//    $html .= "<input type='file' name='test-file'/>";
//    $html .= "<input type='text' name='desc[]'/>";
//    $html .= "<input type='submit' name='submit' value = 'Submit'/>";
//    $html .= "</form>";
//
//    return $html;
//}


//TODO ADD BELOW TO DUMMY.PHP

//Task::build(15, 'tasks', 'Tasks'),
//Review::build(16, 'reviews', 'Reviews')
//    /**
//     * @param Review $obj
//     */
//    public function initReview(Review $obj)
//    {
//        $obj->bindFolderRoot($this->getModule('folderRoot'));
//
//        $obj->onReviewComplete(function ($idReview) {
//            //$idController = ;
//            //$idObjectComponent = ;
//            //$idObject = ;
//            //This function will be called by Review with a given $idObject, found by finding the module's onReviewComplete function.
//            //When a review Completes, notify the Object.
//            //If a Submittal has a completed Review, Do stuff depending on the workflow
//            //ie. Check all Reviews for this Submittal, if they are all completed and Accepted, done.
//            // If some are Accepted but not all, create a new submittal with the non completed Reviews attached.
//        });
//    }
//
//    /**
//     * @param Review $obj
//     */
//    public function initTask(Task $obj)
//    {
//
//    }

//public function initSubmittals(Submittal $obj)
//{
//    $rootDepth = 2;
//    $depth0 = 14;
//    $depth1 = 14;
//    $depth2 = 14;
//    $obj->bindFolderRoot($this->getModule('folderRoot'));
//    $obj->bindRootDepth($rootDepth);
//    $obj->bindModuleByDepth([$depth0,$depth1,$depth2]);
//    $obj->bindReview($this->getModule('reviews'));
//    $obj->bindTask($this->getModule('tasks'));
//
//
//    $obj->onSubmittalSubmit(function ($idSubmittal) {
//        $this->getReview()->identify($this, $idSubmittal, $url, $objectTitle, $objectIdFolder, $idTaskComponent)
//            ->create($type, $description, $idGroup, $idUser, $dateDue);
//    });
//
//    $obj->onReviewComplete(function ($idSubmittal) {
//        //This function will be called by Review with a given $idObject, found by finding the module's onReviewComplete function.
//        //When a review Completes, notify the Object.
//        //If a Submittal has a completed Review, Do stuff depending on the workflow
//        //ie. Check all Reviews for this Submittal, if they are all completed and Accepted, done.
//        // If some are Accepted but not all, create a new submittal with the non completed Reviews attached.
//    });
//}



echo "    Huzzah~!";