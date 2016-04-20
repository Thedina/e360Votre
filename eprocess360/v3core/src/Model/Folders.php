<?php

namespace eprocess360\v3core\Model;
use eprocess360\v3core\Model;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\FixedString32;
use eprocess360\v3core\Keydict\Entry\FixedString128;
use eprocess360\v3core\Keydict\Entry\FixedString256;
use eprocess360\v3core\Keydict\Entry\Bits8;
use eprocess360\v3core\Keydict\Entry\Bit;


class Folders extends Model
{
    public static function keydict() {
        return Table::build(
            PrimaryKeyInt::build('idFolder', 'Folder ID'),
            IdInteger::build('idController', 'Controller ID'),
            IdInteger::build('idParent', 'Parent ID'),
            IdInteger::build('idProject', 'Project ID'),
            FixedString32::build('hash', 'Hash (subdir)'),
            FixedString128::build('folderName', 'Folder Name'),
            Bits8::make('flags',
                Bit::build(0, 'locked', 'Locked')
            )
        )->setName('Folders')->setLabel('Folders');
    }
}