<?php

namespace eprocess360\v3modules\Inspection\Model;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Entry\FixedString128;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;
use eprocess360\v3core\DB;

use Exception;
/**
 * Class Limitation
 * @package eprocess360\v3controllers\Inspection\Model
 */
class InspectionLimitations extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idInspLimitation', 'Limitation Id'),
            FixedString128::build('title', 'Title'),
            FixedString128::build('description', 'Description'),
            IdInteger::build('status', 'Status'),
            IdInteger::build('createdUserId', 'Creater'),
            Keydict\Entry\Datetime::build('createdDate', 'Created time')
        )->setName('InspLimitations')->setLabel('InspLimitations');
    }

    /**
     * Insert limitation to database
     * @param $title
     * @param $description
     * @return array
     */
    public static function create($title, $description)
    {    
        $f = static::make($title, $description);
        $f->insert();

        $result = $f->data->toArray();
        return $result;
    }

    /**
     * Get all limitations from database
     * @return array
     */
    public static function allLimitations()
    {
        //find all Limitation
        $sql = "SELECT * From InspLimitations";
        
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
    
    /**
     * Delete limitation by id
     * @param $idlimitation
     * @throws Exception
     */
    public static function deletelimitation($idlimitation) {
       
        $limitationsAssignedCat       = self::getAllLimitationAssignedCategories($idlimitation);
        $limitationsAssignedInspector = self::getAllLimitationAssignedInspectors($idlimitation);
        
        if(!empty($limitationsAssignedCat) || !empty($limitationsAssignedInspector))
            throw new Exception("Delete Error! Limitation assigned to categories / inspectors");
        
        self::deleteById($idlimitation);
    }
    
    /**
     * Get all categories, which assigend this limitation
     * @param $idlimitation
     * @return array
     */
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
    
    /**
     * Get all inspectors, which assigend this limitation
     * @param $idlimitation
     * @return array
     */
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