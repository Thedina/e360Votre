<?php
namespace eprocess360\v3controllers\Group\Model\GroupProjects\Model\Migrations;
use eprocess360\v3core\Book\Index;
use eprocess360\v3core\Book\Relationship;
use eprocess360\v3core\Book\Migration;
use eprocess360\v3core\Book\MigrationTemplate;
use eprocess360\v3core\Keydict\Entry\Datetime;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\Integer;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\String;

class GroupProjects_160303_000000_Create extends MigrationTemplate
{
    public function up(Migration $table)
    {
        $table->setOwner('eprocess360\v3controllers\Group\Model\GroupProjects');
        $table->create();

        $table->addColumns(
            PrimaryKeyInt::column('idGroupProject', 'Group Project ID'),
            IdInteger::column('idGroup', 'Group ID'),
            IdInteger::column('idController', 'Controller ID')
        );

        $table->useTraits('GroupProjectsTrait');

    }

    public function down(Migration $table)
    {
        $table->destroy();
    }
}