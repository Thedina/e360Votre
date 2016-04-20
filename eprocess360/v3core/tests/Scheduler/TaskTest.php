<?php

namespace eprocess360\v3core\tests\Scheduler;
use eprocess360\v3core\tests\base\DBTestBase;
use eprocess360\v3core\Scheduler\Tasks\Task;


class TaskTest extends DBTestBase
{
    const DUMMY_TASK_ENTRY = [
        'taskName'=>'check_is_scheduler_working',
        'className'=>'CheckIsSchedulerWorking',
        'isRunning'=>'0'
    ];

    const MOVE_UPLOADED_ENTRY = [
        'taskName'=>'move_uploaded_to_cloud',
        'className'=>'MoveUploadedToCloud',
        'isRunning'=>'1'
    ];

    const CLEANUP_LOCAL_ENTRY = [
        'taskName'=>'cleanup_local_uploads',
        'className'=>'CleanupLocalUploads',
        'isRunning'=>'0'
    ];

    public function getDataSet() {
        return $this->createArrayDataSet([
            'SchedulerTasks'=>[
                self::DUMMY_TASK_ENTRY,
                self::MOVE_UPLOADED_ENTRY
            ]
        ]);
    }

    public function testFactoryNew() {
        $entry = self::DUMMY_TASK_ENTRY;

        $task = Task::factoryNew($entry['className']);
        $this->assertEquals($task->getName(), $entry['taskName']);
    }

    public function testGetByName() {
        $entry = self::DUMMY_TASK_ENTRY;

        $task = Task::getByName($entry['taskName']);
        $this->assertEquals($task->getName(), $entry['taskName']);
    }

    public function testGetAll() {
        $entries = [
            self::DUMMY_TASK_ENTRY,
            self::MOVE_UPLOADED_ENTRY
        ];

        $tasks = Task::getAll();
        $this->assertCount(2, $tasks);
        $this->assertEquals($tasks[0]->getName(), $entries[0]['taskName']);
        $this->assertEquals($tasks[1]->getName(), $entries[1]['taskName']);
    }

    public function testDeleteAll() {
        $this->assertEquals(2, $this->getConnection()->getRowCount('SchedulerTasks'));

        Task::deleteAll();
        $this->assertEquals(0, $this->getConnection()->getRowCount('SchedulerTasks'));
    }

    public function testCheckisRunning() {
        $entry = self::MOVE_UPLOADED_ENTRY;

        $task = Task::getByName($entry['taskName']);
        $this->assertEquals($task->checkisRunning(), $entry['isRunning']);
    }

    public function testSetRunning() {
        $entry = self::DUMMY_TASK_ENTRY;
        $entry['isRunning'] = '1';

        $task = Task::getByName($entry['taskName']);
        $task->setRunning(true);

        $this->assertEquals($task->isRunning(), 1);

        $resultTable = $this->getConnection()->createQueryTable(
            'SchedulerTasks', "SELECT * FROM SchedulerTasks WHERE `taskName` = '{$entry['taskName']}'"
        );

        $expectedTable = $this->createArrayTable('SchedulerTasks', [
            $entry
        ]);

        $this->assertTablesEqual($expectedTable, $resultTable);
    }

    public function testDelete() {
        $entry = self::MOVE_UPLOADED_ENTRY;

        $this->assertEquals(2, $this->getConnection()->getRowCount('SchedulerTasks'));

        $expectedBefore = $this->createArrayTable('SchedulerTasks', [
            self::DUMMY_TASK_ENTRY,
            self::MOVE_UPLOADED_ENTRY
        ]);

        $resultTable = $this->getConnection()->createQueryTable(
            'SchedulerTasks', 'SELECT * FROM SchedulerTasks'
        );

        $this->assertTablesEqual($expectedBefore, $resultTable);

        $task = Task::getByName($entry['taskName']);
        $task->delete();

        $this->assertEquals(1, $this->getConnection()->getRowCount('SchedulerTasks'));

        $expectedAfter = $this->createArrayTable('SchedulerTasks', [
            self::DUMMY_TASK_ENTRY
        ]);

        $resultTable = $this->getConnection()->createQueryTable(
            'SchedulerTasks', 'SELECT * FROM SchedulerTasks'
        );

        $this->assertTablesEqual($expectedAfter, $resultTable);
    }

    public function testCreate() {
        $entry = self::CLEANUP_LOCAL_ENTRY;

        $this->assertEquals(2, $this->getConnection()->getRowCount('SchedulerTasks'));

        $expectedBefore = $this->createArrayTable('SchedulerTasks', [
            self::DUMMY_TASK_ENTRY,
            self::MOVE_UPLOADED_ENTRY
        ]);

        $resultTable = $this->getConnection()->createQueryTable(
            'SchedulerTasks', 'SELECT * FROM SchedulerTasks'
        );

        $this->assertTablesEqual($expectedBefore, $resultTable);

        $task = Task::factoryNew($entry['className']);
        $task->create();

        $this->assertEquals(3, $this->getConnection()->getRowCount('SchedulerTasks'));

        $expectedAfter = $this->createArrayTable('SchedulerTasks', [
            self::DUMMY_TASK_ENTRY,
            self::CLEANUP_LOCAL_ENTRY,
            self::MOVE_UPLOADED_ENTRY
        ]);

        $resultTable = $this->getConnection()->createQueryTable(
            'SchedulerTasks', 'SELECT * FROM SchedulerTasks'
        );

        $this->assertTablesEqual($expectedAfter, $resultTable);
    }
}