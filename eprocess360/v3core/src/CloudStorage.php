<?php

namespace eprocess360\v3core;
use eprocess360\v3core\CloudStorage\CloudStorageAdapterInterface;
use eprocess360\v3core\CloudStorage\GoogleCloudStorageAdapter;

/**
 * Class CloudStorage
 * Provides access to appropriate cloud storage implementation for current configuration.
 * @package eprocess360\v3core
 */
class CloudStorage {

    /**
     * @var CloudStorageAdapterInterface $csa
     */
    private static $csa;

    /**
     * Set up cloud storage adapter if necessary and return.
     * @return CloudStorageAdapterInterface
     * @throws /Exception
     */
    public static function getCloudStorage() {
        global $pool;
        if(!is_object(self::$csa)) {
            switch($pool->SysVar->get('cloudStorageProvider')) {
                case 'Google':
                    $client = GoogleAPIManager::getAPIClient();
                    return new GoogleCloudStorageAdapter($client, $pool->SysVar->get('googleUploadBucket'));
            }
        }
        return self::$csa;
    }
}