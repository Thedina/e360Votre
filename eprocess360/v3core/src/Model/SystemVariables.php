<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 8/19/2015
 * Time: 7:16 PM
 */

namespace eprocess360\v3core\Model;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyFixedString32;
use eprocess360\v3core\Keydict\Entry\Text;
use eprocess360\v3core\Keydict\Entry\UnsignedTinyInteger;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;

/**
 * Configured System Groups
 * @package eprocess360\v3core\Model
 */
class SystemVariables extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyFixedString32::build('syskey', 'Key'),
            Text::build('value', 'Value'),
            UnsignedTinyInteger::build('ini','PHP Ini Setting'),
            UnsignedTinyInteger::build('json', 'JSON Array')
        )->setName('SystemVariables');
    }
}