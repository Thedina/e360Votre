<?php

namespace eprocess360\v3core\Scheduler\Tasks;
use eprocess360\v3core\CloudStorage;
use eprocess360\v3core\Files\File;
use eprocess360\v3core\Logger;

/**
 * Class MoveUploadedToCloud
 * Task to copy uploads from local storage to cloud storage
 * @package eprocess360\v3core\Scheduler\Tasks
 */
class MoveUploadedToCloud extends Task
{
    const FILES_PER_RUN = 8;
    const INTERVAL_SECONDS = 300;
    protected $taskName = "move_uploaded_to_cloud";
    protected $schedule = [
        'minute'=>'*/5',
        'hour'=>'*',
        'day'=>'*',
        'month'=>'*',
        'day-of-week'=>'*'
    ];

    /**
     * If there are local uploads without a cloud storage timestamp, copy them
     * to cloud storage and give them a cloud timestamp. If more than
     * FILES_PER_RUN copied or more than INTERVAL_SECONDS elapsed, wait for the
     * next iteration of the job just to avoid overloading anything.
     */
    public function execute() {
        set_time_limit(600);
        $cs = CloudStorage::getCloudStorage();
        $numFiles = 1;
        $startTime = \time();

        $local_uploads = File::getLocalOnly();

        foreach($local_uploads as $u) {
            /**
             * @var File $u
             */
            $fullpath = $u->getPath();
            $ulpath = substr($fullpath, strpos($fullpath, 'uploads'));

            $cs->insertFromFile($fullpath, $ulpath, true);
            $u->setCloudDatetime(date('Y-m-d H:i:s'));

            if((++$numFiles > self::FILES_PER_RUN) || (\time() > $startTime + self::INTERVAL_SECONDS)) {
                break;
            }
        }
    }
}