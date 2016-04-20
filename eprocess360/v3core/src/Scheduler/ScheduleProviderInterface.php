<?php

namespace eprocess360\v3core\Scheduler;
use eprocess360\v3core\Scheduler\Tasks\Task;

/**
 * Interface ScheduleProviderInterface
 * Scheduling backend. Adds and removes scheduled HTTP requests to
 * localhost/e360-scheduled/:task_name
 * @package eprocess360\v3core\Scheduler
 */
interface ScheduleProviderInterface
{
    /**
     * Register a task with the backend scheduling system. Must set up a
     * recurring HTTP GET to localhost/e360-scheduled/:task_name
     * @param Task $task
     */
    public function addTask(Task $task);

    /**
     * Clear all tasks from the backend scheduling system. Must leave non-ep360
     * scheduling untouched.
     */
    public function clearTasks();
}