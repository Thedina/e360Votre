<?php

namespace eprocess360\v3core\Scheduler;
use eprocess360\v3core\Logger;
use eprocess360\v3core\Scheduler\Tasks\Task;

/**
 * Class CronScheduleProvider
 * Implementation of ScheduleProviderInterface via cron
 * @package eprocess360\v3core\Scheduler
 */
class CronScheduleProvider implements ScheduleProviderInterface
{
    private function schedFormat($val) {
        //return ($val < 0 ? '*' : (string)$val);
        return (string)$val;
    }

    /**
     * Register a task with the backend scheduling system. Implemented via cron by appending a php CLI callback to the
     * crontab for the current user.
     * @param Task $task
     * @throws \Exception
     */
    public function addTask(Task $task) {
        $sched = $task->getSchedule();
        $cron_str = '';
        $cron_str .= $this->schedFormat($sched['minute']).' ';
        $cron_str .= $this->schedFormat($sched['hour']).' ';
        $cron_str .= $this->schedFormat($sched['day']).' ';
        $cron_str .= $this->schedFormat($sched['month']).' ';
        $cron_str .= $this->schedFormat($sched['day-of-week']).' ';
        $cron_str .= "curl http://localhost/scheduler/run/".$task->getName();

        \exec("crontab -l 2>&1", $crontab, $exit);

        if($exit === 0) {
            array_filter($crontab, function ($str) {
                return (bool)strlen($str);
            });
            array_push($crontab, $cron_str);
            $new_crontab = implode("\n", $crontab);
        }
        elseif(preg_match('/no crontab/', $crontab[0])) {
            $new_crontab = $cron_str;
        }
        else {
            throw new \Exception("CronScheduleProvider->addTask(): failed reading crontab.");
        }

        \exec("echo ".escapeshellarg($new_crontab)." | crontab", $out, $exit);

        if($exit !== 0) {
            throw new \Exception("CronScheduleProvider->addTask(): failed writing crontab.");
        }
    }

    /**
     * Clear all tasks from the backend scheduling system. Implemented via cron by clearing all lines referencing a
     * path to the eprocess code directory from the crontab for the current user (and preserving the rest).
     * @throws \Exception
     */
    public function clearTasks() {
        $crontab_exists = true;

        \exec("crontab -l 2>&1", $crontab, $exit);

        if($exit !== 0) {
            if(preg_match('/no crontab/', $crontab[0])) {
                $crontab_exists = false;
            }
            else {
                throw new \Exception("CronScheduleProvider->clearTasks(): failed reading crontab.");
            }
        }

        if($crontab_exists) {
            $new_crontab = [];

            foreach($crontab as $line) {
                if(strlen($line)) {
                    if(!preg_match('/scheduler\/run/', $line)) {
                        $new_crontab[] = $line;
                    }
                }
            }

            $new_crontab = implode("\n", $new_crontab);

            \exec("echo ".escapeshellarg($new_crontab)." | crontab", $out, $exit);

            if($exit !== 0) {
                throw new \Exception("CronScheduleProvider->clearTasks(): failed writing crontab.");
            }
        }
    }
}