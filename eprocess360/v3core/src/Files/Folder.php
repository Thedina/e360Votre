<?php

namespace eprocess360\v3core\Files;
use eprocess360\v3core\Controller\Children;
use eprocess360\v3core\Controller\Project;
use eprocess360\v3core\Model;
use eprocess360\v3core\DB;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3modules\FolderRoot\FolderRoot;
use eprocess360\v3core\UploadManager;

/**
 * Class Folder
 * @package eprocess360\v3core\Files
 */
class Folder
{
    const HASH_SALT = '%&31gX0N';
    const FLAG_LOCKED = 0b00000001;

    /**
     * @var Table $data
     */
    private $data;

    /**
     * @var Folder $parentFolder
     */
    private $parentFolder = NULL;

    /**
     * @var FolderRoot $folderRoot
     */
    private $folderRoot;

    /**
     * Set parent pointers for all folders for this root and project. Unused.
     * @param $idController
     * @param $idProject
     */
    private function traverseFolders($idController, $idProject) {
        $folders = [];
        $rows = DB::sql("SELECT * FROM Folders WHERE idController = ".(int)$idController." AND idProject = ".(int)$idProject);

        if(!empty($rows)) {
            foreach ($rows as $r) {
                $folders[] = new Folder($r);
            }

            foreach($folders as $f) {
                /**
                 * @var Folder $f
                 */
                foreach($folders as $f2) {
                    /**
                     * @var Folder $f2
                     */
                    $f2IDParent = $f2->getIDParent();
                    if($f2IDParent == $f->getIDFolder()) {
                        $f2->setParentFolder($f);
                    }
                }
            }
        }
    }

    /**
     * Convert a string into a modified base64 encoding, suitable for use in
     * file names and URLs
     * @param $str
     * @return string
     */
    private static function fileableBase64($str) {
        return str_replace(['/', '+','='], ['&','-','0'], base64_encode($str));
    }

    /**
     * Generate a randomized 32-character string, suitable for use in file
     * names and URLs
     * @return string
     */
    private static function makeHash() {
        $hash = sha1(microtime().self::HASH_SALT, true).openssl_random_pseudo_bytes(4);
        return static::fileableBase64($hash);
    }

    /**
     * @param array $rowData
     */
    public function __construct($rowData = []) {
        $this->data = Model\Folders::keydict();
        $this->data->acceptArray($rowData);
    }

    /**
     * Return the local file path for this folder
     * @return string
     */
    public function getPath() {
        global $pool;
        return APP_PATH.$pool->SysVar->get('localUploadDirectory').'/'.$this->data->idFolder->get().'-'.$this->data->hash->get();
    }

    /**
     * Load data from a Folder entry in the DB
     * @param $idFolder
     */
    public function load($idFolder) {
        $rows = DB::sql("SELECT * FROM Folders WHERE idFolder = ".(int)$idFolder);
        if(!empty($rows)) {
            $this->data->wakeup($rows[0]);
        }
    }

    /**
     * Insert a DB entry for this folder
     */
    public function insert() {
        $this->data->insert();
    }

    /**
     * Update the DB entry for this folder
     */
    public function update() {
        $this->data->update();
    }

    /**
     * @return FolderRoot
     */
    public function getFolderRoot()
    {
        if($this->folderRoot === NULL){
            /** @var Children $controller */
            $controller = Project::getProjectControllerByIdProject($this->data->idProject->get());
            $controller->buildChildren();
            $folderRoot = $controller->getChildById($this->data->idController->get());
            $this->folderRoot  = $folderRoot;
        }
        return $this->folderRoot;
    }

    /**
     * @param FolderRoot $folderRoot
     */
    public function setFolderRoot($folderRoot)
    {
        $this->folderRoot = $folderRoot;
    }

    /**
     * @return int
     */
    public function getIDFolder() {
        return (int)$this->data->idFolder->get();
    }

    /**
     * @return int
     */
    public function getIDProject() {
        return (int)$this->data->idProject->get();
    }


    /**
     * @return int
     */
    public function getIDComponent() {
        return (int)$this->data->idController->get();
    }

