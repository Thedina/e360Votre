<?php

namespace eprocess360\v3core;


/**
 * Class MemcachedData
 * @package eprocess360\v3core
 * Wrapper around Memcached providing namespacing, exceptions, etc.
 */
class MemcachedData
{
    private static $caches = [];

    private $namespace;
    private $prefixLength;

    public function __construct($namespace) {
        $this->namespace = $namespace;
        $this->prefixLength = strlen($namespace) + 1;
    }

    /**
     * Returns false if key not found. Otherwise failure to get a value
     * produces an exception.
     * @param string $key
     * @param callable $cache_cb
     * @param float $cas_token
     * @return mixed
     * @throws \Exception
     */
    public function get($key, $cache_cb = NULL, &$cas_token = NULL) {
        $mc = MemcachedManager::getMemcached();
        $result = $mc->get($this->namespace.'_'.$key, $cache_cb, $cas_token);

        if($result === false) {
            $code = $mc->getResultCode();
            if($code === \Memcached::RES_NOTFOUND) {
                return false;
            }
            throw new \Exception("MemcachedData->get(): Failed to get key {$key} in namespace {$this->namespace}. Result code {$code}.");
        }

        return $result;
    }

    /**
     * @param array $keys
     * @param array $casTokens
     * @param $flags
     * @return bool|mixed
     * @throws \Exception
     */
    public function getMulti(array $keys, array &$casTokens = [], $flags = 0) {
        $mc = MemcachedManager::getMemcached();

        $namespacedKeys = array_map(function($key) {
            return $this->namespace.'_'.$key;
        }, $keys);

        $result = $mc->getMulti($namespacedKeys, $casTokens, $flags);

        if($result === false) {
            $code = $mc->getResultCode();
            if($code === \Memcached::RES_NOTFOUND) {
                return false;
            }
            $keys = var_export($keys, true);
            throw new \Exception("MemcachedData->get(): Failed to get keys {$keys} in namespace {$this->namespace}. Result code {$code}.");
        }

        $deNamespaced = [];

        foreach($result as $key=>$val) {
            $deNamespaced[substr($key, $this->prefixLength)] = $val;
        }

        return $deNamespaced;
    }

    /**
     * Returns false if key exists. Otherwise failure to store a value
     * produces an exception.
     * @param string $key
     * @param mixed $value
     * @param int $expiration
     * @return bool
     * @throws \Exception
     */
    public function add($key, $value, $expiration = self::EXPR_ONE_DAY) {
        $mc = MemcachedManager::getMemcached();
        $result = $mc->add($this->namespace.'_'.$key, $value, $expiration);

        if($result === false) {
            $code = $mc->getResultCode();
            if($code === \Memcached::RES_NOTSTORED) {
                return false;
            }
            throw new \Exception("MemcachedData->set(): Failed to set key {$key} in namespace {$this->namespace}. Result code {$code}.");
        }

        return $result;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int $expiration
     * @return bool
     * @throws \Exception
     */
    public function set($key, $value, $expiration = self::EXPR_ONE_DAY) {
        $mc = MemcachedManager::getMemcached();
        $result = $mc->set($this->namespace.'_'.$key, $value, $expiration);

        if($result === false) {
            $code = $mc->getResultCode();
            throw new \Exception("MemcachedData->set(): Failed to set key {$key} in namespace {$this->namespace}. Result code {$code}.");
        }

        return $result;
    }

    /**
     * @param array $items
     * @param int $expiration
     * @return bool
     * @throws \Exception
     */
    public function setMulti(array $items, $expiration = self::EXPR_ONE_DAY) {
        $mc = MemcachedManager::getMemcached();
        $namespacedItems = [];

        foreach($items as $key=>$val) {
            $namespacedItems[$this->namespace.'_'.$key] = $val;
        }

        $result = $mc->setMulti($namespacedItems, $expiration);

        if($result === false) {
            $code = $mc->getResultCode();
            $items = var_export($items, true);
            throw new \Exception("MemcachedData->set(): Failed to set {$items} in namespace {$this->namespace}. Result code {$code}.");
        }

        return $result;
    }

    /**
     * @param string $key
     * @param int $time
     * @return bool
     * @throws \Exception
     */
    public function delete($key, $time = 0) {
        $mc = MemcachedManager::getMemcached();
        $result = $mc->delete($this->namespace.'_'.$key, $time);

        if($result === false) {
            $code = $mc->getResultCode();
            throw new \Exception("MemcachedData->set(): Failed to delete key {$key} in namespace {$this->namespace}. Result code {$code}.");
        }

        return $result;
    }

    /**
     * @param array $keys
     * @param int $time
     * @return bool
     * @throws \Exception
     */
    public function deleteMulti(array $keys, $time = 0) {
        $mc = MemcachedManager::getMemcached();

        $namespacedKeys = array_map(function($key) {
            return $this->namespace.'_'.$key;
        }, $keys);

        $result = $mc->delete($namespacedKeys, $time);

        if($result === false) {
            $code = $mc->getResultCode();
            $keys = var_export($keys, true);
            throw new \Exception("MemcachedData->set(): Failed to delete keys {$keys} in namespace {$this->namespace}. Result code {$code}.");
        }

        return $result;
    }

    /**
     * Get an existing memcached namespace or create one if it does not exist
     * @param $namespace
     * @return mixed
     */
    public static function getNamespace($namespace) {
        if(!isset(self::$caches[$namespace])) {
            self::$caches[$namespace] = new MemcachedData($namespace);
        }

        return self::$caches[$namespace];
    }
}