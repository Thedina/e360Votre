<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 4/21/16
 * Time: 12:08 PM
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
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;
use eprocess360\v3controllers\Group\Group;
use eprocess360\v3core\DB;
use eprocess360\v3core\Model\Controllers;
use eprocess360\v3core\Model\Roles;
use eprocess360\v3core\Model\UserRoles;
use eprocess360\v3core\Warden;
use eprocess360\v3core\Controller\Warden\Privilege;
use MongoDB\BSON\Timestamp;

/**
 * Class Limitation
 * @package eprocess360\v3controllers\inspection\Model
 */
class Limitation extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idInspLimiattion', 'Inspection Limitation Id'),
            FixedString128::build('title', 'Controller ID'),
            FixedString128::build('description', 'Group Title'),
            IdInteger::build('status', 'Status'),
            IdInteger::build('createdUserId', 'Creater'),
            Keydict\Entry\Datetime::build('createdDate', 'Created time')
        )->setName('InspLimitations')->setLabel('InspLimitations');
    }


    public static function allLimitation($readable = false)
    {
        global $pool;
        //find all Limitation
        $sql = "SELECT * From InspLimitations";
        
        if($readable){
            $sql = "SELECT * FROM `InspLimitations`";
        }

        $new = array();
        foreach (self::each($sql)
                 as $sqlResult){
            $resultArray = $sqlResult->toArray();

            if(isset($resultArray['idInspLimiattion'])) {
                $new[] = $resultArray;
            }
        }

        return $new;
    }
    public static function deletelimitation($idlimitation) {

       
        $sql = "DELETE FROM InspLimitations WHERE `idInspLimiattion` = 1";
           DB::sql($sql);


        return true;
    }



}