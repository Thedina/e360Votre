<?php
/**
 * Keydict R1
 * Support for local and foreign datasets, borrowing, sharing, gateways, etc.
 */
use eprocess360\v3core\Keydict\Entry;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyFixedString32;
use eprocess360\v3core\Keydict\Table;

/**
 *  One of the stengths of the Keydict as it stands is the ability to have nested data that correlates properly with
 *  a flat SQL table.  Also possible is the ability for Nodes to have both a value and children.
 *
 *  Keydict (collection of Entry)
 *      Entry   value*
 *              meta
 *              children*    Entry
 *  Both value and children can be stored in the database because of the way data is condensed:
 *
 *  TableName
 *  fruit           Entry "fruit":   value      "apple"
 *  fruit_seeds                      children   Entry "seeds":  value   2
 *  fruit_color                                 Entry "color":  value   "green"
 *
 *  This sort of allocation works well for translating things like ESRI GIS layers to SQL.
 *
 *  Define/Import Local Books
 *  Define Library
 *  Add to Library
 *  Resume Bookmarks
 */

/**
 * Interface Entry
 */
interface EntryNew
{
    /**
     * Get the Value of the Entry
     * @return mixed
     */
    public function get();

    /**
     * Set the Value of the Entry
     * @param $value
     * @return Entry
     */
    public function set($value);

    /**
     * Get the Value of the Entry for saving to the database
     * @return mixed
     */
    public function sleep();

    /**
     * Set the Value of the Entry from database raw
     * @return Entry
     */
    public function wakeup();

    /**
     * Validate a potential value or throw an exception
     * @param $value
     * @return mixed
     */
    public static function validate($value);

    /**
     * @param Entry $entry
     * @return Entry
     */
    public function map(Entry $entry);
}


/**
 * Interface Book
 * Represents a table in the database.  Some tables are authoritative (single source of truth) while others are
 * borrowed (foreign entities with a local change log), and finally borrowed/authoritative (foreign entities with a
 * local single row record)
 */
interface InterfaceBook
{

    /* ==== USAGE =================================================================================================== */
    /**
     * @param string|int $volume Some books will have different tables for different IDs, differentiate here
     * @param string|null $title Optional Aliasing of the Book
     * @return self
     */
    public static function open($volume = null, $title = null);


    /* ==== COMPOSITION ============================================================================================= */
    /**
     * Build the Table here.  The Table here should be the working version of the newest migration.  For working with
     * foreign resources, the internal-facing Book should link with the external-facing Book via Entry::map().
     * @return Table
     */
    public function table();

    /**
     * Join this Book with another Book (SQL join).  Supports joins configured on the Entry, or through the override.
     * If this Book is joined on a non-authoritative Book, it will only join with localized records.
     * @param InterfaceBook $book
     * @param Entry $selfEntry
     * @param Entry $foreignEntry
     * @return InterfaceBook
     */
    public function join(InterfaceBook $book, Entry $selfEntry = null, Entry $foreignEntry = null);


    /* ==== RECORD LOOKUP, READING, WRITING ========================================================================= */
    /**
     * Tells the Book how to locate the correct row in the table (ie. the frame for which data will be read and
     * written).  In most cases this should be the PrimaryKey column only.
     * @return self
     */
    public function frame();

    /**
     * Read the current frame
     * @return self
     */
    public function read();

    /**
     * Save changes according to the rules
     * @param Entry|Entry[] ...$entries Save only the given Entry (which exist in the Book)
     * @return InterfaceBook
     */
    public function write(Entry ...$entries);


    /* ==== VISIBILITY ============================================================================================== */
    /**
     * Expose this Book or Entry through the API (no parameters to expose all)
     * @param Entry[] $entries
     * @return InterfaceBook
     */
    public function expose(Entry ...$entries);

    /**
     * Hide this Book or Entry from the API (no parameters to hide all)
     * @param Entry[] $entries
     * @return InterfaceBook
     */
    public function hide(Entry ...$entries);
}


class Book implements InterfaceBook
{
    protected $volume = null;
    protected $title = null;
    /** @var Entry */
    protected $frame = null;
    private $frameChanged;
    /** @var \eprocess360\v3core\Keydict */
    private $contents;
    /** @var Book */
    protected $basedOn;

    protected function __construct()
    {
        $this->contents = $this->table();
        $this->frame = $this->table();
        // allow for this Book to be based on another
        $this->basedOn();
    }

    /**
     * @param string|int $volume Some books will have different tables for different IDs, differentiate here
     * @param string|null $title Optional Aliasing of the Book
     * @return $this
     */
    public static function open($volume = null, $title = null)
    {
        $book = new self();
        $book->setVolume($volume);
        $book->setTitle($title);
        return $book;
    }

    public static function import($volume = null, $title = null)
    {
        $book = static::open($volume, $title);
        return $book;
    }

    /**
     * Build the Table here.  The Table here should be the working version of the newest migration.  For working with
     * foreign resources, the internal-facing Book should link with the external-facing Book via Entry::map().
     * @return Table
     */
    public function table()
    {
        return Table::build();
    }

