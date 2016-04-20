<?php
    namespace eprocess360\v3core\tests\base;
    use eprocess360\v3core\DB;
    use eprocess360\v3core\DB\Exception\MySQLException;
    use eprocess360\v3core\Model;
    use eprocess360\v3core\Keydict\Table;
    require_once APP_PATH . 'eprocess360/v3core/tests/base/PHPUnitArrayTable.php';

    /**
     * Class DBTestBase
     * @package eprocess360\v3core\tests\base
     * Extend DBUnit test class with code for shared mysql connection
     */
    abstract class DBTestBase extends \PHPUnit_Extensions_Database_TestCase
    {
        static private $pdo = NULL;
        private $conn = NULL;

        /**
         * Jumping-off-point to sync schema and base data. Creates tables from
         * Models directory.
         */
        final private function initTestingDB() {
            foreach(glob(APP_PATH."/eprocess360/v3core/src/Model/*.php") as $file) {
                include_once($file);
            }

            foreach(get_declared_classes() as $className) {
                if(strpos($className, "eprocess360\\v3core\\Model\\") === 0) {
                    /** @var Model $className */
                    /** @var Table $table */
                    $table = $className::keydict();
                    $tableName = $table->getRawName();

                    if($tableName !== NULL) {
                        $exists = DB::sql("SHOW TABLES LIKE '{$tableName}'");

                        try {
                            if (!empty($exists)) {
                                $className::dropTable();
                                $className::createTable();
                                echo "table " . $tableName . " recreated\n";
                            } else {
                                $className::createTable();
                                echo "table " . $tableName . " created\n";
                            }
                        }
                        catch(MySQLException $e) {
                            echo $e."\n";
                        }
                    }
                }
            }
        }

        /**
         * Check if schema and base data are up-to-date. Placeholder.
         */
        final private function isDBReady() {
            return false;
        }

        /**
         * Implementation of PHPUnit_Extensions_Database_TestCase->getConnection()
         * @return null|\PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection
         */
        final public function getConnection() {
            if ($this->conn === null) {
                if (self::$pdo == null) {
                    self::$pdo = new \PDO("mysql:host=".MYSQL_HOST.";dbname=".MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD);

                    if(!$this->isDBReady()) {
                        $this->initTestingDB();
                    }
                }
                $this->conn = $this->createDefaultDBConnection(self::$pdo, MYSQL_DATABASE);
            }

            return $this->conn;
        }

        /**
         * Helper for asserting value of project variable
         * @param $key
         * @param $val
         * @param int $idproject
         */
        final public function assertProjectData($key, $val, $idproject = 0) {
            /** TODO broken 'cause project data completely changed redo later */
        }

        /**
         * Create a table object from an array
         * @param $tableName
         * @param $data
         * @return PHPUnitArrayTable
         */
        final public function createArrayTable($tableName, $data) {
            return new PHPUnitArrayTable($tableName, $data);
        }
    }
?>