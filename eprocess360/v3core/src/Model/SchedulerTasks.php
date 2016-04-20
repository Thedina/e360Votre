<?php

namespace eprocess360\v3core\Model;
use eprocess360\v3core\Keydict\Entry\FixedString32;
use eprocess360\v3core\Keydict\Entry\TinyInteger;
use eprocess360\v3core\Model;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyFixedString32;
use eprocess360\v3core\Keydict\Table;


class SchedulerTasks extends Model
{
    public static function keydict() {
        return Table::build(
            PrimaryKeyFixedString32::build('taskName', 'Task Name'),
            FixedString32::build('className', 'Class Name'),
            TinyInteger::build('isRunning', 'Is Running', '0')
        )->setName('SchedulerTasks')->setLabel('Scheduler Tasks');
    }
}