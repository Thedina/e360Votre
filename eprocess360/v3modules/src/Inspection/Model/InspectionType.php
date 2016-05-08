<?php


namespace eprocess360\v3modules\Inspection\Model;

use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\String;
use eprocess360\v3core\Keydict\Entry\Datetime;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;
use eprocess360\v3core\DB;
use Exception;
/**
 * Class InspectionType
 * @package eprocess360\v3controllers\Inspection\Model
 */
class InspectionType extends Model
{
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idInspType', 'Type Id'),	
            String::build('title', 'Title'),
            String::build('description', 'Description')
           
        )->setName('InspTypes')->setLabel('InspTypes');
    }
    
    /**
     * Get all inspection types from database
     * @return array
     */
    public static function allInspectionTypes($multiView = false)
    {
        //find all Inspection types
        $sql = "SELECT * FROM InspTypes ORDER BY title";
        
        $keydict = self::keydict();
        
        if($multiView){
            $select = "*";
            $result = ['keydict'=>$keydict, 'select'=>$select,'join'=>NULL, 'where'=>NULL];
            return $result;
        }
                
        $types = DB::sql($sql);

        foreach ($types as &$type) {
            $type = $keydict->wakeup($type)->toArray();
        }
        
        return $types;
    }
    
    /**
     * Insert inspection type to database
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
     * Delete inspection type from database
     * @param $idInspType
     * @throws Exception
     */
    public static function deleteTypes($idInspType){
        
        $typesAssignedCat  = self::getAllTypeAssignedCategories($idInspType);
        
        if(!empty($typesAssignedCat))
            throw new Exception("Delete Error! Type assigned to categories");
        
        self::deleteById($idInspType);

    }
    
    /**
     * Get all categories, which assigend this type
     * @param $idInspType
     * @return array
     */
    public static function getAllTypeAssignedCategories($idInspType)
    {
        
        $sql = "SELECT * FROM InspCatTypes " . 
               "WHERE InspCatTypes.idInspType = {$idInspType}";

        $types = DB::sql($sql);
        
        $new = [];
        foreach($types as $type){
            if(isset($type['idInspType'])) {
                $new[] = $type;
            }
        }
        
        return $new;
    }
    
    public static function make($title = "0", $description = "") {

        $rowData = ['title'=>$title,
            'description'=>$description];

        return self::InspectionTypeConstruct($rowData);
    }
    
    public static function InspectionTypeConstruct($rowData = []) {
        $instance = new self();
        $instance->data = self::keydict();
        $instance->data->acceptArray($rowData);
        return $instance;
    }
    
}




