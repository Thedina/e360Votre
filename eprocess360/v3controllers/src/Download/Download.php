<?php
/**
 * Created by PhpStorm.
 * User: DanielMoreno
 * Date: 12/9/15
 * Time: 10:18 AM
 */

namespace eprocess360\v3controllers\Download;


use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Files\File;

/**
 * Class Download
 * @package eprocess360\v3controllers\Download
 */
class Download extends Controller
{
    use Router;


    /*********************************************   #ROUTING#  **********************************************/


    /**
     * Define the available routes for the module here. This function should not perform any other logic outside of
     * specifying available routes.
     */
    public function routes()
    {
        $this->routes->map('GET', '/[i:idFile]/[*:folderHash]', function ($idFile, $folderHash) {
            $this->getDownloadAPI($idFile, $folderHash);
        });
    }

    /**
     * Reads the file at the corresponding idFile and folderHash
     * TODO Complete revamp Download links to use the File's Hash (needs to be created as well)
     * @param $idFile
     * @param $folderHash
     */
    public function getDownloadAPI($idFile, $folderHash)
    {
        $file = File::getByID($idFile);

        if($file->getIsLocal()) {
            // Possibly will be changed to a redirect to a file directory?
            $filePath = $file->getPath();
            $fileName = $file->getFileName();
            $fileSize = $file->getSize();
            $fileNamePart = explode('.', $fileName);
            $fileExtension = end($fileNamePart);

            ob_start();

            header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
            header("Cache-Control: public"); // needed for i.e.
            header("Content-Type: application/" . $fileExtension);
            header("Content-Transfer-Encoding: Binary");
            header("Content-Length:" . $fileSize);
            header("Content-Disposition: attachment; filename=" . $fileName);

            ob_clean();
            flush();

            readfile($filePath);
            die();
        }
        else {
            // Redirect to cloud storage
            header("Location: ".$file->getRealDownloadURL());
        }
    }
}