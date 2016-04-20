<?php
namespace eprocess360\v3controllers\Group\Model;
use eprocess360\v3core\Model;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\IdInteger;

class GroupProjects extends Model
{
    use Traits\GroupProjects;
    public static function keydict() {
        return Table::build(
            PrimaryKeyInt::build('idGroupProject', 'Group Project ID'),
            IdInteger::build('idGroup', 'Group ID'),
            IdInteger::build('idController', 'Controller ID')
        )->setName('GroupProjects')->setLabel('GroupProjects');
    }
}