<?php

namespace eprocess360\v3controllers\MigrationsController\Model;
use eprocess360\v3core\Keydict\Entry\Bit;
use eprocess360\v3core\Keydict\Entry\Bits8;
use eprocess360\v3core\Keydict\Entry\Datetime;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\String;
use eprocess360\v3core\Keydict\Entry\TinyInteger;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model;

/**
 * Created by PhpStorm.
 * User: kiranebilak
 * Date: 2/23/16
 * Time: 10:30 AM
 */
class Migrations extends Model
{
    /**
     * @return Table
     * @throws \Exception
     * @throws \eprocess360\v3core\Keydict\Exception\KeydictException
     */
    public static function keydict()
    {
        return Table::build(
            PrimaryKeyInt::build('idMigration', 'ID'),
            String::build('title', 'Title'),
            String::build('className', 'Class Name'),
            String::build('file', 'File'),
            Datetime::build('timeCreate', 'Created On'),
            Datetime::build('timeCommit', 'Committed On'),
            Bits8::make('flags',
                Bit::build(0, 'canUp', 'Can be Committed'),
                Bit::build(1, 'canDown', 'Can be Uncommitted')
            )
        )->setName('Migrations')->setLabel('Migrations');
    }
}