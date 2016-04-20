<?php

namespace eprocess360\v3core\Files;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Logger;
use eprocess360\v3core\Model;
use eprocess360\v3core\DB;
use eprocess360\v3core\SysVar;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\CloudStorage;

/**
 * Class File
 * @package eprocess360\v3core\Files
 */
class File
{
    const FLAG_ACTIVE = 0b00000001;
    const FLAG_LOCAL = 0b00000010;

    protected $idFile;
    /**
     * @var Table $data
     */
    protected $data;

    /**
     * @var Folder $folder
     */
    protected $folder;

    /**
     * File constructor.
     * @param array $rowData
     * @param Folder|NULL $folder
     */
    public function __construct($rowData = [], Folder $folder = NULL)
    {
        $this->data = Model\Files::keydict();
        $this->data->acceptArray($rowData);
        if ($folder !== NULL) {
            $this->folder = $folder;
        }
        elseif($this->data->idFolder->get()) {
            $this->folder = Folder::getByID($this->data->idFolder->get());
        }
    }

    /**
     * Insert a DB entry for this File
     */
    public function insert()
    {
        $this->data->insert();
    }

    /**
     * Update the DB entry for this file
     */
    public function update()
    {
        $this->data->update();
    }

    /**
     * Load data for this File from the DB
     * @param $idFile
     * @throws \Exception
     */
    public function load($idFile)
    {
        $rows = DB::sql("SELECT *, CAST(fi.flags_0 AS UNSIGNED) AS fileFlags, CAST(fo.flags_0 AS UNSIGNED) AS folderFlags FROM Files fi INNER JOIN Folders fo ON fi.idFolder = fo.idFolder WHERE fi.idFile = " . (int)$idFile);
        if (!empty($rows)) {
            $raw = $rows[0];
            $raw['flags_0'] = $raw['fileFlags'];
            $this->data->wakeup($raw);
            $raw['flags_0'] = $raw['folderFlags'];
            Keydict::wakeupAndTranslateFlags(Model\Folders::keydict(), $raw);
            $this->folder = new Folder($raw);
        }
    }

    /**
     * @return int
     */
    public function getIDFile()
    {
        return (int)$this->data->idFile->get();
    }

    /**
     * Get the full path to the file starting with the base
     * File directory
     * @return string
     */
    public function getPath()
    {
        return $this->folder->getPath() . '/' . $this->data->fileName->get();
    }

    /**
     * Get the true download URL for this File, even if has been offloaded
     * to cloud storage.
     * @return string
     * @throws \Exception
     */
    public function getRealDownloadURL()
    {
        global $pool;

        if ($this->data->cloudDatetime->get() !== NULL) {
            $cs = CloudStorage::getCloudStorage();
            $fullpath = $this->getPath();
            $fullpath = substr($fullpath, strpos($fullpath, 'uploads'));
            return $cs->getURL($fullpath);
        } else {
            return $pool->SysVar->get('publicDownloadDirectory') . $this->getPath();
        }
    }

    /**
     * Get the public/permanent download URL for this file
     * @return string
     * @throws \Exception
     */
    public function getDownloadURL() {
        global $pool;

        return $pool->SysVar->get('siteUrl').'/download/'.$this->getIDFile().'/'. $this->data->fileName->get();
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return (string)$this->data->fileName->get();
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return (string)$this->data->description->get();
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return (int)$this->data->size->get();
    }

    /**
     * @return string
     */
    public function getDateCreated()
    {
        return (string)$this->data->dateCreated->get();
    }

    /**
     * Set the cloud File timestamp for this File in the DB.
     * @param string|null $ts
     * @throws \Exception
     */
    public function setCloudDatetime($ts)
    {
        DB::sql("UPDATE Files SET cloudDatetime = " . ($ts !== NULL ? "'" . DB::cleanse($ts) . "'" : "NULL") . " WHERE idFile = " . (int)$this->data->idFile->get());
        $this->data->cloudDatetime->set($ts);
    }

