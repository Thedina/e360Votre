<?php

namespace eprocess360\v3core\Scheduler\Tasks;
use eprocess360\v3core\Logger;

/**
 * Class CheckIsSchedulerWorking
 * Task to test/demonstrate that task scheduling is working.
 * @package eprocess360\v3core\Scheduler\Tasks
 */
class CheckIsSchedulerWorking extends Task
{
    protected $taskName = "check_is_scheduler_working";
    protected $schedule = [
        'minute'=>"*/5",
        'hour'=>'*',
        'day'=>'*',
        'month'=>'*',
        'day-of-week'=>'*'
    ];

    public function execute() {
        Logger::log('scheduler triggered at '.\time(), 'scheduler-log-'.date('Y-m-d'));
    }
}