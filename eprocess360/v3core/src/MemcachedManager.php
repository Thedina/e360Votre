<?php

namespace eprocess360\v3core;

/**
 * Class MemcachedManager
 * @package eprocess360\v3core
 * A singleton-style wrapper for a memcached instance
 */
class MemcachedManager
{
    const DEFAULT_PORT = 11211;
    const EXP_ONE_DAY = 86400;
    private static $mc = NULL;

    /**
     * Return the Memcached object, initializing a new instance/connection if
     * it does not exist.
     * @return \Memcached|null
     */
    public static function getMemcached() {
        if(!is_object(self::$mc)) {
            self::$mc = new \Memcached();
            self::$mc->addServer('127.0.0.1', self::DEFAULT_PORT);
        }

        return self::$mc;
    }
}