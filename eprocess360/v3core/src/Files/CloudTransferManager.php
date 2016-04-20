<?php

namespace eprocess360\v3core\Files;
use eprocess360\v3core\DB;


class CloudTransferManager
{
    private $file;

    /**
     * Get all Files which have not been moved to cloud storage
     * @return array
     * @throws \eprocess360\v3core\DB\Exception\MySQLException
     */
    public static function getLocalOnly() {
        $files = [];
        $rows = \_a(DB::sql("SELECT fi.* FROM Files fi INNER JOIN Folders fo ON fi.idFolder = fo.idFolder WHERE fi.cloudDatetime IS NULL"));

        foreach($rows as $r) {
            $folder = new Folder($r);
            $files[] = new File($r, $folder);
        }
        return $files;
    }

    /**
     * Get all Files which have a local copy but were copied to cloud storage
     * at least $minDays days ago.
     * @param $minDays
     * @return array
     * @throws DB\Exception\MySQLException
     */
    public static function getLocalExpired($minDays) {
        $files = [];
        $rows = \_a(DB::sql("SELECT fi.* FROM Files fi INNER JOIN Folders fo ON fi.idFolder = fo.idFolder WHERE fi.islocal = 1 AND DATEDIFF(NOW(), fi.cloudDatetime) >= ".(int)$minDays));

        foreach($rows as $r) {
            $folder = new Folder($r);
            $files[] = new File($r, $folder);
        }
        return $files;
    }
}