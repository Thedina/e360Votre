<?php

namespace eprocess360\v3core\Scheduler\Tasks;
use eprocess360\v3core\CloudStorage;
use eprocess360\v3core\Files\File;
use eprocess360\v3core\Logger;

/**
 * Class CleanupLocalUploads
 * Task to remove uploads from local storage if they were moved to the cloud >= 24 hrs ago
 * @package eprocess360\v3core\Scheduler\Tasks
 */
class CleanupLocalUploads extends Task
{
    const MIN_DAYS_OLD = 1;
    protected $taskName = "cleanup_local_uploads";
    protected $schedule = [
        'minute'=>'0',
        'hour'=>'0',
        'day'=>'*',
        'month'=>'*',
        'day-of-week'=>'*'
    ];

    /**
     * If there are local uploads with cloud time stamp at least MIN_DAYS_OLD,
     * double check that they have been copied to cloud storage then clear them
     * from local storage.
     */
    public function execute() {
        $cs = CloudStorage::getCloudStorage();

        $moved_uploads = File::getLocalExpired(self::MIN_DAYS_OLD);

        foreach($moved_uploads as $u) {
            /**
             * @var File $u
             */
            $ulpath = $u->getPath();
            $cloudpath = ltrim(substr($ulpath, strpos($ulpath, 'uploads')), '/');
            $exists = true;

            try {
                $cs->exists($cloudpath);
            }
            catch(\Exception $e) {
                $exists = false;
            }

            if($exists && file_exists($ulpath)) {
                unlink($ulpath);
                $u->setIsLocal(false);
            }
            else {
                $u->setCloudDatetime(NULL);
            }
        }
    }
}