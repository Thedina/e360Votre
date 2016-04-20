<?php
//CREATE TABLE SchedulerTasks (taskName VARCHAR(32) PRIMARY KEY NOT NULL, className VARCHAR(32), isRunning TINYINT(1) DEFAULT 0 NOT NULL);

namespace eprocess360\v3core\Scheduler\Tasks;

use eprocess360\v3core\DB;
use eprocess360\v3core\Scheduler\SchedulerException;

/**
 * Class Task
 * Base class for scheduler tasks
 * @package eprocess360\v3core\Scheduler\Tasks
 */
abstract class Task
{
    protected $taskName;
    protected $className;
    protected $running;
    protected $schedule = [
        'minute'=>-1,
        'hour'=>-1,
        'day'=>-1,
        'month'=>-1,
        'day-of-week'=>-1
    ];

    public function __construct($isRunning = 0) {
        $this->running = $isRunning;
        $this->className = (new \ReflectionClass($this))->getShortName();
    }

    /**
     * Execute the function of this Task
     */
    public function execute() {
    }

    /**
     * Insert a DB entry for this Task
     * @return mixed
     */
    final public function create() {
        DB::insert("INSERT INTO SchedulerTasks (`taskName`, `className`, `isRunning`) VALUES ('".DB::cleanse($this->taskName)."','".DB::cleanse($this->className)."',0)");
        return $this->taskName;
    }

    /**
     * Delete the DB entry for this Task
     * @throws eprocess360\v3core\DB\Exception\MySQLException
     */
    final public function delete() {
        DB::sql("DELETE FROM SchedulerTasks WHERE `taskName` = '".DB::cleanse($this->taskName)."'");
    }

    /**
     * Get the taskName of this task
     * @return mixed
     */
    final public function getName() {
        return $this->taskName;
    }

    /**
     * Get the stored class taskName for this Task
     * @return string
     */
    final public function getClassName() {
        return $this->className;
    }

    /**
     * Get the running flag from this Task
     * @return int
     */
    final public function isRunning() {
        return $this->running;
    }

    /**
     * Check in the DB if this task is running and update the Task accordingly.
     * @return int
     * @throws \eprocess360\v3core\Scheduler\SchedulerException
     * @throws eprocess360\v3core\DB\Exception\MySQLException
     */
    final public function checkisRunning() {
        $r = DB::sql("SELECT * FROM SchedulerTasks WHERE `taskName` = '".DB::cleanse($this->taskName)."'");
        if(!empty($r)) {
            $r = $r[0];
            $this->running = (int)$r['isRunning'];
            return $this->running;
        }
        else {
            throw new SchedulerException("Task->checkisRunning(): cannot find scheduler_task entry in database.");
        }
    }

    /**
     * Set the running flag for a task in this Task *and* write through to the DB
     * @param $value
     * @throws eprocess360\v3core\DB\Exception\MySQLException
     */
    final public function setRunning($value) {
        $this->running = (int)$value;
        DB::sql("UPDATE SchedulerTasks SET isRunning = ".(int)$this->running. " WHERE `taskName` = '".DB::cleanse($this->taskName)."'");
    }

    /**
     * Return the scheduling information for this Task
     * @return array
     */
    public function getSchedule() {
        return $this->schedule;
    }

    /**
     * Delete all tasks from the database
     * @throws eprocess360\v3core\DB\Exception\MySQLException
     */
    public static function deleteAll() {
        DB::sql("DELETE FROM SchedulerTasks WHERE 1");
    }

    /**
     * Get all Tasks from the database
     * @return array
     * @throws \Exception
     * @throws eprocess360\v3core\DB\Exception\MySQLException
     */
    public static function getAll() {
        $result = \_a(DB::sql("SELECT * FROM SchedulerTasks"));
        $tasks = [];
        foreach($result as $r) {
            $tasks[] = self::factoryNew($r['className'], (int)$r['isRunning']);
        }
        return $tasks;
    }

    /**
     * Get the Task (subclass) for the task identified by $taskName
     * @param $taskName
     * @return Task
     * @throws \Exception
     * @throws eprocess360\v3core\DB\Exception\MySQLException
     */
    public static function getByName($taskName) {
        $r = DB::sql("SELECT * FROM SchedulerTasks WHERE `taskName` = '".DB::cleanse($taskName)."'");
        if(!empty($r)) {
            $r = $r[0];
            return self::factoryNew($r['className'], (int)$r['isRunning']);
        }
        return NULL;
    }

    /**
     * Return a Task subclass corresponding to $className
     * @param $className
     * @param int $isRunning
     * @return Task
     * @throws \eprocess360\v3core\Scheduler\SchedulerException
     */
    public static function factoryNew($className, $isRunning = 0) {
        $className = 'eprocess360\\v3core\\Scheduler\\Tasks\\'.$className;

        if(class_exists($className)) {
            return new $className($isRunning);
        }
        else {
            throw new SchedulerException("Task::factoryNew(): cannot find class {$className}.");
        }
    }
}