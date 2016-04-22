<?php


namespace eprocess360\v3controllers\Inspection\Model;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\String;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3controllers\Inspection\Inspection;
/**
 * Class InspectionCategories
 * @package eprocess360\v3controllers\Group\Model
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
    
    public static function create($title, $description){
        
        $idController = Inspection::register($title);

        $f = static::make($title, $description);
        $f->insert();

        $result = $f->data->toArray();
        return $result;
    }

    public static function allCategories($readable = false)
    {
        global $pool;
        
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
    
    public static function deleteCategory($idInspCategory){
        
        self::deleteById($idInspCategory);
        
        return true;
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