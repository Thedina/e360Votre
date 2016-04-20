<?php
namespace eprocess360\v3controllers\MigrationsController\Model\Migrations;
use eprocess360\v3core\Book\Index;
use eprocess360\v3core\Book\Relationship;
use eprocess360\v3core\Book\Migration;
use eprocess360\v3core\Book\MigrationTemplate;
use eprocess360\v3core\Keydict\Entry\Datetime;
use eprocess360\v3core\Keydict\Entry\Integer;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\String;

class Migrations_160223_105700_Create extends MigrationTemplate
{
    const TEST = 10;
    public function up(Migration $table)
    {
        // this is the model path that will be translated to a directory
        $table->setOwner('eprocess360\v3controllers\MigrationsController\Model\Migrations');

        $table->create(); // Create the table

        $table->addColumns(
            Integer::column('idMigration', 'ID'),
            String::column('title', 'Title'),
            String::column('className', 'Class Name'),
            String::column('file', 'File'),
            Datetime::column('timeCreate', 'Created On'),
            Datetime::column('timeCommit', 'Committed On')
        );

        $table->useMethods('insert');
        $table->useTraits('MigrationsTraits');

    }

    public function down(Migration $table)
    {
        $table->destroy(); // Destroy the table
    }

    public static function insert($param = self::TEST, $string = 'string', ...$test) {}
}