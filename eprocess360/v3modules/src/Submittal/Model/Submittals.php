<?php
/**
 * Created by PhpStorm.
 * User: Daniel
 * Date: 11/11/2015
 * Time: 7:43 AM
 */

namespace eprocess360\v3modules\Submittal\Model;
use eprocess360\v3core\DB;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Entry;
use eprocess360\v3core\Keydict\Entry\Bit;
use eprocess360\v3core\Keydict\Entry\Bits;
use eprocess360\v3core\Keydict\Entry\Bits8;
use eprocess360\v3core\Keydict\Entry\Datetime;
use eprocess360\v3core\Keydict\Entry\FixedString128;
use eprocess360\v3core\Keydict\Entry\FixedString256;
use eprocess360\v3core\Keydict\Entry\FixedString32;
use eprocess360\v3core\Keydict\Entry\FixedString64;
use eprocess360\v3core\Keydict\Entry\Flag;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\IdTinyInt;
use eprocess360\v3core\Keydict\Entry\Integer;
use eprocess360\v3core\Keydict\Entry\JSONArrayFixed128;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\TinyInteger;
use eprocess360\v3core\Keydict\Entry\UnsignedSmallInteger;
use eprocess360\v3core\Keydict\Entry\UnsignedTinyInteger;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;
use eprocess360\v3core\Model\Projects;
use eprocess360\v3modules\Review\Model\Reviews;
use eprocess360\v3modules\Submittal\Submittal;
use eprocess360\v3core\Files\Folder;
use eprocess360\v3core\Model\Files;
use eprocess360\v3core\Model\Folders;
use eprocess360\v3core\Files\File;
use eprocess360\v3modules\Task\Model\Tasks;

/**
 * @package eprocess360\v3modules\Submittal\Model
 */
