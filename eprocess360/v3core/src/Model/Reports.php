<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 8/19/2015
 * Time: 7:16 PM
 */

namespace eprocess360\v3core\Model;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Entry\FixedString128;
use eprocess360\v3core\Keydict\Entry\FixedString256;
use eprocess360\v3core\Keydict\Entry\FixedString32;
use eprocess360\v3core\Keydict\Entry\Flag;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\JSONArray256;
use eprocess360\v3core\Keydict\Entry\JSONArrayFixed128;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\String;
use eprocess360\v3core\Keydict\Entry\TinyInteger;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;

/**
 * @package eprocess360\v3core\Model
 */
class Reports extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idReport', 'Report ID'),
            String::build('title', 'Title'),
            JSONArray256::build('tables', 'Tables'),
            JSONArray256::build('filters', 'Filters'),
            JSONArray256::build('columns', 'Columns'),
            String::build('template', 'Template')
        )->setName('Reports')->setLabel('Reports');
    }
}