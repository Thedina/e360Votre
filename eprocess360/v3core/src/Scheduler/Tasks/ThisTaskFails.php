<?php

namespace eprocess360\v3core\Scheduler\Tasks;

/**
 * Class ThisTaskFails
 * Task for testing purposes - contains a fatal error
 * @package eprocess360\v3core\Scheduler\Tasks
 */
class ThisTaskFails extends Task
{
    protected $taskName = "this_task_fails";
    protected $schedule = [
        'minute'=>"0",
        'hour'=>'0',
        'day'=>'1',
        'month'=>'*',
        'day-of-week'=>'*'
    ];

    public function execute() {
        call_user_func('NotARealFunction');
    }
}