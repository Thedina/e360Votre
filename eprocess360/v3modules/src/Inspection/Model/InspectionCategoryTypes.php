<?php

namespace eprocess360\v3modules\Inspection\Model;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\String;
use eprocess360\v3core\Keydict\Entry\Integer;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3controllers\Inspection\Inspection;

use eprocess360\v3core\DB;
/**
 * Class InspectionCategoryTypes
 * @package eprocess360\v3controllers\Inspection\InspectionCategoryTypes
 */
class InspectionCategoryTypes extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idInspCatType', 'Category Type ID'),
            Integer::build('idInspCategory', 'Category ID')->setRequired(),
            Integer::build('idInspType', 'Type ID')
        )->setName('InspCatTypes')->setLabel('InspCatTypes');
    }
    
    /**
     * Get inspection types assigned to category
     * @param $idInspCategory
     * @return array
     */
    public static function getTypes($idInspCategory)
    {
        $sql = "SELECT * FROM InspCatTypes LEFT JOIN InspTypes ON InspCatTypes.idInspType = InspTypes.idInspType " . 
               "WHERE InspCatTypes.idInspCategory = {$idInspCategory}";

        $skills = DB::sql($sql);
        
        $new = [];
        foreach($skills as $skill){
            if(isset($skill['idInspCatType'])) {
                $new[] = $skill;
            }
        }
        
        return $new;
    }

    /**
     * Assign / remove inspection types to / from category
     * @param $idInspCategory
     * @param $postData
     */
    public static function editTypes($idInspCategory, $postData)
    {
        $inspTypes    = self::getTypes($idInspCategory);
        $inspTypeIds   = array();
        $checkType     = false;
        
        if(!empty($inspTypes)){
            $checkType = true;
            foreach($inspTypes as $type){
                $idInspType = $type['idInspType'];
                $inspTypeIds[$idInspType] = $idInspType;
            }
        }

        foreach($postData as $postType){
            
            $add    = false;
            $remove = false;
            
            if($checkType){
                if(isset($inspTypeIds[$postType['id']]) && $postType['assigned'] == false){
                    $remove = true;
                }
                else if(!isset($inspTypeIds[$postType['id']])){
                    $add = ($postType['assigned'] == true) ? true : false;
                }
            }
            else{
                $add = ($postType['assigned'] == true) ? true : false;
            }

            if($add)
                self::addInspectionCategoryType($idInspCategory, $postType['id']);
            if($remove)
                self::deleteInspectionCategoryType($idInspCategory, $postType['id']);
        }
    }
    
    /**
     * Add category inspection types to database
     * @param $idInspCategory
     * @param $idType
     * @return boolean
     */
    public static function addInspectionCategoryType($idInspCategory, $idType)
    {
        $sql = "INSERT INTO InspCatTypes (`idInspCategory`, `idInspType`)" . 
               "VALUES({$idInspCategory}, {$idType})";

        DB::sql($sql);
        return true;
    }
    
    /**
     * Delete category inspection types to database
     * @param $idInspCategory
     * @param $idType
     * @return boolean
     */
    public static function deleteInspectionCategoryType($idInspCategory, $idType)
    {
        $sql = "DELETE FROM InspCatTypes " . 
               "WHERE idInspCategory = {$idInspCategory} AND idInspType = {$idType}";
               
        DB::sql($sql);
        return true;
    }
}
