<?php

namespace eprocess360\v3core;

/**
 * Class GoogleAPIManager
 * Provides access to a connection to the Google Service API
 * @package eprocess360\v3core
 */
class GoogleAPIManager
{
    private static $client;
    private static $cred;

    /**
     * Return a Google API client object, initializing the connection if
     * needed.
     * @return \Google_Client
     * @throws \Exception
     */
    public static function getAPIClient() {
        global $pool;

        if(!is_object(GoogleAPIManager::$client)) {
            self::$client = new \Google_Client();
            self::$client->setApplicationName($pool->SysVar->get('googleAppName'));
            self::$client->setDeveloperKey($pool->SysVar->get('googleAPIKey'));
            self::$client->setAuthConfig($pool->SysVar->get('googleOAuthPresetFile'));
            self::$client->setScopes([
                'https://www.googleapis.com/auth/devstorage.full_control'
            ]);
        }

        if(self::$client->isAccessTokenExpired()) {
            self::$client->refreshTokenWithAssertion();
        }

        return self::$client;
    }
}