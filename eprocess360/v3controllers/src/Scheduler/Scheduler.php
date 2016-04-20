<?php

namespace eprocess360\v3controllers\Scheduler;

use eprocess360\v3core\Controller\Controller;
use eprocess360\v3core\Controller\Router;
use eprocess360\v3core\Logger;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Class Scheduler
 * Controller providing point-of-entry for scheduler callbacks
 * @package eprocess360\v3controllers\Scheduler
 */
class Scheduler extends Controller
{
    use Router;

    public function routes()
    {
        $this->routes->map('GET', '/run/[*:task]', function ($task) {
            try {
                $this->runSchedulerTask($task);
            }
            catch(\Exception $e) {
                $this->handleException($e, $task);
            }
        });
    }

    public function runSchedulerTask($task) {
        if($_SERVER['REMOTE_ADDR'] !== '127.0.0.1') {
            throw new \Exception("runSchedulerTask():: request from non-local host ".$_SERVER['REMOTE_ADDR']);
        }

        $scheduler = \eprocess360\v3core\Scheduler::initScheduler();
        $scheduler->dispatch($task);
        die();
    }

    public function handleException(\Exception $e, $task) {
        Logger::log($e->getMessage(), 'scheduler-log-'.date('Y-m-d'));
        die();
    }
}