<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
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
use eprocess360\v3core\DB;
use eprocess360\v3core\Model\Controllers;
use eprocess360\v3core\Model\Roles;
use eprocess360\v3core\Model\UserRoles;
use eprocess360\v3core\Warden;
use eprocess360\v3core\Controller\Warden\Privilege;

/**
 * Description of InspectionType
 *
 * @author madusankagmt
 */
class InspectionType extends Model {
    //put your code here
    
    
      public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idInspType', 'Inspection Type ID'),
            FixedString128::build('title', 'Titel'),
            FixedString128::build('description', 'Description'),
            TinyInteger::build('idInspCategory','Category'),
            TinyInteger::build('createdUserId','Created By'),
            Datetime::build('creaetdDate','Created Date'),
            TinyInteger::build('status','status')
                
        );
    }
    
    
    public static function allInspectionTypes($readable = false)
    {
        global $pool;
        //find all Inspection Type 
        $sql = "SELECT InspTypes.* FROM InspTypes LEFT JOIN InspCategories ON "
                . "InspTypes.idInspCategory = InspCategories.idInspCategory "
                . "ORDER BY InspTypes.idInspCategory DESC LIMIT 0,30 ";

        if($readable){
            $sql = "SELECT * FROM `InspTypes`
                   ORDER BY `idInspCategory` DESC";
         }

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
