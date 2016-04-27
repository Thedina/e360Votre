<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 4/21/16
 * Time: 12:08 PM
 */

namespace eprocess360\v3controllers\Inspection\Model;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Entry\Bits8;
use eprocess360\v3core\Keydict\Entry\FixedString128;
use eprocess360\v3core\Keydict\Entry\FixedString256;
use eprocess360\v3core\Keydict\Entry\FixedString32;
use eprocess360\v3core\Keydict\Entry\Flag;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\JSONArrayFixed128;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\String;
use eprocess360\v3core\Keydict\Entry\TinyInteger;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;
use eprocess360\v3controllers\Group\Group;
use eprocess360\v3core\DB;
use eprocess360\v3core\Model\Controllers;
use eprocess360\v3core\Model\Roles;
use eprocess360\v3core\Model\UserRoles;
use eprocess360\v3core\Warden;
use eprocess360\v3core\Controller\Warden\Privilege;
use MongoDB\BSON\Timestamp;

use Exception;
/**
 * Class Limitation
 * @package eprocess360\v3controllers\inspection\Model
 */
class InspectionLimitations extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idInspLimitation', 'Inspection Limitation Id'),
            FixedString128::build('title', 'Controller ID'),
            FixedString128::build('description', 'Group Title'),
            IdInteger::build('status', 'Status'),
            IdInteger::build('createdUserId', 'Creater'),
            Keydict\Entry\Datetime::build('createdDate', 'Created time')
        )->setName('InspLimitations')->setLabel('InspLimitations');
    }

    
    public static function create($title, $description){
        
        $f = static::make($title, $description);
        $f->insert();

        $result = $f->data->toArray();
        return $result;
    }

    public static function allLimitations($readable = false)
    {
        global $pool;
        //find all Limitation
        $sql = "SELECT * From InspLimitations";
        
        if($readable){
            $sql = "SELECT * FROM `InspLimitations`";
        }

        $new = array();
        foreach (self::each($sql)
                 as $sqlResult){
            $resultArray = $sqlResult->toArray();

            if(isset($resultArray['idInspLimitation'])) {
                $new[] = $resultArray;
            }
        }

        return $new;
    }
    public static function deletelimitation($idlimitation) {
       
        $limitationsAssignedCat       = self::getAllLimitationAssignedCategories($idlimitation);
        $limitationsAssignedInspector = self::getAllLimitationAssignedInspectors($idlimitation);
        
        if(!empty($limitationsAssignedCat) || !empty($limitationsAssignedInspector))
            throw new Exception("Delete Error! Limitation assigned to categories / inspectors");
        
        self::deleteById($idlimitation);
    }
    
     public static function getAllLimitationAssignedCategories($idlimitation)
    {
        
        $sql = "SELECT * FROM 	InspCatLimitations " . 
               "WHERE 	InspCatLimitations.idInspLimitation = {$idlimitation}";

        $limitations = DB::sql($sql);
        
        $new = [];
        foreach($limitations as $limitation){
            if(isset($limitation['idInspLimitation'])) {
                $new[] = $limitation;
            }
        }
        
        return $new;
        
    }
    
    public static function getAllLimitationAssignedInspectors($idlimitation)
    {
        $sql = "SELECT * FROM InspectorLimitations " . 
               "WHERE InspectorLimitations.idInspLimitation = {$idlimitation}";

        $limitations = DB::sql($sql);
        
        $new = [];
        foreach($limitations as $limitation){
            if(isset($limitation['idInspLimitation'])) {
                $new[] = $limitation;
            }
        }
        
        return $new;
        
    }
    
    public static function make($title = "0", $description = "") {

        $rowData = ['title'=>$title,
            'description'=>$description];

        return self::InspectionCategoryConstruct($rowData);
    }

    public static function InspectionCategoryConstruct($rowData = []) {
        $instance = new self();
        $instance->data = self::keydict();
        $instance->data->acceptArray($rowData);
        return $instance;
    }



}