<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 6/4/2015
 * Time: 19:39
 */

namespace eprocess360\v3core;

use eprocess360\v3core\Keydict;
use Exception;

/**
 * Class DB
 * @package eprocess360\v3core
 */
class DB
{
    const VARCHAR = 'VARCHAR';
    const TINYINT = 'TINYINT';
    const BIGINT = 'BIGINT';
    const INT = 'INT';
    const CHAR = 'CHAR';
    const TEXT = 'TEXT';
    const BIT = 'BIT';
    const SMALLINT = 'SMALLINT';
    const DATETIME = 'DATETIME';
    const FLOAT = 'FLOAT';
    const DECIMAL = 'DECIMAL';

    /**
     * Establish a connection
     * @return \mysqli
     */
    public static function connection()
    {
        global $databaseConnection;
        if (is_object($databaseConnection))
            return $databaseConnection;
        return $databaseConnection = new \mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DATABASE);
    }

    /**
     * Cleanse a string
     * @param $string
     * @return string
     * @throws \Exception
     */
    public static function cleanse($string)
    {
        switch (gettype($string)) {
            case 'object':
                throw new \Exception("DB::cleanse() cannot accept objects.");
            case 'array':
                if ($string==[]) {
                    return '';
                }
                throw new \Exception("DB::cleanse() cannot accept arrays.");
            case 'int':
            case 'float':
                return $string;
            break;
        }
        return mysqli_real_escape_string(self::connection(), $string);

    }

    /**
     * Get the MySQL query statistics
     * @return mixed
     */
    public static function getStats()
    {
        global $sqlStats;
        return $sqlStats;
    }

    /**
     * Execute SQL against the database and return associative array
     *
     * @param string $sql
     * @param bool $mode ASSOC or NUM (ASSOC default)
     * @return array|null
     * @throws MySQLException
     */
    public static function sql($sql, $mode = false)
    {
        //echo $sql.PHP_EOL;
        global $sql_stats;
        if (!is_array($sql_stats)) {
            $sql_stats = [
                'query_count' => 0,
                'query_time' => 0,
                'queries' => []
            ];
        }
        $start = Toolbox::microtimeFloat();
        $result = self::connection()->query($sql);
        $end = Toolbox::microtimeFloat();
        $total = $end - $start;

        $sql_stats['query_count']++;
        $sql_stats['query_time'] += $total;
        $trace = debug_backtrace();
        $caller = $trace[1];
        $sql_stats['queries'][] = [
            'query' => $sql,
            'time' => $total,
            'called_by' => (isset($caller['class']) ? $caller['class'] : '') . '\\' . $caller['function'] . '():' . $caller['line'],
            'error' => [
                'errno' => self::connection()->errno,
                'message' => self::connection()->error
            ]
        ];

        $pool = Pool::getInstance();
        // Debugging for SQL
        $dbstats = function () {
            ob_start();
            var_dump(DB::getStats());
            $return = ob_get_contents();
            ob_end_clean();
            return $return;
        };
        $pool->add($dbstats(), 'DBStats');
        if (self::connection()->errno) {
            throw new Exception(self::connection()->error, self::connection()->errno);
        }
        if (!is_bool($result)) {

            $out = [];
            while ($row = $result->fetch_array(!$mode ? MYSQLI_ASSOC : MYSQLI_NUM)) {
                $out[] = $row;
            }
            return $out;
        }
        return null;
    }

    /**
     * Execute SQL against database and return insert id
     *
     * @param string $sql SQL Query
     * @return int
     */
    public static function insert($sql)
    {
        self::sql($sql);
        return mysqli_insert_id(self::connection());
    }

    /**
     * Get the last insert id
     * @return int|string
     */
    public static function iid()
    {
        return mysqli_insert_id(self::connection());
    }

}