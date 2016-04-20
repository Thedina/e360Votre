<?php

namespace eprocess360\v3core\tests\Scheduler;
require_once 'eprocess360/v3core/tests/Scheduler/FakeScheduleProvider.php';
use eprocess360\v3core\tests\base\DBTestBase;
use eprocess360\v3core\Scheduler;
use eprocess360\v3core\Scheduler\Tasks\Task;
use eprocess360\v3core\Scheduler\SchedulerException;


class SchedulerTest extends DBTestBase
{
    const DUMMY_TASK_ENTRY = [
        'taskName'=>'check_is_scheduler_working',
        'className'=>'CheckIsSchedulerWorking',
        'isRunning'=>'0'
    ];

    public function getDataSet() {
        return $this->createArrayDataSet([
            'SchedulerTasks'=>[
                self::DUMMY_TASK_ENTRY
            ]
        ]);
    }

    public function testClearTasks() {
        $scheduler = new Scheduler(new FakeScheduleProvider());

        $resultTable = $this->getConnection()->createQueryTable(
            'SchedulerTasks', 'SELECT * FROM SchedulerTasks'
        );

        $expectedTable = $this->createArrayTable('SchedulerTasks', [
            self::DUMMY_TASK_ENTRY
        ]);

        $this->assertEquals(1, $this->getConnection()->getRowCount('SchedulerTasks'));
        $this->assertTablesEqual($resultTable, $expectedTable);

        $scheduler->clearTasks();

        $this->assertEquals(0, $this->getConnection()->getRowCount('SchedulerTasks'));
    }

    public function testInitTasks() {
        $scheduler = new Scheduler(new FakeScheduleProvider());

        $scheduler->clearTasks();

        $this->assertEquals(0, $this->getConnection()->getRowCount('SchedulerTasks'));

        $scheduler->initTasks(['CheckIsSchedulerWorking']);

        $resultTable = $this->getConnection()->createQueryTable(
            'SchedulerTasks', 'SELECT * FROM SchedulerTasks'
        );

        $expectedTable = $this->createArrayTable('SchedulerTasks', [
            self::DUMMY_TASK_ENTRY
        ]);

        $this->assertEquals(1, $this->getConnection()->getRowCount('SchedulerTasks'));
        $this->assertTablesEqual($resultTable, $expectedTable);
    }

    public function testErrorHandling() {
        $scheduler = new Scheduler(new FakeScheduleProvider());

        $scheduler->initTasks(['ThisTaskFails']);

        $exception = false;

        try {
            $scheduler->dispatch('this_task_fails');
        }
        catch(SchedulerException $e) {
            $exception = true;
        }

        $this->assertEquals($exception, true);
        $task = Task::getByName('this_task_fails');
        $this->assertEquals($task->checkisRunning(), 0);
    }
}