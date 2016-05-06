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
 * Class InspectionCategorySkills
 * @package eprocess360\v3controllers\Inspection\InspectionCategorySkills
 */
class InspectionCategorySkills extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idInspCatSkill', 'Category Skill ID'),
            Integer::build('idInspCategory', 'Category ID')->setRequired(),
            Integer::build('idInspSkill', 'Skill ID')
        )->setName('InspCatSkills')->setLabel('InspCatSkills');
    }
    
    /**
     * Get skills assigned to category
     * @param $idInspCategory
     * @return array
     */
    public static function getSkills($idInspCategory)
    {
        $sql = "SELECT * FROM InspCatSkills LEFT JOIN InspSkills ON InspCatSkills.idInspSkill = InspSkills.idInspSkill " . 
               "WHERE InspCatSkills.idInspCategory = {$idInspCategory}";

        $skills = DB::sql($sql);
        
        $new = [];
        foreach($skills as $skill){
            if(isset($skill['idInspCategory'])) {
                $new[] = $skill;
            }
        }
        
        return $new;
    }
    
    /**
     * Assign / remove skill to / from category
     * @param $idInspCategory
     * @param $postData
     * @return boolean
     */
    public static function editSkills($idInspCategory, $postData)
    {
        $inspSkills     = self::getSkills($idInspCategory);
        $inspSkillIds   = array();
        $checkSkill     = false;
        
        if(!empty($inspSkills)){
            $checkSkill = true;
            foreach($inspSkills as $skill){
                $idInspSkill = $skill['idInspSkill'];
                $inspSkillIds[$idInspSkill] = $idInspSkill;
            }
        }
        
        foreach($postData as $postSkill){
            
            $add    = false;
            $remove = false;
            
            if($checkSkill){
                //skill alredy assigned to inspector
                //check skill needs to be removed from inspector
                if(isset($inspSkillIds[$postSkill['id']]) && $postSkill['assigned'] == false){
                    $remove = true;
                }
                else if(!isset($inspSkillIds[$postSkill['id']])){
                    $add = ($postSkill['assigned'] == true) ? true : false;
                }
            }
            else{
                $add = ($postSkill['assigned'] == true) ? true : false;
            }
            
            if($add)
                self::addInspectionCategorySkill($idInspCategory, $postSkill['id']);
            if($remove)
                self::deleteInspectionCategorySkill($idInspCategory, $postSkill['id']);
        }
        
        return true;
    }
    
    /**
     * Add category skill to database
     * @param $idInspCategory
     * @param $idSkill
     * @return boolean
     */
    public static function addInspectionCategorySkill($idInspCategory, $idSkill)
    {
        $sql = "INSERT INTO InspCatSkills (`idInspCategory`, `idInspSkill`)" . 
               "VALUES({$idInspCategory}, {$idSkill})";

        DB::sql($sql);
        return true;
    }
    
    /**
     * Remove category skill from database
     * @param $idInspCategory
     * @param $idSkill
     * @return boolean
     */
    public static function deleteInspectionCategorySkill($idInspCategory, $idSkill)
    {
        $sql = "DELETE FROM InspCatSkills " . 
               "WHERE idInspCategory = {$idInspCategory} AND idInspSkill = {$idSkill}";
               
        DB::sql($sql);
        return true;
    }

}