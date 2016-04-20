<?php

namespace eprocess360\v3core\Scheduler\Tasks;
use eprocess360\v3core\MailManager;

/**
 * Class MailQueueWorker
 * Sends emails from the email queue
 * @package eprocess360\v3core\Scheduler\Tasks
 */
class MailQueueWorker extends Task
{
    protected $taskName = "mail_queue_worker";
    protected $schedule = [
        'minute'=>"*",
        'hour'=>'*',
        'day'=>'*',
        'month'=>'*',
        'day-of-week'=>'*'
    ];

    public function execute() {
        MailManager::retryQueueUnsent();
    }
}