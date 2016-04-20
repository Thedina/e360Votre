<?php

namespace eprocess360\v3modules\FolderRoot;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Dashboard;
use eprocess360\v3core\Controller\Module;
use eprocess360\v3core\Controller\Persistent;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Controller\Rules;
use eprocess360\v3core\Controller\Triggers;
use eprocess360\v3core\Controller\Warden\Privilege;
use eprocess360\v3core\Files\Folder;
use eprocess360\v3core\Model\Files;
use eprocess360\v3core\Request\Request;
use Exception;

/**
 * Class FolderRoot
 * @package eprocess360\v3core
 * Container for mapping Folders in the controller and managing permissions
 */
class FolderRoot extends Controller
{
    use Router, Module, Persistent, Triggers, Rules;
    /** @var Folder $folder*/
    private $folder;
    private $fileCategories;
    private $fileMaxSize;
    private $fileMinSize;
    private $acceptedFileTypes;
    private $specialCategories;
    private $verified;


    /**
     * Used as a fail-safe in Controllers to make sure that their dependencies and initializations are met; If not, exception is thrown.
     */
    public function dependencyCheck()
    {
        if($this->fileCategories === NULL)
            throw new Exception("FolderRoot Module does not have File Categories set, please correctly use fileAcceptParameters in the initialization function.");
        if($this->fileMaxSize === NULL)
            throw new Exception("FolderRoot Module does not have a File Max Size set, please correctly use fileAcceptParameters in the initialization function.");
        if($this->fileMinSize === NULL)
            throw new Exception("FolderRoot Module does not have File Minimum Size set, please correctly use fileAcceptParameters in the initialization function.");
        if($this->acceptedFileTypes === NULL)
            throw new Exception("FolderRoot Module does not have Accepted File Types set, please correctly use fileAcceptParameters in the initialization function.");
        if($this->specialCategories === NULL)
            throw new Exception("FolderRoot Module does not have Special Categories set, please correctly use fileAcceptParameters in the initialization function.");
    }


    /*********************************************   #ROUTING#  **********************************************/


    /**
     * Define the available routes for the module here. This function should not perform any other logic outside of
     * specifying available routes.
     */
    public function routes()
    {
        $this->routes->map('POST', '', function () {
            $this->uploadFileAPI();
        });
        $this->routes->map('GET', '/[i:idFile]', function ($idFile) {
            $this->getFileAPI($idFile);
        });
        $this->routes->map('PUT', '/[i:idFile]', function ($idFile) {
            $this->editFileAPI($idFile);
        });
        $this->routes->map('DELETE', '/[i:idFile]', function ($idFile) {
            $this->deleteFileAPI($idFile);
        });

    }

    /**
     * API call to upload a file. Will return custom Error code if problem occurs.
     * @Required_Privilege: Write
     */
    public function uploadFileAPI()
    {
        if(!$this->verified)
            $this->verifyPrivilege(Privilege::WRITE);

        $data = Files::uploadFiles($this->folder);

        $this->standardResponse($data);
    }

    /**
     * API call to Get a File.
     * @param $idFile
     * @Required_Privilege: Read
     */
    public function getFileAPI($idFile)
    {
        if(!$this->verified)
            $this->verifyPrivilege(Privilege::READ);

        $data = Files::getFileById($idFile);

        $this->standardResponse($data);
    }

    /**
     * API call to edit a given File.
     * @param $idFile
     * @throws Exception
     * @Required_Privilege: Write
     */
    public function editFileAPI($idFile)
    {
        if(!$this->verified)
            $this->verifyPrivilege(Privilege::WRITE);

        $data = Request::get()->getRequestBody();
        $category = isset($data['category'])? $data['category'] : NULL;
        $description = isset($data['description'])? $data['description'] : NULL;

        $file = Files::sqlFetch($idFile);

        $data = Files::edit($file, $category, $description);
        Files::translateFlags($file, $data);

        $this->standardResponse($data);
    }

    /**
     * API call to delete a specified File
     * @param $idFile
     * @throws Exception
     * @Required_Privilege: Delete
     */
    public function deleteFileAPI($idFile)
    {
        if(!$this->verified)
            $this->verifyPrivilege(Privilege::DELETE);

        Files::deleteById($idFile);

        $this->standardResponse(true);
    }


    /**********************************************   #HELPER#  **********************************************/


    /**
     * Helper function that sets the Response's template, data, response code, and errors.
     * @param array $data
     * @param int $responseCode
     * @param bool|false $error
     */
    private function standardResponse($data = [], $responseCode = 200, $error = false)
    {
        $responseData = [
            'data' => $data
        ];

        $response = $this->getResponseHandler();

        $response->addResponseMeta('fileCategories',$this->getFileCategories());

        $response->setResponse($responseData, $responseCode, false);
        if($error)
            $response->setErrorResponse(new Exception($error));
    }


