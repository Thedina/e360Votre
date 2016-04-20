<?php

namespace eprocess360\v3core\tests\Scheduler;
use eprocess360\v3core\Scheduler\Tasks\Task;
use eprocess360\v3core\Scheduler\ScheduleProviderInterface;


class FakeScheduleProvider implements ScheduleProviderInterface
{
    public function addTask(Task $task) {
        //Do nothing
    }

    public function clearTasks() {
        //Do nothing
    }

}