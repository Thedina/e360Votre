<?php
namespace eprocess360\v3controllers\MigrationsController\Model\Migrations;
use eprocess360\v3core\Book\Index;
use eprocess360\v3core\Book\Relationship;
use eprocess360\v3core\Book\Migration;
use eprocess360\v3core\Book\MigrationTemplate;
use eprocess360\v3core\Keydict\Entry\Bit;
use eprocess360\v3core\Keydict\Entry\Bits;
use eprocess360\v3core\Keydict\Entry\Datetime;
use eprocess360\v3core\Keydict\Entry\Integer;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\String;
use eprocess360\v3core\Keydict\Entry\String256;

class MigrationsTest_160223_124600_Patch extends MigrationTemplate
{
    public function up(Migration $table)
    {
        $table->setOwner('\eprocess360\v3controllers\MigrationsController\Model\MigrationsTest');

        $table->addColumns(
            String::column('dummyTitle', 'Title'),
            Bits::column('flags', 'Flags')
        );

        $table->getColumnByName('flags')->addBit(
            Bit::build(0, 'isFruit')
        );
        $table->changeColumns([
            'dummyTitle'=>String256::column('dummyTitle', 'Title')
        ]);
    }

    public function down(Migration $table)
    {
        $table->destroyColumns(
            String::column('dummyTitle', 'Title'),
            Bits::column('flags', 'Flags')
        );
    }
}