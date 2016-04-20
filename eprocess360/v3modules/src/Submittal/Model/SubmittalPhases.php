<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 8/19/2015
 * Time: 7:16 PM
 */

namespace eprocess360\v3modules\Submittal\Model;
use eprocess360\v3core\Files\Folder;
use eprocess360\v3core\Model\Folders;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Entry;
use eprocess360\v3core\Keydict\Entry\Bit;
use eprocess360\v3core\Keydict\Entry\Bits;
use eprocess360\v3core\Keydict\Entry\Bits8;
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
use eprocess360\v3core\Model\Files;
use eprocess360\v3modules\Submittal\Submittal;
use eprocess360\v3core\DB;
use eprocess360\v3modules\Task\Model\Tasks;

/**
 * @package eprocess360\v3modules\Submittal\Model
 */
class SubmittalPhases extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idSubmittalPhase', 'Submittal Phase ID'),
            FixedString64::build('title', 'Submittal Phase Title'),
            FixedString128::build('description','Submittal Phase Description'),
            IdInteger::build('idProject', 'Project ID')->joinsOn(Projects::model()),
            IdInteger::build('idParent', 'Parent SubmittalController ID'),
            IdInteger::build('idFolder', 'Folder ID')->joinsOn(Model\Folders::model()),
            IdInteger::build('idController', 'Controller ID'),
            UnsignedTinyInteger::build('depth', 'Controller Depth'),
            UnsignedSmallInteger::build('sequenceNumber', 'Sequence Number'),
            UnsignedSmallInteger::build('childNextSequenceNumber', 'Next Child Sequence Number'),
            Bits8::make(
                'status',
                Bit::build(0, 'isComplete', 'Complete'),
                Bit::build(1, 'limitOneIncomplete', 'Limit to One Incomplete')
            )
        )->setName('SubmittalPhases');
    }

    /**
     * @param int $title
     * @param int $description
     * @param int $idProject
     * @param int $idParent
     * @param int $idFolder
     * @param int $idController
     * @param int $depth
     * @param array $status
     * @return SubmittalPhases
     */
    public static function make($title = 0, $description = 0, $idProject = 0, $idParent = 0, $idFolder = 0, $idController = 0, $depth = 0, $status = [0]) {

        $rowData = ['title'=>$title,
                    'description'=>$description,
                    'idProject'=>$idProject,
                    'idParent'=>$idParent,
                    'idFolder'=>$idFolder,
                    'idController'=>$idController,
                    'depth'=>$depth,
                    'sequenceNumber'=>0,
                    'childNextSequenceNumber'=>1,
                    'status'=>$status];

        return self::SubmittalPhaseConstruct($rowData);
    }

    /**
     * @param array $rowData
     * @return SubmittalPhases
     */
    public static function SubmittalPhaseConstruct($rowData = []) {
        $instance = new self();
        $instance->data = self::keydict();
        $instance->data->acceptArray($rowData);
        return $instance;
    }

    /**
     * Instantiate a SubmittalPhase and insert into the DB
     * @param Submittal $root
     * @param $title
     * @param $description
     * @param $idParent
     * @param $status
     * @return SubmittalPhases
     */
    public static function create(Submittal $root, $title, $description, $idParent, $limitOneIncomplete) {
        $idProject = $root->getClosest('Project')->getIdProject();
        $idController = $root->getId();
        $status = ['isComplete'=>false,
                    'limitOneIncomplete'=>$limitOneIncomplete];

        if ($idParent) {
            $parent = self::sqlFetch($idParent);
            $depth = $parent->depth->get() - 1;
            $parentFolder = Folder::getByID($parent->idFolder->get());
            $folder = $parentFolder->createChild($idProject, $title);
        } else {
            $depth = $root->getRootDepth();
            $folder = $root->getFolderRoot()->addNewFolder($idProject, $title);
        }

        $idFolder = $folder->getIDFolder();

        $f = static::make($title, $description, $idProject, $idParent, $idFolder, $idController, $depth, $status);
        $f->insert();

        $result = $f->data->toArray();
        self::createLinks($result,$root);

        return $result;
    }

    /**
     * @param Submittal $root
     * @param $allowedDepth
     * @return array
     * @throws \Exception
     * @throws \eprocess360\v3core\Controller\ControllerException
     */
    public static function allReadableSubmittalPhases(Submittal $root, $allowedDepth)
    {
        $idController = (int)$root->getId();
        $idProject = (int)$root->getClosest('Project')->getIdProject();

        $new = array();
        $new[0] = array();
        $sql = "SELECT
                  sp.*,
                  GROUP_CONCAT(CONCAT(st.sequenceNumber,'#',CAST(st.status_0 AS UNSIGNED),'#',st.dateCreated)) AS submittalStatus,
                  st.dueDates,
                  st.outDates
                FROM SubmittalPhases sp
                  LEFT JOIN
                  (SELECT
                    s.idSubmittalPhase,
                    s.sequenceNumber,
                    s.dateCreated,
                    s.status_0,
                    GROUP_CONCAT(CONCAT(CAST(t.status_0 AS UNSIGNED),'#',t.dateDue)) AS dueDates,
                    GROUP_CONCAT(CONCAT(CAST(t.status_0 AS UNSIGNED),'#',t.dateCompleted)) AS outDates
                    FROM Submittals s
                      LEFT JOIN Tasks t ON t.idObjectComponent = {$idController} AND t.idObject = s.idSubmittal
                    GROUP BY s.idSubmittal
                  ) AS st ON st.idSubmittalPhase = sp.idSubmittalPhase
                WHERE
                    sp.idProject = {$idProject}
                    AND sp.idController = {$idController}
                    AND sp.depth <= {$allowedDepth}
                GROUP BY sp.idSubmittalPhase
                ORDER BY sp.idSubmittalPhase ASC";

        $results = DB::sql($sql);

        foreach ($results as $sqlResult) {
            $phase = self::keydict();
            $phase->wakeup($sqlResult);
            $resultArray = $phase->toArray();

            self::addSubmittalSummary($sqlResult, $resultArray);

            $resultDepth = $resultArray['depth'];
            if ($resultDepth >= $allowedDepth) {
                self::createLinks($resultArray,$root);
                $new[0][] = $resultArray;
            } else {
                $new[$resultArray['idParent']][] = $resultArray;
            }
        }
        $result = self::createTree($new, $new[0]);

        return $result;
    }

    /**
     * @param $list
     * @param $parent
     * @return array
     */
    private static function createTree(&$list, $parent){
        $tree = array();
        foreach ($parent as $k=>$l){
            if(isset($list[$l['idSubmittalPhase']])){
                $l['children'] = self::createTree($list, $list[$l['idSubmittalPhase']]);
            }
            $tree[] = $l;
        }
        return $tree;
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
     * @param $idSubmittalPhase
     * @return array
     */
    public static function getById(Submittal $root, $idSubmittalPhase)
    {
        $idController = (int)$root->getId();
        $idProject = (int)$root->getClosest('Project')->getIdProject();

        //TODO Optimize so that not two fetch's required by Controller, or pull out functionality.
        $parent = self::sqlFetch($idSubmittalPhase);
        $depth = (int)$parent->depth->get();

        $parentResult = $parent->toArray();
        self::createLinks($parentResult, $root);

        $allowedDepth = $depth - 1;
        $new = array();
        $new[0][] = $parentResult;
        $sql = "SELECT
                  sp.*,
                  GROUP_CONCAT(CONCAT(st.sequenceNumber,'#',CAST(st.status_0 AS UNSIGNED),'#',st.dateCreated)) AS submittalStatus,
                  st.dueDates,
                  st.outDates
                FROM SubmittalPhases sp
                  LEFT JOIN
                  (SELECT
                    s.idSubmittalPhase,
                    s.sequenceNumber,
                    s.dateCreated,
                    s.status_0,
                    GROUP_CONCAT(CONCAT(CAST(t.status_0 AS UNSIGNED),'#',t.dateDue)) AS dueDates,
                    GROUP_CONCAT(CONCAT(CAST(t.status_0 AS UNSIGNED),'#',t.dateCompleted)) AS outDates
                    FROM Submittals s
                      LEFT JOIN Tasks t ON t.idObjectComponent = {$idController} AND t.idObject = s.idSubmittal
                    GROUP BY s.idSubmittal
                  ) AS st ON st.idSubmittalPhase = sp.idSubmittalPhase
                WHERE
                    sp.idProject = {$idProject}
                    AND sp.idController = {$idController}
                    AND sp.depth <= {$allowedDepth}
                GROUP BY sp.idSubmittalPhase
                ORDER BY sp.idSubmittalPhase ASC";

        $results = DB::sql($sql);

        foreach ($results as $sqlResult){
            $phase = self::keydict();
            $phase->wakeup($sqlResult);
            $resultArray = $phase->toArray();

            self::addSubmittalSummary($sqlResult, $resultArray);

            if(isset($resultArray['idParent'])) {
                $new[$resultArray['idParent']][] = $resultArray;
            }
        }
        $tree = self::createTree($new, $new[0]);

        $result = $tree[0];

        return $result;
    }

    /**
     * @param Submittal $root
     * @param $idSubmittalPhase
     * @return bool
     * @throws \Exception
     */
    public static function deletePhase(Submittal $root, $idSubmittalPhase) {
        self::killChildren($idSubmittalPhase);
        Folders::deleteById(self::sqlFetch($idSubmittalPhase)->idFolder->get());
        self::deleteById($idSubmittalPhase);

        return true;
    }

    /**
     * @param $idSubmittalPhase
     * @throws \Exception
     */
    public static function killChildren($idSubmittalPhase) {
        foreach (self::each("SELECT * FROM `SubmittalPhases` WHERE `idParent` = '{$idSubmittalPhase}'")
                 as $sqlResult) {
            $id = $sqlResult->idSubmittalPhase->get();
            $depth = $sqlResult->depth->get();
            $folder = $sqlResult->idFolder->get();
            if(!$depth){
                self::killSubmittals($id);
            }
            else{
                self::killChildren($id);
            }
            Folders::deleteById($folder);
            self::deleteById($id);
        }
    }

    /**
     * @param $idSubmittalPhase
     * @throws \Exception
     */
    public static function killSubmittals($idSubmittalPhase) {
        foreach (Submittals::each("SELECT * FROM `Submittals` WHERE `idSubmittalPhase` = '{$idSubmittalPhase}'")
                 as $sqlResult) {
            $id = $sqlResult->idSubmittal->get();
            $folder = $sqlResult->idFolder->get();

            self::killFiles($folder);
            Folders::deleteById($folder);
            Submittals::deleteById($id);
        }
    }

    /**
     * @param $idFolder
     * @throws \Exception
     */
    public static function killFiles($idFolder) {
        foreach (Files::each("SELECT * FROM `Files` WHERE `idFolder` = '{$idFolder}'")
                 as $sqlResult) {
            $id = $sqlResult->idFile->get();

            Files::deleteById($id);
        }
    }

    /**
     * @param $idSubmittalPhase
     * @return int
     */
    public static function getChildNextSequenceNumber($idSubmittalPhase) {
        $parent = self::sqlFetch($idSubmittalPhase);
        $returnSequenceNumber = $parent->childNextSequenceNumber->get();
        $parent->childNextSequenceNumber->set(1+$returnSequenceNumber);
        $parent->update();
        return $returnSequenceNumber;
    }

    /**
     * @param $idSubmittalPhase
     * @return bool
     * @throws \Exception
     */
    public static function isBaseDepth($idSubmittalPhase) {
        try {
            $phase = self::sqlFetch($idSubmittalPhase);
            $depth = $phase->depth->get();
            return $depth == 0;
        }
        catch(\Exception $e){
            return false;
        }
    }

    /**
     * @param $phase
     * @param Submittal $root
     * @throws \Exception
     */
    public static function createLinks(&$phase, Submittal $root){
        global $pool;
        if(isset($phase['idSubmittalPhase'])){
            $ref = array();
            $tempRef = array();
            //TODO Quick fix because Links were giving //# , refactor when possible.
            $basePath = $pool->SysVar->siteUrl().$root->getPath();
            $basePath = preg_replace( '/\s*\d+$/', '', $basePath);
            $basePath = rtrim($basePath, '/');


            $tempRef = ['rel' => 'self', 'title' =>$phase['title'], 'href' => $basePath.'/'.$phase['idSubmittalPhase']];
            $ref[] = $tempRef;

            if($phase['idParent']) {
                $parent = self::sqlFetch($phase['idParent']);
                $tempRef = ['rel' => 'parent',
                            'title' => $parent->title->get(),
                            'href' => $basePath.'/'.$parent->idSubmittalPhase->get()];
                $ref[] = $tempRef;

                if($parent->idParent->get()){
                    $grandparent = self::sqlFetch($parent->idParent->get());
                    $tempRef = ['rel' => 'grandparent',
                                'title' => $grandparent->title->get(),
                                'href' => $basePath.'/'.$grandparent->idSubmittalPhase->get()];
                    $ref[] = $tempRef;
                }
                else{
                    $tempRef = ['rel' => 'grandparent', 'title' => "Base Submittals", 'href' => $basePath];
                    $ref[] = $tempRef;
                }
            }
            else{
                $tempRef = ['rel' => 'parent', 'title' =>"Base Submittals", 'href' => $basePath];
                $ref[] = $tempRef;
            }
            $phase['links'] = $ref;
        }
    }

    /**
     * Format submittal/review summary data from The Big SubmittalPhase Query
     * @param $row
     * @param $array
     */
    public static function addSubmittalSummary($row, &$array) {
        $returnSubNumber = NULL;
        $returnSubStatus = NULL;
        $returnSubCreated = NULL;
        $returnDue = NULL;
        $returnOut = NULL;

        if($row['submittalStatus'] !== NULL) {
            $keydict = Submittals::keydict();
            $statusStrings = explode(',', $row['submittalStatus']);
            rsort($statusStrings);

            foreach($statusStrings as $ss) {
                $split = explode('#', $ss);
                $temp = ['status_0'=>$split[1]];
                Keydict::wakeupAndTranslateStatus($keydict, $temp);

                if(!$temp['status']['isComplete']) {
                    $returnSubStatus = $temp['status'];
                    $returnSubNumber = (int)$split[0];
                    $returnSubCreated = $split[2];
                    break;
                }
                elseif($returnSubStatus === NULL) {
                    $returnSubStatus = $temp['status'];
                    $returnSubNumber = (int)$split[0];
                    $returnSubCreated = $split[2];
                }
            }
        }

        $array['activeSubmittalNumber'] = $returnSubNumber;
        $array['activeSubmittalStatus'] = $returnSubStatus;
        $array['activeSubmittalCreated'] = $returnSubCreated;

        if($row['dueDates'] !== NULL) {
            $keydict = Tasks::keydict();
            $dueStrings = explode(',', $row['dueDates']);
            $incomplete = [];

            foreach($dueStrings as $ds) {
                $split = explode('#', $ds);
                $temp = ['status_0'=>$split[0]];
                Keydict::wakeupAndTranslateStatus($keydict, $temp);

                if(!$temp['status']['isComplete']) {
                    $incomplete[] = $split[1];
                }
            }

            if(!empty($incomplete)) {
                $returnDue = min($incomplete);
            }
        }

        $array['firstReviewDue'] = $returnDue;

        if($row['outDates'] !== NULL) {
            $keydict = Tasks::keydict();
            $outStrings = explode(',', $row['dueDates']);
            $incomplete = [];

            foreach($outStrings as $ds) {
                $split = explode('#', $ds);
                $temp = ['status_0'=>$split[0]];
                Keydict::wakeupAndTranslateStatus($keydict, $temp);

                if($temp['status']['isComplete']) {
                    $incomplete[] = $split[1];
                }
            }

            if(!empty($incomplete)) {
                $returnOut = max($incomplete);
            }
        }

        $array['lastReviewOut'] = $returnOut;
    }

}