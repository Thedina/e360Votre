<?php


namespace eprocess360\v3controllers\Inspection\Model;
use Composer\Command\SelfUpdateCommand;
use Dompdf\Exception;
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
use eprocess360\v3core\Model\Roles;
use eprocess360\v3core\Model\UserRoles;
use eprocess360\v3core\Model\Users;
use eprocess360\v3core\User;
use eprocess360\v3core\DB;
use SebastianBergmann\Comparator\ExceptionComparatorTest;

/**
 * Class GroupUsers
 * @package eprocess360\v3controllers\Group\Model
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
            FixedString128::build('description', 'Description'),
            FixedString128::build('title', 'Title'),
            IdInteger::build('status', 'Controller ID'),
            IdInteger::build('createdUserId', 'Created By')
        )->setName('InspSkills')->setLabel('InspSkills');
    }
    
    public static function create($title, $description){
        
        $f = static::make($title, $description);
        $f->insert();

        $result = $f->data->toArray();
        return $result;
        
    }


    public static function allSkills($readable = false)
    {
        
        //find all Groups where User has a UserGroup
        $sql = "SELECT * FROM InspSkills";

        $new = array();
        foreach (self::each($sql)
                 as $sqlResult){
            
            $resultArray = $sqlResult->toArray();

            if(isset($resultArray['idInspSkill'])) {
                $new[] = $resultArray;
            }
        }
        return $new;
    }
    
    
    public static function deleteSkill($idInspSkill)
    {
        
        $skillsAssignedCat       = self::getAllSkillAssignedCategories($idInspSkill);
        $skillsAssignedInspector = self::getAllSkillAssignedCategories($idInspSkill);
        
        if(!empty($skillsAssignedCat) || !empty($skillsAssignedInspector))
            throw new Exception("Delete Error! Skill assigned to categories / inspectors");
        
        self::deleteById($idInspSkill);
        
    }
    
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