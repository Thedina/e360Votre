<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 11/17/15
 * Time: 3:44 PM
 */
namespace eprocess360\v3core;

use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Children;
use eprocess360\v3core\Controller\Project;
use eprocess360\v3core\Model\Controllers;
use eprocess360\v3core\Model\Projects;
use eprocess360\v3core\Keydict\Entry\Bits;
use eprocess360\v3modules\Submittal\Model\SubmittalPhases;
use eprocess360\v3modules\Submittal\Submittal;
use eprocess360\v3modules\Submittal\Model\Submittals;
use eprocess360\v3core\Scheduler;
use eprocess360\v3modules\FolderRoot\FolderRoot;
use eprocess360\v3core\Files\Folder;
use eprocess360\v3core\Files\File;

$idProject = 1;
$folderName = 'TestingFolder';
$idController = Projects::sqlFetch($idProject)->idController->get();
/** @var Children|Controller|Dummy $controller */
$controller = Project::getProjectController($idController)->setName('projects')->setObjectId($idProject);
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

///** ADD 3 SubmittlePhases and 4 Submittals to the database*/
//***********************************************************************************

$status = true;
$parent = 0;
$newSubmittal = SubmittalPhases::create($submittals, "Title 1", "Desc 1", $parent, $status);
//$newSubmittal = SubmittalPhases::create($submittals, "Title 2", "Desc 2", $newSubmittal['idSubmittalPhase'], $status);
//$newSubmittal = SubmittalPhases::create($submittals, "Title 3", "Desc 3", $newSubmittal['idSubmittalPhase'], $status);
//var_dump(json_encode($newSubmittal));
//$newSubmittals = Submittals::create($submittals, $newSubmittal['idSubmittalPhase']);
//$newSubmittals = Submittals::create($submittals, $newSubmittal['idSubmittalPhase']);
//$newSubmittals = Submittals::create($submittals, $newSubmittal['idSubmittalPhase']);
//$newSubmittals = Submittals::create($submittals, $newSubmittal['idSubmittalPhase']);

//***********************************************************************************
//Submittals::deleteSubmittal($submittals, 1);
//var_dump(json_encode($newSubmittals));
//$result = SubmittalPhases::getById($submittals, 2);
//var_dump(json_encode($result));
//$data = SubmittalPhases::allReadableSubmittalPhases($submittals);
//var_dump(json_encode($data));
//
//$getIdResult = Submittals::getById($submittals, 1);
//var_dump(json_encode($getIdResult));
//
//$getByPhaseIdResult = Submittals::getByPhaseId($submittals, 3);
//var_dump(json_encode($getByPhaseIdResult));

//echo  "Congratz~!";

//if($_SERVER['REQUEST_METHOD'] == 'POST') {
//    $uploadFilesResult = Submittals::uploadFiles($submittals, 1);
//    //var_dump(json_encode($uploadFilesResult));
//    if($uploadFilesResult['data'] !== NULL) {
//        var_dump(json_encode($uploadFilesResult['data']));
//        echo "You're File Has Been Uploaded~";
//
//        $file = File::getByID($uploadFilesResult['data'][0]['idFile']);
//        $fileNamePart = explode('.', $file->getFileName());
//        $fileExtension = end($fileNamePart);

//        $file = Submittals::getSubmittalFileById(1);
//        var_dump(json_encode($file));

//
//        ob_start();
//
//        header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
//        header("Cache-Control: public"); // needed for i.e.
//        header("Content-Type: application/".$fileExtension);
//        header("Content-Transfer-Encoding: Binary");
//        header("Content-Length:".$file->getSize());
//        header("Content-Disposition: attachment; filename=".$file->getFileName());
//        readfile($file->getRealDownloadURL());
//
//        ob_flush();
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
//    $html .= "<form action='{$site_url}/SubmittalsTest' method='post' enctype='multipart/form-data'>";
//    $html .= "<select name='category[]'>".$categoryOptions."</select>";
//    $html .= "<input type='file' name='test-file'/>";
//    $html .= "<input type='text' name='desc[]'/>";
//    $html .= "<input type='submit' name='submit' value = 'Submit'/>";
//    $html .= "</form>";
//
//    return $html;
//}


