<?php

namespace eprocess360\v3core;
use eprocess360\v3core\Files\Folder;
use eprocess360\v3core\Files\File;
use Exception;

/**
 * Class UploadManager
 * Provides functions for processing uploaded files
 * @package eprocess360\v3core
 */
class UploadManager
{
    private static $um;
    private $baseDir;

    /**
     * @param string $baseDir
     */
    public function __construct() {
    }

    /**
     * Construct an array associating $_FILES contents with POSTed file
     * description and type data.
     * @return array
     */
    public function collectFiles() {
        $newfiles = array();
        $i = 0;
        foreach($_FILES as $fieldname => $fieldvalue) {
            foreach($fieldvalue as $paramname => $paramvalue)
                foreach((array)$paramvalue as $index => $value) {
                    if(isset($_REQUEST['desc'][$index]))
                        $newfiles[$index]['desc'] = $_REQUEST['desc'][$index];
                    else
                        $newfiles[$index]['desc'] = '';
                    if(isset($_REQUEST['category'][$index]))
                        $newfiles[$index]['category'] = $_REQUEST['category'][$index];
                    else
                        $newfiles[$index]['category'] = '';
                    $newfiles[$index][$paramname] = $value;
                }
            $i++;
        }
        return $newfiles;
    }

    /**
     * Create the full local directory structure for the upload path if it
     * does not exist
     * @param $dirPath
     * @return mixed|string
     * @throws /Exception
     */
    public function makeLocalDir($dirPath) {
        $fullPath = "";
        $dirs = explode('/', $dirPath);

        foreach($dirs as $d) {
            if($d != '') {
                $fullPath .= '/'.$d;
            }
            if($fullPath && !file_exists($fullPath)) {
                mkdir($fullPath);
            }
        }
        if(!file_exists($fullPath)) {
            throw new Exception("UploadManager->makeLocalDir(): failed to create directory {$fullPath}.");
        }

        return $fullPath;
    }

    /**
     * For all uploaded files in $_FILES, copy to permanent local storage and
     * create uploads DB entry. Returns an array of File objects
     * @param Folder $folder
     * @return array
     * @throws \Exception
     */
    public function processUploads(Folder $folder) {
        global $pool;

        $date = date('Y-m-d H:i:s');

        $uploaded = [];

        $files = $this->collectFiles();

        if(!count($files)) {
            throw new Exception('UploadManager->processUploads(): empty or unprocessable file data.');
        }

        //TODO Add in the ability to account for errors with Multiple Files as well as Passing along a message for JSON
        foreach ($files as $fname => $f) 
        {
            $fileNamePart = explode('.', $f['name']);
            $fileExtension = end($fileNamePart);
            $paramCheck = $folder->getFolderRoot()->parameterCheck($f['size'], $fileExtension, $f['category']);
            if($paramCheck['result']) {
                $filePath = $this->makeLocalDir($folder->getPath()) . '/' . $f['name'];
                if(!strlen($f['desc'])) {
                    $f['desc'] = $f['name'];
                }
                if (move_uploaded_file($f['tmp_name'], $filePath)) {
                    $u = $folder->createFile($pool->User->getIdUser(), $f['name'], $f['desc'], $f['category'], $date, $f['size'], ['active'=>1, 'local'=>1]);
                    $uploaded[] = $u;
                } else {
                    throw new Exception('UploadManager->processUploads(): unable to save uploaded file.');
                }
            }
            else{
                throw new Exception('Unable to uploaded file. '.$paramCheck['message']);
            }
        }

        return $uploaded;
    }

    /**
     * Instantiate a singleton UploadManager. Load the base directory from
     * SysVar
     * @return UploadManager
     * @throws Exception
     */
    public static function getUploadManager() {
        global $pool;
        if(!is_object(self::$um)) {
            self::$um = new UploadManager($pool->SysVar->get('localUploadDirectory'));
        }
        return self::$um;
    }
}
?>