    /**
     * @param $idController
     * @throws Keydict\Exception\InvalidValueException
     */
    public function setIDComponent($idController) {
        $this->data->idController->set((int)$idController);
    }

    /**
     * @return int
     */
    public function getIDParent() {
        return (int)$this->data->idParent->get();
    }

    /**
     * @param $idFolder
     * @throws Keydict\Exception\InvalidValueException
     */
    public function setIDParent($idFolder) {
        $this->data->idParent->set((int)$idFolder);
    }

    /**
     * @return Folder
     */
    public function getParentFolder() {
        return $this->parentFolder;
    }

    /**
     * @param Folder $folder
     */
    public function setParentFolder(Folder $folder) {
        $this->parentFolder = $folder;
    }

    /**
     * Instantiate a File as a child of this Folder
     * @param int $idUser
     * @param string $fileName
     * @param string $description
     * @param string $category
     * @param string $dateCreated
     * @param int $size
     * @param int $flags
     * @return static
     */
    public function buildFile($idUser = 0, $fileName = "", $description = "", $category = "", $dateCreated = "", $size = 0, $flags = ['active'=>0, 'local'=>0]) {
        return File::build($this, $idUser, $fileName, $description, $category, $dateCreated, $size, $flags);
    }

    /**
     * Instantiate a File as a child of this Folder and insert into the DB
     * @param int $idUser
     * @param string $fileName
     * @param string $description
     * @param string $category
     * @param string $dateCreated
     * @param int $size
     * @param int $flags
     * @return File
     */
    public function createFile($idUser = 0, $fileName = "", $description = "", $category = "", $dateCreated = "", $size = 0, $flags = ['active'=>0, 'local'=>0]) {
        return File::create($this, $idUser, $fileName, $description, $category, $dateCreated, $size, $flags);
    }

    /**
     * Instantiate a Folder as a child of this Folder
     * @param int $idProject
     * @param string $folderName
     * @param int $flags
     * @return Folder
     */
    public function buildChild($idProject = 0, $folderName = '', $flags = ['locked'=>0]) {
        return static::build($this->getIDComponent(), $this->getIDFolder(), $idProject, $folderName, $flags);
    }

    /**
     * Instantiate a Folder as a child of this Folder and insert into th DB
     * @param int $idProject
     * @param string $folderName
     * @param int $flags
     * @return Folder
     */
    public function createChild($idProject = 0, $folderName = '', $flags = ['locked'=>0]) {
        $f = static::build($this->getIDComponent(), $this->getIDFolder(), $idProject, $folderName, $flags);
        $f->setFolderRoot($this->getFolderRoot());
        $f->insert();
        return $f;
    }

    /**
     * Instantiate a Folder
     * @param int $idController
     * @param int $idParent
     * @param int $idProject
     * @param string $folderName
     * @param int $flags
     * @return static
     */
    public static function build($idController = 0, $idParent = 0, $idProject = 0, $folderName = '', $flags = ['locked'=>0]) {
        $hash = static::makeHash();
        $rowData = ['idController'=>$idController, 'idParent'=>$idParent, 'idProject'=>$idProject, 'hash'=>$hash, 'folderName'=>$folderName, 'flags'=>$flags];
        return new static($rowData);
    }

    /**
     * Instantiate a Folder and insert into the DB
     * @param int $idController
     * @param int $idParent
     * @param int $idProject
     * @param string $folderName
     * @param int $flags
     * @return static
     */
    public static function create($idController = 0, $idParent = 0, $idProject = 0, $folderName = '', $flags = ['locked'=>0]) {
        $f = static::build($idController, $idParent, $idProject, $folderName, $flags);
        $f->insert();
        return $f;
    }

    /**
     * Retrieve a saved Folder by ID
     * @param $idFolder
     * @return Folder
     */
    public static function getByID($idFolder) {
        $f = static::build();
        $f->load($idFolder);
        return $f;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function acceptUploads(){
        try {
            $uc = UploadManager::getUploadManager();
            return $uc->processUploads($this);
        }
        catch(\Exception $e) {
            throw $e;
        }
    }

}