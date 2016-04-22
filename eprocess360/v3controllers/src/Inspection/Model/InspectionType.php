<?php


namespace eprocess360\v3controllers\Inspection\Model;
use eprocess360\v3core\Controller\Controller;
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
use eprocess360\v3controllers\Group\Group;
use eprocess360\v3core\DB;
use eprocess360\v3core\Model\Controllers;
use eprocess360\v3core\Model\Roles;
use eprocess360\v3core\Model\UserRoles;
use eprocess360\v3core\Warden;
use eprocess360\v3core\Controller\Warden\Privilege;

/**
 * Class Groups
 * @package eprocess360\v3controllers\Group\Model
 */
class InspectionType extends Model
{
     public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idInspType', 'Type Id'),	
            FixedString128::build('description', 'Description'),
            FixedString128::build('title', 'Titel'),
            IdInteger::build('idInspCategory', 'Category'),
            IdInteger::build('status', 'Controller ID'),
            IdInteger::build('createdUserId', 'Created By'),
            Datetime::build('creaetdDate', 'Date')
           
        )->setName('InspectionType')->setLabel('InspectionType');
    }

        public static function allInspectionTypes($readable = false)
    {
        global $pool;
        //find all Groups where User has a UserGroup
        $sql = "SELECT InspTypes.* FROM InspTypes LEFT JOIN InspCategories"
                . " ON InspTypes.idInspCategory = InspCategories.idInspCategory "
                . "ORDER BY InspTypes.idInspType DESC LIMIT 0,30 ";

//        if($readable){
//            $sql = "SELECT * FROM `Groups`
//                    ORDER BY `idGroup` DESC";
//         }

        $new = array();
        foreach (self::each($sql)
                 as $sqlResult){
            $resultArray = $sqlResult->toArray();

            if(isset($resultArray['idInspType'])) {
                $new[] = $resultArray;
            }
        }

        return $new;
    }
    
    
    
}




