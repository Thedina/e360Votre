<?php
/**
 * Created by PhpStorm.
 * User: kiranebilak
 * Date: 2/18/16
 * Time: 12:00 PM
 */
namespace eprocess360\v3core\Book;
use eprocess360\v3controllers\MigrationsController\Model\Migrations;
use eprocess360\v3core\Keydict\Entry;

/**
 * Class Migration
 * @package eprocess360\v3core\Book
 *
 * Terms:
 *      Book        Model
 *      Table       Table as it Exists in the Database
 */
class Migration
{
    const CREATE = 1;
    const DESTROY = 2;
    const TIMESTAMP_CREATED = 4;
    const TIMESTAMP_UPDATED = 8;
    const SOFT_DELETE = 16; // is actually TIMESTAMP_DELETED
    const CHANGE_LOG = 32; // track all changes

    private $owner;
    /** @var Entry[] */
    private $columns = [];
    /** @var Index[] */
    private $indices = [];
    /** @var Index[] */
    private $indicesRemoved = [];
    /** @var Entry[] */
    private $columnsRemoved = [];
    /** @var Entry[] */
    private $columnsDestroyed = [];
    /** @var Relationship[] */
    private $relationships = [];
    /** @var Relationship[] */
    private $relationshipsRemoved;
    /** @var int */
    private $options;
    private $startTime;
    private $endTime;
    private $columnsAdded;

    private $operations;
    private $modelBasePath;
    private $modelPath;
    private $modelName;
    private $modelLabel = null;
    /** @var  MigrationTemplate */
    private $currentOperation;
    private $methods = [];
    private $traits = [];

    public function __construct($model)
    {
        $this->model = $model;
    }

    /** COMMIT METHODS ============================================================================================== */

    /**
     * Migrate a Model between the Start and End Times
     * @param $model
     * @param bool $nullMode
     * @return MigrationTemplate|\Generator
     * @throws \Exception
     * @throws \eprocess360\v3core\Keydict\Exception\InvalidValueException
     */
    public function migrate($nullMode = false)
    {
        $table = Migrations::keydict();
        $table->className->set($this->model);

        // if we have a range of 0 .. 0, there is no way this function can return anything, so yield nothing
        if ($this->startTime == 0 && $this->endTime == 0) {
            return;
        }
        // switch start and end so they behave in BETWEEN
        if ($this->startTime < $this->endTime) {
            $start = date(Entry\Datetime::format(), $this->startTime);
            $end = date(Entry\Datetime::format(), $this->endTime);
        } else {
            $end = date(Entry\Datetime::format(), $this->startTime);
            $start = date(Entry\Datetime::format(), $this->endTime);
        }

        $query = $nullMode?'timeCommit is NULL':"timeCommit BETWEEN '{$start}' AND '{$end}'";
        // Get all the available migrations for the Model
        foreach (Migrations::each($sql = "SELECT * FROM Migrations WHERE className = {$table->className->cleanSleep()} AND {$query}") as $result) {
            // todo fix the bug where timeCommit in operation incorrectly has non-null value when null expected
            $class = $result->file->get();
            $operation = new $class;
            /** @var MigrationTemplate $operation */
            $operation->setData($result->toArray());
            yield $operation;
        }
    }

    /**
     * Apply last changes, clean-up and write Model
     */
    public function commit()
    {
        global $twig, $twig_loader;
        $twig_loader->addPath(APP_PATH.'/eprocess360/v3core/src/Book/static/twig');

        echo $twig->render('Book.Model.php.twig', ['Model'=>$this]);


        $table = Migrations::keydict();
        $time = date(Entry\Datetime::timestamps(), time());
        // For each migration operation (MigrationTemplate) that was migrated or rolled back, update the timeCommit
        // column
        /** @var MigrationTemplate $operation */
        foreach ($this->operations as $operation) {
            $table->acceptArray($operation->getData());
            $table->timeCommit->set($operation->isUp()?$time:null);
            $table->update();
        }
    }

    /** COMMAND METHODS ============================================================================================= */

    /**
     * Create the Book and Table.  Cannot create() if Table and/or Model already exists.
     * Creates the Book PHP file.
     */
    public function create()
    {
        $this->addOptions(self::CREATE);
        return $this;
    }

    /**
     * Destroy the Book and Table.  Can destroy() regardless of existence.
     * Delete the Book PHP file.
     */
    public function destroy()
    {
        $this->addOptions(self::DESTROY);
        return $this;
    }

    public function up($model)
    {
        foreach ($this->migrate($model) as $migration) {
            $migration->up();
        }
    }

    public function down($model)
    {
        foreach ($this->migrate($model) as $migration) {
            $migration->down();
        }
    }


