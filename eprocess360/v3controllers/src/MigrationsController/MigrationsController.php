<?php
/**
 * Created by PhpStorm.
 * User: kiranebilak
 * Date: 2/22/16
 * Time: 11:44 AM
 */

namespace eprocess360\v3controllers\MigrationsController;
use eprocess360\v3controllers\MigrationsController\Model\Migrations;
use eprocess360\v3core\Book\Migration;
use eprocess360\v3core\Book\MigrationTemplate;
use eprocess360\v3core\Book\TwigType;
use eprocess360\v3core\Controller\Auth;
use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Persistent;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Keydict;
use eprocess360\v3core\Keydict\Entry\Datetime;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\String;
use eprocess360\v3core\Keydict\Table;

/**
 * Class MigrationController
 * @package eprocess360\v3core\Book
 *
 * Performs the migration functions using Migration files
 */
class MigrationsController extends Controller
{
    use Router, Auth;

    public function routes()
    {
        $this->routes->map('GET', '/migrate', function () {
            $this->migrate();
            die();
        });
        $this->routes->map('GET', '/rollback', function () {
            $this->rollback();
            die();
        });
        $this->routes->map('GET', '/index', function () {
            self::addMigrationClasses();
        });
    }

    public function ready()
    {
        global $twig;
        $twig->addExtension(new TwigType());
        return parent::ready();
    }

    public function migrate()
    {
        self::addMigrationClasses();
        $classes = [];
        // Compile a list of the affected Models first.  We have to do this because the affected Models need to be
        // reparsed from their first Migration so that the proper Model class is built.
        /** @var Table $result */
        foreach (Migrations::each('SELECT className FROM Migrations WHERE timeCommit IS NULL AND flags_0 & 0b1 GROUP BY className ORDER BY timeCreate ASC') as $result) {
            /** @var MigrationTemplate $operation */
            $classes[] = $result->className->get();
        }
        $endTime = self::getLastMigrationTime(); // this forms the basis of the range to migrate
        foreach ($classes as $class) {
            $migration = new Migration($class);
            $migration->setCurrent($endTime);

            // Migrate to NEW
            // All migrations between LAST MIGRATION+1 and TIME() that are not committed
            $migration->setStartTime($endTime+1);
            $migration->setEndTime(time());
            foreach ($migration->migrate(true) as $operation) {
                $operation->doUp($migration);
            }
            $migration->preview();
            $migration->commit(); // writes the Model file and updates the database
        }
    }

    public function rollback()
    {
        $table = Migrations::keydict();
        $endTime = self::getLastMigrationTime(); // this forms the basis of the range to migrate
        $table->timeCommit->set(date(Datetime::format(), $endTime));
        $classes = [];
        // Get the affected Models
        foreach (Migrations::each($sql = "SELECT `className` FROM Migrations WHERE timeCommit = {$table->timeCommit->cleanSleep()} AND flags_0 & 0b10 GROUP BY className ORDER BY idMigration DESC") as $result) {
            /** @var MigrationTemplate $operation */
            $classes[] = $result->className->get();
        }

        foreach ($classes as $class) {
            $migration = new Migration($class);
            $migration->setCurrent($endTime);

            // Rollback from CURRENT
            // All migrations between LAST MIGRATION and TIME() that are committed
            $migration->setStartTime(time());
            foreach ($migration->migrate() as $operation) {
                $operation->doDown($migration);
            }
            $migration->preview();
            $migration->commit(); // writes the Model file and updates the database
        }
    }

    /** Generate a new Migration.  By default, the Migration will go into App/Migrations.  If a Model is specified,
     * it will go into the appropriate Model/Migrations folder.
     * @param string|null $model
     */
    public function create($model = null)
    {

    }

    public static function getLastMigrationTime()
    {
        foreach (Migrations::each($sql = 'SELECT timeCommit FROM Migrations ORDER BY timeCommit DESC LIMIT 1') as
        $result) {
            return strtotime($result->timeCommit->get());
        }
        return 0;
    }

    /**
     * Does a full comparison of the Migration in the database and in the filesystem, then adds the new ones to the
     * database.
     * @throws \eprocess360\v3core\Keydict\Exception\InvalidValueException
     * @throws \eprocess360\v3core\Keydict\Exception\KeydictException
     */
    public static function addMigrationClasses()
    {
        $existing = [];
        foreach (Migrations::each('SELECT * FROM Migrations') as $result) {
            $existing[$result->idMigration->get()] = $result->title->get();
        }
        foreach (self::yieldMigrationClasses() as $file) {
            $className = $file;
            $classNameTarget = substr($className, strrpos($className, '\\')+1);
            $migrationName = $classNameTarget;
            $time = substr($classNameTarget, $f=(strpos($classNameTarget, '_')+1));
            $time = substr($time, 0, strpos($time,'_',strpos($time,'_')+1));
            $classNameTarget = substr($classNameTarget, 0, strpos($classNameTarget, '_'));
            $time = \DateTime::createFromFormat('ymd_His', $time)->format(Datetime::timestamps());
            if (!in_array($migrationName, $existing)) {
                $new = Migrations::keydict();
                $new->title->set($migrationName);
                $new->className->set($classNameTarget);
                $new->file->set($file);
                $new->timeCreate->set($time);
                $new->flags->canUp->set(true);
                $new->flags->canDown->set(true);
                $new->insert();
            }
        }
    }

    /**
     * @return \Generator|Migration
     */
    public static function yieldMigrationClasses()
    {
        foreach(glob(APP_PATH."/eprocess360/v3modules/src/*/Model/Migrations/*.php") as $file) {
            include($file);
        }
        foreach(glob(APP_PATH."/eprocess360/v3controllers/src/*/Model/Migrations/*.php") as $file) {
            include($file);
        }
        foreach(get_declared_classes() as $className) {
            if(strpos($className, "Model\\Migrations\\")) {
                yield $className;
            }
        }
    }

    /**
     * return a list of revisions
     */
    public function getMigrations()
    {

    }
}