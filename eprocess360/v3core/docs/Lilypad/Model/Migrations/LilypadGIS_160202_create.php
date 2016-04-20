<?php
use eprocess360\v3core\Book\Index;
use eprocess360\v3core\Book\Relationship;
use eprocess360\v3core\Book\Migration;
use eprocess360\v3core\Book\MigrationTemplate;

class LilypadGIS_160202_123001_Create extends MigrationTemplate
{
    public function up(Migration $migration)
    {
        $migration->addOptions(
            Migration::TIMESTAMP_CREATED &
            Migration::TIMESTAMP_UPDATED &
            Migration::SOFT_DELETE
        );

        $migration->setOwner('\eprocess360\v3core\Lilypad');

        $migration->addColumns(
            Integer::column('id', 'Primary Key')
                ->primary()
                ->autoIncrement(),
            String::column('address', 'Address')
                ->notNull()
        );

        $migration->addIndices(
            Index::primary('id')->increment(false), // alternate way of specifying primary
            Index::unique('address')->identifiedBy('addressIndex'),
            Index::plain('id', 'address')
        );

        $migration->addRelationships(
            Relationship::hasOne('address', 'PondGIS.address')
        );

        $migration->create(); // $this->update() to update rather than make new
    }

    public function down(Migration $migration)
    {
        $migration->destroy();
    }

    public static function sync(Book $book)
    {
        $foreign = PondGISForeign::import();
        $book->syncWith($foreign);
        $book->address->syncWith($foreign->layer_0->address);
        return $book;
    }

    public static function read(Book $book)
    {
        $book->getContents()->acceptJsonResponse('https://gis.froggystreets.com/api?lilypad='.
            $book->frame->layer_0->lilypad->get());
        return $book;
    }

}