    /** COLUMNS ===================================================================================================== */

    /**
     * Add Columns (aka Entry) to the Book.  This is a change log.
     * @param Entry[] $entries
     * @return $this
     * @throws BookRevisionException
     */
    public function addColumns(Entry ...$entries)
    {
        foreach ($entries as $entry) {
            if ($this->hasColumn($entry)) {
                throw new BookRevisionException('Column already exists.');
            }
            $this->columns[$entry->getName()] = $entry;
            $this->columnsAdded[$entry->getName()] = $entry;
            // Make sure we don't try to remove a Column with the same name later
            if (array_key_exists($entry->getName(), $this->columnsRemoved)) {
                unset($this->columnsRemoved[$entry->getName()]);
            }
        }
        return $this;
    }

    /**
     * Remove Columns (aka Entry) from the Book.  This is a change log.  Doesn't remove the data from the database.
     * @param Entry|\eprocess360\v3core\Keydict\Entry[] ...$entries
     * @return $this
     * @throws BookRevisionException
     */
    public function removeColumns(Entry ...$entries)
    {
        foreach ($entries as $entry) {
            if (!$this->hasColumn($entry)) {
                throw new BookRevisionException('Column doesn\'t exist.');
            }
            $this->columnsRemoved[$this->columns[$entry->getName()]->getName()] = $this->columns[$entry->getName()];
            unset($this->columns[$entry->getName()], $this->columnsAdded[$entry->getName()]);
        }
        return $this;
    }

    /**
     * Destroy Columns (aka Entry) from the Book.  This is a change log.  Removes data from the database.
     * @param Entry|\eprocess360\v3core\Keydict\Entry[] ...$entries
     * @return $this
     * @throws BookRevisionException
     */
    public function destroyColumns(Entry ...$entries)
    {
        foreach ($entries as $entry) {
            if (!$this->hasColumn($entry)) {
                throw new BookRevisionException('Column doesn\'t exist.');
            }
            $this->columnsDestroyed[$this->columns[$entry->getName()]->getName()] = $this->columns[$entry->getName()];
            unset($this->columns[$entry->getName()], $this->columnsAdded[$entry->getName()]);
        }
        return $this;
    }

    /**
     * @param Entry[] $changes
     */
    public function changeColumns($changes)
    {

    }

    /**
     * Checks to see if the Column name is taken using the Entry or Name
     * @param Entry|string $entry
     * @return bool
     */
    public function hasColumn($entry)
    {
        return array_key_exists(is_object($entry)?$entry->getName():$entry, $this->columns);
    }

    /**
     * Get the Column (aka Entry) by Name
     * @param $column
     * @return Entry
     * @throws BookRevisionException
     */
    public function getColumnByName($column)
    {
        if (!$this->hasColumn($column)) {
            throw new BookRevisionException('Column doesn\'t exist.');
        }
        return $this->columns[$column];
    }

    /** INDEXES ===================================================================================================== */

    /**
     * Add Indices to the Book.  This is a change log.
     * @param Index[] $indices
     * @throws BookRevisionException
     */
    public function addIndices(Index ...$indices)
    {
        foreach ($indices as $index) {
            if ($this->hasIndex($index)) {
                throw new BookRevisionException('Column already exists.');
            }
            $this->indices[$index->getName()] = $index;
            // Make sure we don't try to remove an Index with the same name later
            if (array_key_exists($index->getName(), $this->indicesRemoved)) {
                unset($this->indicesRemoved[$index->getName()]);
            }
        }
    }

    /**
     * Remove Indices from the Book.  This is a change log.  Doesn't remove the data from the database.
     * @param Index[] $indices
     * @throws BookRevisionException
     */
    public function removeIndices(Index ...$indices)
    {
        foreach ($indices as $index) {
            if (!$this->hasIndex($index)) {
                throw new BookRevisionException('Index doesn\'t exist.');
            }
            $this->indicesRemoved[$this->indices[$index->getName()]->getName()] = $this->indices[$index->getName()];
            unset($this->indices[$index->getName()]);
        }
    }

    /**
     * Checks to see if the Column name is taken using the Entry or Name
     * @param Index|string $index
     * @return bool
     */
    public function hasIndex($index)
    {
        return array_key_exists(is_object($index)?$index->getName():$index, $this->columns);
    }
    /** METHODS ===================================================================================================== */

    /**
     * Use a method from the current migration
     * @param array ...$methods
     */
    public function useMethods(... $methods)
    {
        $migrationTemplate = $this->getCurrentOperation();
        foreach ($methods as $method) {
            $file = $migrationTemplate->getData();
            $file = $file['file'];
            $this->methods[$method][$file] = new \ReflectionMethod($migrationTemplate,
                $method);
        }
    }