    /*********************************************   #WORKFLOW#  *********************************************/


    /**
     * Select the folder for this FolderRoot
     * @param Folder $folder
     */
    public function attachFolder(Folder $folder) {
        $this->folder = $folder;
        $this->folder->setFolderRoot($this);
        if(!$this->folder->getIDComponent()) {
            $this->folder->setIDComponent($this->getId());
        }
    }

    /**
     * Create a new folder and attach it
     * @param int $idProject
     * @param string $folderName
     * @param array $flags
     * @return static
     */
    public function addNewFolder($idProject = 0, $folderName = '', $flags = ['locked'=>0]) {
        $f = Folder::create((int)$this->getId(), 0, $idProject, $folderName, $flags);
        $this->attachFolder($f);
        return $f;
    }

    /**
     * Getter function to get the folder attached to this FolderRoot
     * @return Folder
     */
    public function getFolder()
    {
        return $this->folder;
    }

    /**
     * Setter function to attach a folder to this FolderRoot
     * @param Folder $folder
     */
    public function setFolder($folder)
    {
        $this->folder = $folder;
    }

    /**
     * Getter function to get the File Categories available for this FolderRoot
     * @return mixed
     */
    public function getFileCategories()
    {
        return $this->fileCategories;
    }

    /**
     * Getter function to get the Max File Size for this FolderRoot
     * @return mixed
     */
    public function getFileMaxSize()
    {
        return $this->fileMaxSize;
    }

    /**
     * Getter function to get the Minimum File Size for this FolderRoot
     * @return FolderRoot
     */
    public function getFileMinSize()
    {
        return $this->fileMinSize;
    }

    /**
     * Getter function to get the Accepted File Types available for this FolderRoot
     * @return mixed
     */
    public function getAcceptedFileTypes()
    {
        return $this->acceptedFileTypes;
    }

    /**
     * Getter function to get the File Categories that have specialized File Types, Min, and Max size avaiable for this FolderRoot
     * @return mixed
     */
    public function getSpecialCategories()
    {
        return $this->specialCategories;
    }

    /**
     * Function to determine if the given file parts meet the requirements of this FolderRoot in terms of file size and type.
     * @param integer $size
     * @param string $type
     * @param string $category
     * @return array
     */
    //TODO Change format of return array and change error checking to an Exception throw
    public function parameterCheck($size, $type, $category)
    {
        $specialCategories = $this->specialCategories;
        $result = true;
        $message = "";
        if($category != "" && isset($specialCategories[$category])){
            $typeCheck = $specialCategories[$category]['acceptedFileTypes'];
            $maxCheck = $specialCategories[$category]['fileMaxSize'];
            $minCheck = $specialCategories[$category]['fileMinSize'];
        }
        else{
            $typeCheck = $this->acceptedFileTypes;
            $maxCheck = $this->fileMaxSize;
            $minCheck = $this->fileMinSize;
        }

        if(!in_array(strtolower($type),$typeCheck)){
            $result = false;
            $message = $message."Not an acceptable File Type.\n";
        }
        if($size > $maxCheck){
            $result = false;
            $message = $message."File larger than the max size of ".$maxCheck." bytes.\\n";
        }
        if($size < $minCheck){
            $result = false;
            $message = $message."File smaller than the minimum size of ".$minCheck."bytes.\\n";
        }

        return array("result" => $result, "message" => $message);
    }

    /**
     * Getter function to determine if the function has been verified, which is to say it has come here from another Module
     * @return mixed
     */
    public function getVerified()
    {
        return $this->verified;
    }

    /**
     * Setter function to determine if the function has been verified, which is to say it has come here from another Module
     * @param mixed $verified
     */
    public function setVerified($verified)
    {
        $this->verified = $verified;
    }



    /******************************************   #INITIALIZATION#  ******************************************/


    /**
     * Initialization Function that sets the File Categories, Max and Min Sizes, Accepted File Types,
     * and any categories that have exceptions within this FolderRoot for uploaded Files
     * @param array $acceptedFileTypes
     * @param integer $fileMaxSize
     * @param integer $fileMinSize
     * @param array $fileCategories
     * @param mixed $specialCategories
     */
    public function fileAcceptParameters (array $acceptedFileTypes, $fileMaxSize, $fileMinSize, array $fileCategories, array $specialCategories){
        $this->fileCategories = $fileCategories;
        $this->fileMaxSize = $fileMaxSize;
        $this->fileMinSize = $fileMinSize;
        $this->acceptedFileTypes = $acceptedFileTypes;
        $this->specialCategories = $specialCategories;
    }


    /*********************************************   #TRIGGERS#  *********************************************/


}