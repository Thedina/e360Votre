<?php

namespace eprocess360\v3modules\Inspection\Model;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\Integer;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;

use eprocess360\v3core\DB;
/**
 * Class InspectionCategoryLimitations
 * @package eprocess360\v3controllers\Inspection\InspectionCategoryLimitations
 */
class InspectionCategoryLimitations extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idInspCatLimitation', 'Category Limitation ID'),
            Integer::build('idInspCategory', 'Category ID')->setRequired(),
            Integer::build('idInspLimitation', 'Limitation ID')
        )->setName('InspCatLimitations')->setLabel('InspCatLimitations');
    }
    
    /**
     * Get limitations assigned to category
     * @param type $idInspCategory
     * @return type
     */
    public static function getLimitations($idInspCategory)
    {
        $sql = "SELECT * FROM InspCatLimitations LEFT JOIN InspLimitations ON InspCatLimitations.idInspLimitation = InspLimitations.idInspLimitation " . 
               "WHERE InspCatLimitations.idInspCategory = {$idInspCategory}";

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
     * Assign / remove limitations to / from category
     * @param $idInspCategory
     * @param $postData
     * @return boolean
     */
    public static function editLimitations($idInspCategory, $postData)
    {
        $inspLimitations    = self::getLimitations($idInspCategory);
        $inspLimitationIds   = array();
        $checkLimitation     = false;
        
        if(!empty($inspLimitations)){
            $checkLimitation = true;
            foreach($inspLimitations as $limitation){
                $idInspLimitation = $limitation['idInspLimitation'];
                $inspLimitationIds[$idInspLimitation] = $idInspLimitation;
            }
        }
        
        foreach($postData as $postLimitation){
            
            $add    = false;
            $remove = false;
            
            if($checkLimitation){
                //limitation alredy assigned to inspector
                //check limitaion needs to be removed from inspector
                if(isset($inspLimitationIds[$postLimitation['id']]) && $postLimitation['assigned'] == false){
                    $remove = true;
                }
                else if(!isset($inspLimitationIds[$postLimitation['id']])){
                    $add = ($postLimitation['assigned'] == true) ? true : false;
                }
            }
            else{
                $add = ($postLimitation['assigned'] == true) ? true : false;
            }
            
            if($add)
                self::addInspectionCategoryLimitation($idInspCategory, $postLimitation['id']);
            if($remove)
                self::deleteInspectionCategoryLimitation($idInspCategory, $postLimitation['id']);
        }
        
        return true;
    }
    
    /**
     * Add category limitation to database
     * @param $idInspCategory
     * @param $idLimitation
     * @return boolean
     */
    public static function addInspectionCategoryLimitation($idInspCategory, $idLimitation)
    {
        $sql = "INSERT INTO InspCatLimitations (`idInspCategory`, `idInspLimitation`)" . 
               "VALUES({$idInspCategory}, {$idLimitation})";

        $limitations = DB::sql($sql);
        
        return true;
    }
    
    /**
     * Delete category limitation to database
     * @param $idInspCategory
     * @param $idLimitation
     * @return boolean
     */
    public static function deleteInspectionCategoryLimitation($idInspCategory, $idLimitation)
    {
        $sql = "DELETE FROM InspCatLimitations " . 
               "WHERE idInspCategory = {$idInspCategory} AND idInspLimitation = {$idLimitation}";
               
        $limitations = DB::sql($sql);
        
        return true;
    }

}