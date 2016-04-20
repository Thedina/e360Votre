<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 8/19/2015
 * Time: 7:16 PM
 */

namespace eprocess360\v3core\Model;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Entry\Bit;
use eprocess360\v3core\Keydict\Entry\Bits8;
use eprocess360\v3core\Keydict\Entry\FixedString128;
use eprocess360\v3core\Keydict\Entry\FixedString256;
use eprocess360\v3core\Keydict\Entry\FixedString32;
use eprocess360\v3core\Keydict\Entry\FixedString64;
use eprocess360\v3core\Keydict\Entry\Flag;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\JSONArrayFixed128;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\TinyInteger;
use eprocess360\v3core\Keydict\Entry\UnsignedSmallInteger;
use eprocess360\v3core\Keydict\Entry\UnsignedTinyInteger;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;

/**
 * @package eprocess360\v3core\Model
 */
class Projects extends Model
{
    /**
     * @return Table
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idProject', 'Project ID'),
            IdInteger::build('idController', 'Controller ID')->joinsOn(ProjectControllers::model()),
            FixedString64::build('state', 'Current Project State'),
            FixedString128::build('title','Project Title'),
            FixedString128::build('description','Project Description'),
            Bits8::make('status',
                Bit::build(0, 'isActive', 'Active Project')
            )
        )->setName('Projects')->setLabel('Projects');
    }

}