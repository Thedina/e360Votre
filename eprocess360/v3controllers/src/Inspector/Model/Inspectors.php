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
    
    public static function getInspector($idInspector)
    {    
        
        $sql = "SELECT * FROM Inspectors LEFT JOIN Users ON Inspectors.idUser = Users.idUser " . 
               "WHERE Inspectors.idUser = {$idInspector}";

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