class Submittals extends Model
{

    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idSubmittal', 'Submittal  ID'),
            UnsignedTinyInteger::build('sequenceNumber', 'Submittal Sequence Number'),
            FixedString64::build('title', 'Submittal Title'),
            FixedString128::build('description','Submittal Description'),
            IdInteger::build('idUser', 'User ID')->joinsOn(Model\Users::model()),
            IdInteger::build('idFolder', 'Folder ID')->joinsOn(Model\Folders::model()),
            IdInteger::build('idSubmittalPhase', 'SubmittalPhase ID')->joinsOn(SubmittalPhases::model()),
            Datetime::build('dateCreated', 'Date Created'),
            Datetime::build('dateCompleted', 'Date Completed'),
            Bits8::make('status',
                Bit::build(0, 'isComplete', 'Complete'),
                Bit::build(1, 'hasReview', 'Has Review'),
                Bit::build(2, 'reviewsAccepted', 'Reviews Accepted'),
                Bit::build(3, 'reviewsCompleted', 'Reviews Completed')
            )
        )->setName('Submittals');
    }

    /**
     * @param int $sequenceNumber
     * @param string $title
     * @param string $description
     * @param int $idUser
     * @param int $idFolder
     * @param int $idSubmittalPhase
     * @param null $dateCreated
     * @param null $dateCompleted
     * @param array $status
     * @return Submittals
     */
    public static function make($sequenceNumber = 0, $title = '', $description = '', $idUser = 0, $idFolder = 0, $idSubmittalPhase = 0, $dateCreated = NULL, $dateCompleted = NULL, $status = [0])
    {
        $rowData = ['sequenceNumber'=>$sequenceNumber,
                    'title'=>$title,
                    'description'=>$description,
                    'idUser'=>$idUser,
                    'idFolder'=>$idFolder,
                    'idSubmittalPhase'=>$idSubmittalPhase,
                    'dateCreated'=>$dateCreated,
                    'dateCompleted'=>$dateCompleted,
                    'status'=>$status];

        return self::SubmittalConstruct($rowData);
    }

    /**
     * @param array $rowData
     * @return Submittals
     */
    public static function SubmittalConstruct($rowData = []) {
        $instance = new self();
        $instance->data = self::keydict();
        $instance->data->acceptArray($rowData);

        return $instance;
    }

    /**
     * @param Submittal $root
     * @param $idSubmittalPhase
     * @param string $title
     * @param string $description
     * @return array
     * @throws \Exception
     * @throws \eprocess360\v3core\Controller\ControllerException
     */
    public static function create(Submittal $root, $idSubmittalPhase, $title = null, $description = null)
    {
        global $pool;

        $parent = SubmittalPhases::sqlFetch($idSubmittalPhase);

        assert($parent->depth->get() == 0);

        $parentFolder = Folder::getByID($parent->idFolder->get());
        $idProject = $root->getClosest('Project')->getIdProject();

        $sequenceNumber = SubmittalPhases::getChildNextSequenceNumber($idSubmittalPhase);
        $folder = $parentFolder->createChild($idProject, $sequenceNumber);

        if($title === NULL)
            $title = "Submittal";

        if($description === NULL)
            $description = "";

        $idUser = $pool->User->getIdUser();
        $idFolder = $folder->getIDFolder();
        $dateCreated = Datetime::timestamps();
        $dateCompleted = NULL;
        $status = [
            'isComplete'=>false,
            'hasReview'=>false,
            'reviewsAccepted'=>false,
            'reviewsCompleted'=>false
        ];
        $f = static::make($sequenceNumber, $title, $description, $idUser, $idFolder, $idSubmittalPhase, $dateCreated, $dateCompleted, $status);
        $f->insert();

        $result = $f->data->toArray();
        self::translateStatus($f->data, $result);

        return $result;
    }

    /**
     * @param Keydict $keydict
     * @param $array
     */
    public static function translateStatus(Keydict $keydict, &$array)
    {
        unset($array['status_0']);
        /** @var Entry $field */
        foreach ($keydict->status->getFields() as $field) {
            $array['status'][$field->getName()] = $field->get();
        }
    }

    /**
     * @param Submittal $root
     * @param $idSubmittal
     * @return array
     * @throws \Exception
     */
    public static function getById(Submittal $root, $idSubmittal)
    {
        $parent = self::sqlFetch($idSubmittal);

        $idFolder = $parent->idFolder->get();
        $parentResult = $parent->toArray();

        $new = array();
        foreach (Files::each("SELECT * FROM `Files` WHERE `idFolder` = '{$idFolder}' ORDER BY `idFile` ASC")
                 as $sqlResult){
            $resultArray = $sqlResult->toArray();
            Files::downloadUrl($resultArray);

            array_push($new, $resultArray);
        }
        if($new !== []){
            $parentResult['files'] = $new;
        }

        $idController = (int)$root->getID();
        $sql = "SELECT *, CAST(r.status_0 AS UNSIGNED) AS reviewStatus, CAST(t.status_0 AS UNSIGNED) AS taskStatus
                    FROM Reviews r
                    LEFT JOIN Tasks t ON r.idTask = t.idTask
                    WHERE idObjectComponent = {$idController}
                      AND idObject = {$idSubmittal}";
        $reviews = DB::sql($sql);

        foreach ($reviews as &$review) {
            $review['status_0'] = $review['reviewStatus'];
            unset($review['reviewStatus']);
            Keydict::wakeupAndTranslateStatus(self::keydict(), $review);

            $review['status_0'] = $review['taskStatus'];
            unset($review['taskStatus']);
            Keydict::wakeupAndTranslateStatus(Tasks::keydict(), $review);
        }

        $parentResult['reviews'] = $reviews;

        $result = $parentResult;

        return $result;
    }

    /**
     * @param Submittal $root
     * @param $idSubmittalPhase
     * @return array|null
     */
    public static function getByPhaseId(Submittal $root, $idSubmittalPhase)
    {
        $parent = SubmittalPhases::sqlFetch($idSubmittalPhase);

        $parentResult = $parent->toArray();
        SubmittalPhases::createLinks($parentResult, $root);

        $new = array();
        foreach (self::each("SELECT * FROM `Submittals` WHERE `idSubmittalPhase` = '{$idSubmittalPhase}' ORDER BY `idSubmittal` DESC")
                 as $sqlResult){
            $resultArray = $sqlResult->toArray();

            $idFolder = $resultArray['idFolder'];

            $files = array();
            foreach (Files::each("SELECT * FROM `Files` WHERE `idFolder` = '{$idFolder}' ORDER BY `idFile` ASC")
                     as $sqlFiles){
                $resultFilesArray = $sqlFiles->toArray();
                Files::downloadUrl($resultFilesArray);

                array_push($files, $resultFilesArray);
            }
            if($files !== []){
                $resultArray['files'] = $files;
            }

            $idController = (int)$root->getID();
            $idSubmittal = (int)$sqlResult->idSubmittal->get();
            $sql = "SELECT *, CAST(r.status_0 AS UNSIGNED) AS reviewStatus, CAST(t.status_0 AS UNSIGNED) AS taskStatus
                    FROM Reviews r
                    LEFT JOIN Tasks t ON r.idTask = t.idTask
                    WHERE idObjectComponent = {$idController}
                      AND idObject = {$idSubmittal}";
            $reviews = DB::sql($sql);

            foreach ($reviews as &$review) {
                $review['status_0'] = $review['reviewStatus'];
                unset($review['reviewStatus']);
                Keydict::wakeupAndTranslateStatus(self::keydict(), $review);

                $review['status_0'] = $review['taskStatus'];
                unset($review['taskStatus']);
                Keydict::wakeupAndTranslateStatus(Tasks::keydict(), $review);
            }

            $resultArray['reviews'] = $reviews;

            if(count($reviews)) {
                $resultArray['reviewers'] = $root->getReviews()->getReviewers();
            }

            array_push($new, $resultArray);
        }
        if($new !== []){
            $parentResult['submittals'] = $new;
        }

        $result = $parentResult;

        return $result;
    }

    /**
     * @param $idSubmittal
     * @return mixed
     * @throws \Exception
     */
    public static function isComplete($idSubmittal)
    {
        try {
            $data = self::sqlFetch($idSubmittal);
            return $data->status->isComplete->get();
        }
        catch(\Exception $e){
            //$result = NULL;
            throw $e;
        }
    }

    /**
     * @param Submittal $root
     * @param $idSubmittal
     * @return bool
     */
    public static function deleteSubmittal(Submittal $root, $idSubmittal){
        $sqlResult = self::sqlFetch($idSubmittal);
        $id = $sqlResult->idSubmittal->get();
        $folder = $sqlResult->idFolder->get();

        SubmittalPhases::killFiles($folder);
        Folders::deleteById($folder);
        Submittals::deleteById($id);
        return true;
    }

    /**
     * @param $idSubmittalPhase
     * @return Table
     * @throws \Exception
     */
    public static function getTopSubmittal($idSubmittalPhase)
    {
        $sql = "SELECT * FROM `Submittals` WHERE `idSubmittalPhase` = '{$idSubmittalPhase}' ORDER BY `idSubmittal` DESC";

        $submittal = DB::sql($sql)[0];

        return self::keydict()->wakeup($submittal);
    }
}