<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 8/19/2015
 * Time: 7:16 PM
 */

namespace eprocess360\v3core\Model;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Entry\FixedString16;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyFixedString16;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\Datetime;
use eprocess360\v3core\Keydict\Entry\TinyInteger;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;

/**
 * Configured System Groups
 * @package eprocess360\v3core\Model
 */
class Sessions extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idUser', 'User ID'),
            PrimaryKeyFixedString16::build('series', 'Series Key'),
            FixedString16::build('auth', 'Auth Key'),
            Datetime::build('expires', 'Expiration Time'),
            TinyInteger::build('status', 'Status')
        )->setName('Sessions')->setLabel('Sessions');
    }

//    private function makeTable()
//    {
//        $sql = "CREATE TABLE Sessions (
//          iduser    INT UNSIGNED,
//          series    CHAR(16),
//          auth      CHAR(16),
//          expires   TIMESTAMP,
//          status    TINYINT(1) UNSIGNED,
//          PRIMARY KEY (iduser, series)
//        ) CHAR SET latin1;";
//    }
}