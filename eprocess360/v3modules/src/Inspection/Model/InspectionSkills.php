<?php


namespace eprocess360\v3modules\Inspection\Model;

use eprocess360\v3core\Keydict\Entry\FixedString128;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;
use eprocess360\v3core\DB;
use Exception;

/**
 * Class InspectionSkills
 * @package eprocess360\v3controllers\Inspection\Model
 */
class InspectionSkills extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idInspSkill', 'Skill ID'),
            FixedString128::build('title', 'Title'),
            FixedString128::build('description', 'Description'),
            IdInteger::build('status', 'Controller ID'),
            IdInteger::build('createdUserId', 'Created By')
        )->setName('InspSkills')->setLabel('InspSkills');
    }
    
    /**
     * Insert skill to database
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
     * Get all skills from database
     * @return array
     */
    public static function allSkills($multiView = false)
    {
        
        $sql = "SELECT * FROM InspSkills ORDER BY title";
        
        $keydict = self::keydict();
        
        if($multiView){
            $select = "*";
            $result = ['keydict'=>$keydict, 'select'=>$select,'join'=>NULL, 'where'=>NULL];
            return $result;
        }
        
        $skills = DB::sql($sql);

        foreach ($skills as &$skill) {
            $skill = $keydict->wakeup($skill)->toArray();
        }

        return $skills;
    }
    
    /**
     * Delete skill from database
     * @param $idInspSkill
     * @throws Exception
     */
    public static function deleteSkill($idInspSkill)
    {
        
        $skillsAssignedCat       = self::getAllSkillAssignedCategories($idInspSkill);
        $skillsAssignedInspector = self::getAllSkillAssignedCategories($idInspSkill);
        
        if(!empty($skillsAssignedCat) || !empty($skillsAssignedInspector))
            throw new Exception("Delete Error! Skill assigned to categories / inspectors");
        
        self::deleteById($idInspSkill);
        
    }
    
    /**
     * Get all categories, which assigend this skill
     * @param $idInspSkill
     * @return array
     */
    public static function getAllSkillAssignedCategories($idInspSkill)
    {
        
        $sql = "SELECT * FROM InspCatSkills " . 
               "WHERE InspCatSkills.idInspSkill = {$idInspSkill}";

        $skills = DB::sql($sql);
        
        $new = [];
        foreach($skills as $skill){
            if(isset($skill['idInspSkill'])) {
                $new[] = $skill;
            }
        }
        
        return $new;
        
    }
    
    /**
     * Get all inspectors, which assigend this skill
     * @param $idInspSkill
     * @return array
     */
    public static function getAllSkillAssignedInspectors($idInspSkill)
    {
        $sql = "SELECT * FROM InspectorSkills " . 
               "WHERE InspectorSkills.idInspSkill = {$idInspSkill}";

        $skills = DB::sql($sql);
        
        $new = [];
        foreach($skills as $skill){
            if(isset($skill['idInspSkill'])) {
                $new[] = $skill;
            }
        }
        
        return $new;
        
    }
    
    public static function make($title = "0", $description = "") {

        $rowData = ['title'=>$title,
            'description'=>$description];

        return self::InspectionSkillConstruct($rowData);
    }

    public static function InspectionSkillConstruct($rowData = []) {
        $instance = new self();
        $instance->data = self::keydict();
        $instance->data->acceptArray($rowData);
        return $instance;
    }

}