    /**
     * Set the islocal flag for this File in the DB.
     * @param $val
     * @throws \Exception
     */
    public function setIsLocal($val)
    {
        if ($val) {
            DB::sql("UPDATE Files SET flags_0 = flags_0 | " . self::FLAG_LOCAL . " WHERE idFile = " . (int)$this->getIDFile());
        } else {
            DB::sql("UPDATE Files SET flags_0 = flags_0 & ~" . self::FLAG_LOCAL . " WHERE idFile = " . (int)$this->getIDFile());
        }

        $this->data->flags->local->set((int)$val);
    }

    /**
     * Get the isLocal flag for this File
     * @return bool
     */
    public function getIsLocal(){
        return (bool)$this->data->flags->local->get();
    }

    /**
     * @return Table
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param bool|false $checkAPI
     * @return bool
     */
    public function existsOnCloud($checkAPI = false)
    {
        $flags = ($this->data->cloudDatetime->get() !== NULL AND !$this->data->flags->local->get());
        if ($flags) {
            if ($checkAPI) {
                $cs = CloudStorage::getCloudStorage();
                return $cs->exists(ltrim($this->getPath(), '/'));
            } else {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $idFile
     * @return static
     */
    public static function getByID($idFile)
    {
        $f = new static();
        $f->load($idFile);
        return $f;
    }

    /**
     * Return an File for each id in $ids
     * @param array $ids
     * @return array
     */
    public static function getMultiple(array $ids)
    {
        $files = [];
        $ids = array_map(function ($val) {
            return (int)$val;
        }, $ids);

        $sql = "SELECT *, CAST(fi.flags_0 AS UNSIGNED) AS fileFlags, CAST(fo.flags_0 AS UNSIGNED) AS folderFlags FROM Files fi INNER JOIN Folders fo ON fi.idFolder = fo.idFolder WHERE fi.idFile IN (" . implode(',', $ids) . ")";
        $rows = DB::sql($sql);

        if(!empty($rows)) {
            foreach ($rows as $r) {
                $r['flags_0'] = $r['folderFlags'];
                Keydict::wakeupAndTranslateFlags(Model\Folders::keydict(), $r);
                $folder = new Folder($r);
                $r['flags_0'] = $r['fileFlags'];
                Keydict::wakeupAndTranslateFlags(Model\Files::keydict(), $r);
                $files[] = new static($r, $folder);
            }
        }
        return $files;
    }

    /**
     * Return an array containing Files for Files in the DB matching *all*
     * of the filters [:col=>['op'=>:operator, 'val'=>:value]]
     * @param $filters
     * @return array
     * @throws \Exception
     */
    public static function getByFilter($filters)
    {
        $ops = ['=', '!=', '<', '>', '<=', '>=', 'IS', 'IS NOT', 'IN', 'LIKE'];

        $files = [];
        $where_clauses = [];

        foreach ($filters as $col => $f) {
            $op = $f['op'];
            $val = DB::cleanse($f['val']);
            if (in_array($op, $ops)) {
                $where_clauses[] = "fi.{$col} {$op} '{$val}' ";
            }
        }
        $sql = "SELECT *, CAST(fi.flags_0 AS UNSIGNED) AS fileFlags, CAST(fo.flags_0 AS UNSIGNED) AS folderFlags FROM Files fi INNER JOIN Folders fo ON fi.idFolder = fo.idFolder " . implode(' AND ', $where_clauses);
        $rows = DB::sql($sql);

        if(!empty($rows)) {
            foreach ($rows as $r) {
                $r['flags_0'] = $r['folderFlags'];
                Keydict::wakeupAndTranslateFlags(Model\Folders::keydict(), $r);
                $folder = new Folder($r);
                $r['flags_0'] = $r['fileFlags'];
                Keydict::wakeupAndTranslateFlags(Model\Files::keydict(), $r);
                $files[] = new static($r, $folder);
            }
        }
        return $files;
    }

    /**
     * Return an array containing Files for all Files in the DB
     * @return array
     * @throws \Exception
     */
    public static function getAll()
    {
        $files = [];
        $rows = DB::sql("SELECT *, CAST(fi.flags_0 AS UNSIGNED) AS fileFlags, CAST(fo.flags_0 AS UNSIGNED) AS folderFlags FROM Files fi INNER JOIN Folders fo ON fi.idFolder = fo.idFolder");

        if (!empty($rows)) {
            foreach ($rows as $r) {
                $r['flags_0'] = $r['folderFlags'];
                Keydict::wakeupAndTranslateFlags(Model\Folders::keydict(), $r);
                $folder = new Folder($r);
                $r['flags_0'] = $r['fileFlags'];
                Keydict::wakeupAndTranslateFlags(Model\Files::keydict(), $r);
                $files[] = new static($r, $folder);
            }
        }
        return $files;
    }

    /**
     * Instantiate a File
     * @param Folder $folder
     * @param $idUser
     * @param $fileName
     * @param $description
     * @param $dateCreated
     * @param $size
     * @param $flags
     * @return static
     */
    public static function build(Folder $folder, $idUser = 0, $fileName = "", $description = "", $category = "", $dateCreated = "", $size = 0, $flags = ['active'=>0, 'local'=>0]) {
        $rowData = ['idFolder'=>$folder->getIDFolder(), 'idUser'=>$idUser, 'fileName'=>$fileName, 'description'=>$description, 'category'=>$category, 'dateCreated'=>$dateCreated, 'size'=>$size, 'flags'=>$flags];
        return new static($rowData, $folder);
    }

    /**
     * Instantiate a file and insert into the DB
     * @param Folder $folder
     * @param int $idUser
     * @param string $fileName
     * @param string $description
     * @param string $dateCreated
     * @param int $size
     * @param int $flags
     * @return File
     */
    public static function create(Folder $folder, $idUser = 0, $fileName = "", $description = "", $category = "", $dateCreated = "", $size = 0, $flags = ['active'=>0, 'local'=>0]) {
        $f = static::build($folder, $idUser, $fileName, $description, $category, $dateCreated, $size, $flags);
        $f->insert();
        return $f;
    }


    /**
     * Get all Files which have not been moved to cloud storage
     * @return array
     * @throws \Exception
     */
    public static function getLocalOnly() {
        $files = [];
        $rows = DB::sql("SELECT *, CAST(fi.flags_0 AS UNSIGNED) AS fileFlags, CAST(fo.flags_0 AS UNSIGNED) AS folderFlags FROM Files fi INNER JOIN Folders fo ON fi.idFolder = fo.idFolder WHERE fi.cloudDatetime IS NULL");
        if(!empty($rows)) {
            foreach ($rows as $r) {
                $r['flags_0'] = $r['folderFlags'];
                Keydict::wakeupAndTranslateFlags(Model\Folders::keydict(), $r);
                $folder = new Folder($r);
                $r['flags_0'] = $r['fileFlags'];
                Keydict::wakeupAndTranslateFlags(Model\Files::keydict(), $r);
                $files[] = new static($r, $folder);
            }
        }
        return $files;
    }

    /**
     * Get all Files which have a local copy but were copied to cloud storage
     * at least $min_days days ago.
     * @param $min_days
     * @return array
     * @throws \Exception
     */
    public static function getLocalExpired($min_days) {
        $files = [];
        $rows = DB::sql("SELECT *, CAST(fi.flags_0 AS UNSIGNED) AS fileFlags, CAST(fo.flags_0 AS UNSIGNED) AS folderFlags FROM Files fi INNER JOIN Folders fo ON fi.idFolder = fo.idFolder WHERE fi.flags_0 & ".File::FLAG_LOCAL." AND DATEDIFF(NOW(), fi.cloudDatetime) >= ".(int)$min_days);

        if(!empty($rows)) {
            foreach ($rows as $r) {
                $r['flags_0'] = $r['folderFlags'];
                Keydict::wakeupAndTranslateFlags(Model\Folders::keydict(), $r);
                $folder = new Folder($r);
                $r['flags_0'] = $r['fileFlags'];
                Keydict::wakeupAndTranslateFlags(Model\Files::keydict(), $r);
                $files[] = new static($r, $folder);
            }
        }
        return $files;
    }
}