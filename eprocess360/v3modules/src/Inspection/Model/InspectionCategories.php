<?php

namespace eprocess360\v3modules\Inspection\Model;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\String;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3controllers\Inspection\Inspection;

use eprocess360\v3core\DB;
/**
 * Class InspectionCategories
 * @package eprocess360\v3controllers\Inspection\InspectionCategories
 */
class InspectionCategories extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idInspCategory', 'Category ID'),
            String::build('title', 'Category Name')->setRequired(),
            String::build('description', 'Category Description')
        )->setName('InspCategories')->setLabel('InspCategories');
    }
    
    /**
     * Insert inspection category to database
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
     * Get all categories from database
     * @return array
     */
    public static function allCategories()
    {        
        //find all Inspection Categories
        $sql = "SELECT * FROM InspCategories ORDER BY title";

        $new = array();
        foreach (self::each($sql) as $sqlResult){
            
            $resultArray = $sqlResult->toArray();

            if(isset($resultArray['idInspCategory'])) {
                $new[] = $resultArray;
            }
        }
        return $new;
    }
    
    /**
     * Delete inspection category by id
     * @param $idInspCategory
     * @return boolean
     */
    public static function deleteCategory($idInspCategory)
    {
        self::deleteById($idInspCategory);    
        return true;
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
            if(isset($skill['idInspCategory'])) {
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
            $checkLimitation = true;
            foreach($inspTypes as $type){
                $idInspType = $type['idInspType'];
                $inspTypeIds[$idInspType] = $idInspType;
            }
        }
        
        foreach($postData as $postType){
            
            $add    = false;
            $remove = false;
            
            if($checkType){
                //limitation alredy assigned to inspector
                //check limitaion needs to be removed from inspector
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
    
    /**
     * @param $title
     * @param $description
     */
    public static function make($title = "0", $description = "") {

        $rowData = ['title'=>$title,
            'description'=>$description];

        return self::InspectionCategoryConstruct($rowData);
    }

    /**
     * @param type $rowData
     * @return \self
     */
    public static function InspectionCategoryConstruct($rowData = []) {
        $instance = new self();
        $instance->data = self::keydict();
        $instance->data->acceptArray($rowData);
        return $instance;
    }

}