    /**
     * Join this Book with another Book (SQL join).  Supports joins configured on the Entry, or through the override.
     * If this Book is joined on a non-authoritative Book, it will only join with localized records.
     * @param InterfaceBook $book
     * @param Entry $selfEntry
     * @param Entry $foreignEntry
     * @return $this
     */
    public function join(InterfaceBook $book, Entry $selfEntry = null, Entry $foreignEntry = null)
    {
        // TODO: Implement join() method.
    }

    /**
     * Tells the Book how to locate the correct row in the table (ie. the frame for which data will be read and
     * written).  In most cases this should be the PrimaryKey column only.
     * @return $this
     */
    public function frame()
    {
        if(!isset($this)) {
            return __CLASS__;
        }
        return $this->frame;

    }

    /**
     * Read the current frame
     * @return $this
     */
    public function read()
    {
        if ($this->frame->hasChanged()) {
            if (is_object($this->basedOn)) {
                // todo move to Entry/Keydict logic
//                foreach ($this->frame->getChanged() as $changedEntry) {
//                    $changedEntry->syncDown();
//                }
                $this->basedOn->setSync(true)->read()->setSync(false);
            } else {
                $this->contents->fetchId($this->frame->getPrimaryKey()->get());
            }
            $this->frame->hasChanged(false);
        }
        return $this;
    }

    /**
     * Save changes according to the rules
     * @param Entry|Entry[] ...$entries Save only the given Entry (which exist in the Book)
     * @return $this
     */
    public function write(Entry ...$entries)
    {
        // TODO: Implement write() method.
    }

    /**
     * Expose this Book or Entry through the API (no parameters to expose all)
     * @param Entry[] $entries
     * @return InterfaceBook
     */
    public function expose(Entry ...$entries)
    {
        // TODO: Implement expose() method.
    }

    /**
     * Hide this Book or Entry from the API (no parameters to hide all)
     * @param Entry[] $entries
     * @return InterfaceBook
     */
    public function hide(Entry ...$entries)
    {
        // TODO: Implement hide() method.
    }


    /**
     * @return null
     */
    public function getVolume()
    {
        return $this->volume;
    }

    /**
     * @param null $volume
     * @return Book
     */
    public function setVolume($volume)
    {
        $this->volume = $volume;
        return $this;
    }

    /**
     * @return null
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param null $title
     * @return Book
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param $name
     * @return Entry
     */
    public function __get($name)
    {
        return $this->contents->{$name};
    }

    /**
     * @return \eprocess360\v3core\Keydict
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * @param \eprocess360\v3core\Keydict $contents
     * @return $this
     */
    public function setContents($contents)
    {
        $this->contents = $contents;
        return $this;
    }

    /**
     * @return Book
     */
    public function getBasedOn()
    {
        return $this->basedOn;
    }

    private function basedOn()
    {
    }
}


/**
 * Interface Library
 * Has a fancy API for everything
 */
class Library extends Controller
{
    public function add(InterfaceBook $books) {

    }
}

/**
 *      /Model
 *          PondGIS.php
 *              /Migrations
 *              PondGIS_160220_add_parcel_owner.php
 */

$library = new Library();
$library->add(
  PondGIS::open()
);

class ProjectData extends Book
{
    public function relationshipAddress()
    {
        return $this->address->hasOne(PondGIS::frame());
    }

    public function colors()
    {
        return $this->hasMany(Colors::frame());
    }


    public function table()
    {
        return Table::build(
            Entry\String::build('address', 'Lilypad Address')
        )->setName('PondGIS');
    }

}

class Frogs extends Book
{
    public function table()
    {
        return Table::build(
            \eprocess360\v3core\Keydict::build('colors', 'Colors')
        );
    }
}

/**
 * Class PondGIS
 */
class PondGIS extends Book
{

    public function table()
    {
        return Table::build(
            Entry\String::build('lilypad', 'Lilypad Address'),
            Entry\String::build('parcel', 'Parcel'),
            Entry\String::build('parcelOwner', 'Owner')
        )->setName('PondGIS');
    }

    private function basedOn()
    {
        // basedOn settings - configured by admin panel
        /** @var Book $foreign */
        $foreign = PondGISForeign::import();
        $this->getContents()->lilypad->setBasedOn($foreign->layer_0->lilypad);
        $this->getContents()->parcel->setBasedOn($foreign->layer_0->parcel);
        $this->getContents()->parcelOwner->setBasedOn($foreign->layer_0->parcelOwner);
        $this->basedOn = $foreign;
    }

}

class PondGISForeign extends Book
{
    public function table()
    {
        return Table::build(
            \eprocess360\v3core\Keydict::build(
                \eprocess360\v3core\Keydict::build(
                Entry\String::build('lilypad', 'Lilypad Address'),
                Entry\String::build('parcel', 'Parcel'),
                Entry\String::build('parcelOwner', 'Owner')
                )->setName('0')
            )->setName('results')
        );
    }

    public function read()
    {
        $this->getContents()->acceptJsonResponse('https://gis.froggystreets.com/api?lilypad='.
            $this->frame->layer_0->lilypad->get());
        return $this;
    }
}