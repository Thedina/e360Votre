<?php


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
use eprocess360\v3core\Keydict\Entry\Datetime;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;
use eprocess360\v3controllers\Group\Group;
use eprocess360\v3core\DB;
use eprocess360\v3core\Model\Controllers;
use eprocess360\v3core\Model\Roles;
use eprocess360\v3core\Model\UserRoles;
use eprocess360\v3core\Warden;
use eprocess360\v3core\Controller\Warden\Privilege;
use eprocess360\v3controllers\Inspection\Inspection;
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
            String::build('description', 'Description'),
            String::build('title', 'Titel'),
            IdInteger::build('idInspCategory', 'Category'),
            IdInteger::build('status', 'Controller ID'),
            IdInteger::build('createdUserId', 'Created By'),
            Datetime::build('creaetdDate', 'Date')
           
        )->setName('InspTypes')->setLabel('InspTypes');
    }

        public static function allInspectionTypes($readable = false)
    {
        global $pool;

        $sql = "SELECT InspTypes.* FROM InspTypes LEFT JOIN InspCategories"
                . " ON InspTypes.idInspCategory = InspCategories.idInspCategory "
                . "ORDER BY InspTypes.idInspType DESC LIMIT 0,30 ";

//        if($readable){
//            $sql = "SELECT * FROM `Groups`
//                    ORDER BY `idGroup` DESC";
//         }

        $new = array();
        foreach (self::each($sql)
                 as $sqlResult){
            $resultArray = $sqlResult->toArray();

            if(isset($resultArray['idInspType'])) {
                $new[] = $resultArray;
            }
        }

        return $new;
    }
    
    
    public static function create($title, $description){
        
        $idController = Inspection::register($title);

        $f = static::make($title, $description);
        $f->insert();

        $result = $f->data->toArray();
        return $result;
    }
    
    public static function deleteTypes($idInspType){
        
        $typesAssignedCat  = self::getAllTypeAssignedCategories($idInspType);
        
        if(!empty($typesAssignedCat))
            throw new Exception("Delete Error! Type assigned to categories");
        
        self::deleteById($idInspType);

    }
    
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




