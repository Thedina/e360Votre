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