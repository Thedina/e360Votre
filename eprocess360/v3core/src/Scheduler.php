<?php

namespace eprocess360\v3core;
use eprocess360\v3core\Scheduler\Tasks\Task;
use eprocess360\v3core\Scheduler\ScheduleProviderInterface;
use eprocess360\v3core\Scheduler\SchedulerException;

/**
 * Class Scheduler
 * Sets up and dispatches scheduled/recurring functionality
 * @package eprocess360\v3core\Scheduler
 */
class Scheduler
{
    /**
     * @var ScheduleProviderInterface $provider
     */
    private $provider;

    /**
     * @var Task $curTask
     */
    private $curTask;

    public function __construct($provider) {
        $this->provider = $provider;
    }

    /**
     * @return Task
     */
    public function getCurTask()
    {
        return $this->curTask;
    }

    /**
     * @return ScheduleProviderInterface
     */
    public function getScheduleProvider()
    {
        return $this->provider;
    }

    /**
     * @param ScheduleProviderInterface $provider
     */
    public function setScheduleProvider($provider)
    {
        $this->provider = $provider;
    }

    /**
     * Set up scheduled tasks from the specified task classes.
     * @param Array $taskClasses
     * @throws \eprocess360\v3core\Scheduler\SchedulerException
     */
    public function initTasks(Array $taskClasses) {
        $this->clearTasks();

        foreach($taskClasses as $tc) {
            $newTask = Task::factoryNew($tc);
            if ($newTask->create()) {
                $this->provider->addTask($newTask);
            }
        }
    }

    /**
     * Clear all tasks from the provider and the database.
     */
    public function clearTasks() {
        $this->provider->clearTasks();
        Task::deleteAll();
    }

    /**
     * Execute the corresponding functions for the task identified by $name.
     * @param $name
     * @throws \eprocess360\v3core\Scheduler\SchedulerException
     */
    public function dispatch($name) {
        set_error_handler([$this, 'error_handler']);
        register_shutdown_function([$this, 'shutdown_handler']);

        $this->curTask = Task::getByName($name);
        if($this->curTask !== NULL && !$this->curTask->isRunning()) {
            $this->curTask->setRunning(true);
            try {
                $this->curTask->execute();
            }
            catch(\Exception $e) {
                $this->curTask->setRunning(false);
                throw new SchedulerException("Scheduler->dispatch(): An exception occurred during the execution of task '{$this->curTask->getName()}'. {$e->getMessage()}");
            }
            $this->curTask->setRunning(false);
        }
    }

    /**
     * Custom error handler to convert errors to exceptions and make sure
     * failed tasks do not stay marked as running in the DB.
     * @param $errno
     * @param $errstr
     * @return bool
     * @throws \Exception
     */
    public function error_handler($errno, $errstr) {
        switch ($errno) {
            case E_NOTICE:
                break;
            default:
                $this->curTask->setRunning(false);
                throw new \Exception($errstr);
                break;
        }
        return true;
    }

    /**
     * Custom shutdown handler in case execution fails in a manner even the
     * error handler can't handle.
     */
    public function shutdown_handler() {
        $error = error_get_last();
        switch ($error['type']) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_PARSE:
                /** TODO in case of catastrophic failure - email, log to file, ideally somehow make this (indirectly?) clear running tasks from DB. */
                break;
        }
    }

    /**
     * Instantiate a Scheduler with provider backend specified in config.
     * This helper could be dispensed with entirely and the dependency
     * injection done from wherever as in the unit tests but this is a safe
     * place for this setup to happen for now.
     *
     * @param $mode
     * @return Scheduler
     */
    public static function initScheduler() {
        global $pool;

        $providerClass = 'eprocess360\\v3core\\Scheduler\\'.$pool->SysVar->get('scheduleProvider');

        return new self(new $providerClass());
    }

    /**
     * Get an array of available Task class names. If $fullNamespace is set,
     * get fully qualified class names
     * @param bool|false $fullNamespace
     * @return array
     */
    public static function getAvailableTasks($fullNamespace = false) {
        $taskNames = [];

        foreach(glob(APP_PATH."/eprocess360/v3core/src/Scheduler/Tasks/*.php") as $file) {
            include_once($file);
        }

        foreach(get_declared_classes() as $className) {
            if(strpos($className, "eprocess360\\v3core\\Scheduler\\Tasks\\") === 0 && get_parent_class($className) == "eprocess360\\v3core\\Scheduler\\Tasks\\Task") {
                $taskNames[] = ($fullNamespace ? $className : substr($className, strrpos($className, '\\') + 1));
            }
        }

        return $taskNames;
    }
}