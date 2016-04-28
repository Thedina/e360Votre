<?php


namespace eprocess360\v3controllers\Inspector\Model;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\String;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Keydict\Entry\Integer;
use eprocess360\v3core\Keydict\Entry\PhoneNumber;
use eprocess360\v3core\Keydict\Entry\Email;
use eprocess360\v3core\Model;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3controllers\Inspector\Inspector;
use eprocess360\v3core\Model\Users;

use eprocess360\v3core\DB;

use Exception;

/**
 * Class InspectionCategories
 * @package eprocess360\v3controllers\Group\Model
 */
class Inspectors extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idInspector', 'Inspector ID'),
            Integer::build('idUser', 'User ID')->joinsOn(Users::model()),
//            Email::build('email', 'Email'),
//            String::build('firstName', 'First Name'),
//            String::build('lastName', 'Last Name'),
            String::build('address', 'Address'),
            PhoneNumber::build('phone', 'Phone'),
            String::build('fax', 'Fax')
        )->setName('Inspectors')->setLabel('Inspectors');
        
    }
    
    public static function create($idUser){
        
        $sql = "SELECT Inspectors.idUser FROM Inspectors WHERE
                Inspectors.idUser = {$idUser}";
        $inspector = DB::sql($sql);

        if($inspector !== [])
            throw new Exception("User is already added as Inspector.");
        
        $f = static::make($idUser);
        $f->insert();

        $result = $f->data->toArray();
        return $result;
    }

    public static function allInspectors($readable = false)
    {
        
        //find all Inspection Categories
        $sql = "SELECT * FROM Inspectors LEFT JOIN Users ON Inspectors.idUser = Users.idUser";
        $inspectors = DB::sql($sql);
        
        $new = [];
        
        foreach($inspectors as $inspector){
            
            if(isset($inspector['idInspector'])) {
                $new[] = $inspector;
            }
        }
        
        return $new;
    }
    
    
    public static function getInspector($idInspUser)
    {    
        
        $sql = "SELECT * FROM Inspectors LEFT JOIN Users ON Inspectors.idUser = Users.idUser " . 
               "WHERE Inspectors.idUser = {$idInspUser}";

        $inspectors = DB::sql($sql);
        
        $new = [];
            
        if(isset($inspectors[0]['idInspector'])) {
            $new = $inspectors[0];
        }
        
        return $new;
    }
    
    public static function editInspector($inspUserId, $address, $fax)
    {
        $sql = "UPDATE Inspectors SET address = '{$address}', fax = '{$fax}'" . 
               "WHERE Inspectors.idUser = {$inspUserId}";
               
        DB::sql($sql);
        
        return true;
    }
    
    
    public static function editSkills($idInspector, $postData)
    {
        $inspSkills     = self::getSkills($idInspector);
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
                self::addInspectorSkill($idInspector, $postSkill['id']);
            if($remove)
                self::deleteInspectorSkill($idInspector, $postSkill['id']);
        }
    }
    
    public static function getSkills($idInspector)
    {
        $sql = "SELECT * FROM InspectorSkills LEFT JOIN InspSkills ON InspectorSkills.idInspSkill = InspSkills.idInspSkill " . 
               "WHERE InspectorSkills.idInspector = {$idInspector}";

        $skills = DB::sql($sql);
        
        $new = [];
        foreach($skills as $skill){
            if(isset($skill['idInspectorSkill'])) {
                $new[] = $skill;
            }
        }
        
        return $new;
    }
    
    public static function addInspectorSkill($idInspector, $idSkill)
    {
        $sql = "INSERT INTO InspectorSkills (`idInspector`, `idInspSkill`)" . 
               "VALUES({$idInspector}, {$idSkill})";

        $skills = DB::sql($sql);
        
        return true;
    }
    
    public static function deleteInspectorSkill($idInspector, $idSkill)
    {
        $sql = "DELETE FROM InspectorSkills " . 
               "WHERE idInspector = {$idInspector} AND idInspSkill = {$idSkill}";
               
        $skills = DB::sql($sql);
        
        return true;
    }
    
    public static function getLimitations($idInspector)
    {
        $sql = "SELECT * FROM InspectorLimitations LEFT JOIN InspLimitations ON InspectorLimitations.idInspLimitation = InspLimitations.idInspLimitation " . 
               "WHERE InspectorLimitations.idInspector = {$idInspector}";

        $limitations = DB::sql($sql);
        
        $new = [];
        foreach($limitations as $limitation){
            if(isset($limitation['idInspectorLimitation'])) {
                $new[] = $limitation;
            }
        }
        
        return $new;
    }
    
    public static function editLimitations($idInspector, $postData)
    {
        $inspLimitations    = self::getLimitations($idInspector);
        $inspLimitationIds   = array();
        $checkLimitation     = false;
        $limitationAdd       = array();
        $limitationRemove    = array();
        
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
                self::addInspectorLimitation($idInspector, $postLimitation['id']);
            if($remove)
                self::deleteInspectorLimitation($idInspector, $postLimitation['id']);
        }
    }
    
    public static function addInspectorLimitation($idInspector, $idLimitation)
    {
        $sql = "INSERT INTO InspectorLimitations (`idInspector`, `idInspLimitation`)" . 
               "VALUES({$idInspector}, {$idLimitation})";

        $limitations = DB::sql($sql);
        
        return true;
    }
    
    public static function deleteInspectorLimitation($idInspector, $idLimitation)
    {
        $sql = "DELETE FROM InspectorLimitations " . 
               "WHERE idInspector = {$idInspector} AND idInspLimitation = {$idLimitation}";
               
        $limitations = DB::sql($sql);
        
        return true;
    }

    public static function deleteInspector($idInspector){
        
        self::deleteAllInspectorSkills($idInspector);
        self::deleteAllInspectorLimitations($idInspector);
        self::deleteById($idInspector);
        
        return true;
    }
    
    public static function deleteAllInspectorSkills($idInspector)
    {
        $sql = "DELETE FROM InspectorSkills " . 
               "WHERE idInspector = {$idInspector}";
               
        DB::sql($sql);
        
        return true;
    }
    
    
    public static function deleteAllInspectorLimitations($idInspector)
    {
        $sql = "DELETE FROM InspectorLimitations " . 
               "WHERE idInspector = {$idInspector} ";
               
        DB::sql($sql);
        
        return true;
    }
    
    public static function make($idUser = NULL)
    {

        $rowData = ['idUser'=>$idUser];

        return self::InspectionCategoryConstruct($rowData);
    }

    public static function InspectionCategoryConstruct($rowData = [])
    {
        $instance = new self();
        $instance->data = self::keydict();
        $instance->data->acceptArray($rowData);
        return $instance;
    }

}