    /**
     * Remove a method from supported methods
     * @param array ...$methods
     */
    public function dropMethods(... $methods)
    {
        foreach ($methods as $method) {
            unset($this->methods[$method]);
        }
    }

    public function rollbackMethods($file)
    {
        foreach ($this->methods as $method=>$files) {
            if (isset($files[$file])) {
                unset($this->methods[$method][$file]);
            }
        }
    }

    /** TRAITS ====================================================================================================== */

    /**
     * Use a method from the current migration
     * @param array ...$traits
     */
    public function useTraits(... $traits)
    {
        $migrationTemplate = $this->getCurrentOperation();
        foreach ($traits as $trait) {
            $file = $migrationTemplate->getData();
            $file = $file['file'];
            $this->traits[$trait][$file] = $trait;
        }
    }

    /**
     * Remove a method from supported methods
     * @param array ...$traits
     */
    public function dropTraits(... $traits)
    {
        foreach ($traits as $trait) {
            unset($this->traits[$trait]);
        }
    }

    public function rollbackTraits($file)
    {
        foreach ($this->traits as $trait=>$files) {
            if (isset($files[$file])) {
                unset($this->traits[$trait][$file]);
            }
        }
    }

    /** RELATIONSHIPS =============================================================================================== */

    /**
     * Add Relationships to the Book.  This is a change log.
     * @param Relationship[] $relationships
     * @throws BookRevisionException
     */
    public function addRelationships(Relationship ...$relationships)
    {
        foreach ($relationships as $relationship) {
            if ($this->hasIndex($relationship)) {
                throw new BookRevisionException('Relationship already exists.');
            }
            $this->relationships[$relationship->getName()] = $relationship;
            // Make sure we don't try to remove an Relationship with the same name later
            if (array_key_exists($relationship->getName(), $this->relationshipsRemoved)) {
                unset($this->relationshipsRemoved[$relationship->getName()]);
            }
        }
    }

    /**
     * Remove Relationships from the Book.  This is a change log.  Doesn't remove the data from the database.
     * @param Relationship[] $relationships
     * @throws BookRevisionException
     */
    public function removeRelationships(Relationship ...$relationships)
    {
        foreach ($relationships as $relationship) {
            if (!$this->hasRelationship($relationship)) {
                throw new BookRevisionException('Relationship doesn\'t exist.');
            }
            $this->indicesRemoved[$this->relationships[$relationship->getName()]->getName()] =
                $this->relationships[$relationship->getName()];
            unset($this->relationships[$relationship->getName()]);
        }
    }

    /**
     * Checks to see if the Relationship name is taken using the Entry or Name
     * @param Relationship|string $relationship
     * @return bool
     */
    public function hasRelationship($relationship)
    {
        return array_key_exists(is_object($relationship)?$relationship->getName():$relationship, $this->relationships);
    }

    /** OWNERSHIP =================================================================================================== */

    /**
     * Get Owner
     * @return string
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set Owner
     * @param string $owner
     * @return $this
     */
    public function setOwner($owner)
    {
        $owner = trim($owner, '\\'); // strip the leading and trailing slashes
        $this->owner = $owner;
        // based on the owner we can map this Migration to a Model+path
        $psr4Paths = json_decode(file_get_contents(APP_PATH .'/composer.json'), true);
        // get the user and project and match to the psr4Paths
        $ownerPieces = explode('\\', $this->owner);
        // format the first two elements into the psr-4 base namespace and use it to retrieve the source
        $src = $psr4Paths['autoload']['psr-4'][sprintf('%s\%s\\', array_shift($ownerPieces), array_shift($ownerPieces))];
        // format the full source, which is the path minus suffixes to the Model
        $this->modelBasePath = $src . implode('/', $ownerPieces);
        $this->modelPath = $this->modelBasePath . '.php';
        $this->modelName = end($ownerPieces);
        return $this;
    }

    /** OPTIONS ===================================================================================================== */

    /**
     * Set options on the Book
     * @param int $options
     */
    public function addOptions($options)
    {
        $this->options |= $options;
    }

    /**
     * Unset options on the Book
     * @param int $options
     */
    public function removeOptions($options)
    {
        $this->options &= ~$options;
    }

    /** HELPERS ===================================================================================================== */

    /**
     * When iterating the Revisions, once the current installed Revision is reached, run this function to make sure
     * deletions, additions and removals from the past don't affect the current write operations.
     */
    public function setCurrent($endTime)
    {
        $this->setStartTime(0);
        $this->setEndTime($endTime);
        foreach ($this->migrate() as $operation) {
            $operation->doUp($this); // Run the up() against the Migration via wrapper
        }

        $this->clearOperations();
        $this->columnsRemoved = [];
        $this->columnsDestroyed = [];
        $this->columnsAdded = [];
        $this->indicesRemoved = [];
        $this->relationshipsRemoved = [];
        $this->removeOptions(self::CREATE + self::DESTROY);
    }

    public function setEndTime($time)
    {
        $this->endTime = $time;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @param mixed $startTime
     * @return $this
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;
        return $this;
    }

    /**
     * Return the viable Entry Types
     */
    public function getEntryTypes()
    {
        $types = [];
        foreach ($this->columns as $column) {
            if (!in_array($type = $column->getClass(), $types)) {
                $types[] = $type;
            }
        }
        if (in_array('Bits', $types)) {
            $types[] = 'Bit';
        }
        return $types;
    }

    /**
     * Return the viable Entry Types
     */
    public function getEntries()
    {
        $out = [];
        foreach ($this->columns as $column) {
            $out[] = $column;
        }
        return $out;
    }

    public function preview()
    {
        $output = [];
        $output['create'] = (bool)($this->options & self::CREATE);
        $output['destroy'] = (bool)($this->options & self::DESTROY);
        $output['owner'] = $this->owner;
        $output['model'] = $this->modelName;
        $output['modelPath'] = $this->modelPath;
        /** @var MigrationTemplate $operation */
        foreach ($this->operations as $operation) {
            $output['operations'][] = $operation->getData();
        }
        foreach ($this->columns as $column) {
            $output['columns'][] = [
                'name'=>$column->getName(),
                'type'=>$column->getClass(),
                'mode'=>(isset($this->columnsAdded[$column->getName()])?'add':'keep')
            ];
        }
        foreach ($this->columnsRemoved as $column) {
            $output['columnsRemoved'][] = [
                'name'=>$column->getName(),
                'type'=>$column->getClass()
            ];
        }
        foreach ($this->columnsDestroyed as $column) {
            $output['columnsDestroyed'][] = [
                'name'=>$column->getName(),
                'type'=>$column->getClass()
            ];
        }
        foreach ($this->methods as $method=>$reflections) {
            if (sizeof($reflections)) {
                $reflection = array_pop($reflections);
                /** @var \ReflectionMethod $reflection */
                $output['methods'][$method] = $reflection->getDeclaringClass()->name;
            }
        }
        echo json_encode($output);
    }

    public function addOperation(MigrationTemplate $operation)
    {
        $this->operations[] = $operation;
    }

    /**
     * Get the current MigrationTemplate
     * @return MigrationTemplate
     */
    public function getCurrentOperation()
    {
        return $this->currentOperation;
    }

    /**
     * Get the current MigrationTemplate
     * @param MigrationTemplate $operation
     * @return $this
     */
    public function setCurrentOperation(MigrationTemplate $operation)
    {
        $this->currentOperation = $operation;
        return $this;
    }

    public function getEndTime()
    {
        if (!$this->endTime) throw new \Exception('End Time not available');
        return $this->endTime;
    }

    public function clearOperations()
    {
        $this->operations = [];
        return $this;
    }

    /**
     * @return string
     */
    public function getModelPath()
    {
        return $this->modelPath;
    }

    public function getModelNamespace()
    {
        return substr($this->owner, 0, strrpos($this->owner, '\\'));
    }

    /**
     * @param string $modelPath
     * @return $this
     */
    public function setModelPath($modelPath)
    {
        $this->modelPath = $modelPath;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getModelName()
    {
        return $this->modelName;
    }

    /**
     * @param mixed $modelName
     */
    public function setModelName($modelName)
    {
        $this->modelName = $modelName;
        return $this;
    }

    /**
     * @return null
     */
    public function getModelLabel()
    {
        return $this->modelLabel?:$this->modelName;
    }

    /**
     * @param null $modelLabel
     */
    public function setModelLabel($modelLabel)
    {
        $this->modelLabel = $modelLabel;
    }

    /**
     * @return array
     */
    public function getMethods()
    {
        $out = [];
        foreach ($this->methods as $method=>$reflections) {
            if (sizeof($reflections)) {
                end($reflections);
                $namespace = key($reflections);
                $reflection = current($reflections);
                /** @var \ReflectionMethod $reflection */
                $out[$namespace] = $reflection;
            }
        }
        return $out;
    }

    /**
     * @return array
     */
    public function getTraits()
    {
        $out = [];
        foreach ($this->traits as $trait=>$traitNames) {
            if (sizeof($traitNames)) {
                end($traitNames);
                $namespace = key($traitNames);
                $traitName = current($traitNames);
                $out[$namespace] = $traitName;
            }
        }
        return $out;
    }
}