<?php
/**
 * Created by PhpStorm.
 * User: Jacob
 * Date: 2/1/2016
 * Time: 6:24 PM
 */

namespace eprocess360\v3core;

/**
 * Class MemcachedArray
 * @package eprocess360\v3core
 * Memcached array wrapper (serializing one namespace <-> one key)
 */
class MemcachedArray
{
    const ALL_KEY_PREFIX = 'ARR_';

    private static $caches = [];

    private $namespace;
    private $expiration;
    private $loaded;
    private $data = [];

    /**
     * @param string $namespace
     */
    public function __construct($namespace, $expiration = MemcachedManager::EXP_ONE_DAY) {
        $this->namespace = $namespace;
        $this->expiration = $expiration;
        $this->loaded = false;
    }

    /**
     * @param \Memcached $mc
     * @throws \Exception
     */
    public function init(\Memcached $mc) {
        $result = $mc->add(self::ALL_KEY_PREFIX.$this->namespace, serialize([]));

        if($result === false) {
            $code = $mc->getResultCode();
            throw new \Exception("MemcachedArray->init(): could not add memcached key ".self::ALL_KEY_PREFIX.$this->namespace. ". Response code: {$code}");
        }
    }

    /**
     * @param Callable|null $cacheCallback
     * @throws \Exception
     */
    public function load($cacheCallback = NULL) {
        $mc = MemcachedManager::getMemcached();
        $result = $mc->get(self::ALL_KEY_PREFIX.$this->namespace, $cacheCallback);

        if($result !== false) {
            $this->data = unserialize($result);
            $this->loaded = true;
        }
        else {
            $code = $mc->getResultCode();
            if($code === \Memcached::RES_NOTFOUND) {
                $this->init($mc);
            }
            else {
                throw new \Exception("MemcachedArray->load(): could not get memcached key " . self::ALL_KEY_PREFIX . $this->namespace . ". Response code: {$code}");
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function writeBack() {
        $mc = MemcachedManager::getMemcached();
        $result = $mc->set(self::ALL_KEY_PREFIX.$this->namespace, serialize($this->data), $this->expiration);

        if($result === false) {
            $code = $mc->getResultCode();
            throw new \Exception("MemcachedArray->load(): could not set memcached key ".self::ALL_KEY_PREFIX.$this->namespace. ". Response code: {$code}");
        }
    }

    /**
     * Delete this namespace from Memcached entirely
     * @param int $time
     * @throws \Exception
     */
    public function delete($time = 0) {
        $mc = MemcachedManager::getMemcached();

        $result = $mc->delete($time);

        if($result === false) {
            $code = $mc->getResultCode();
            throw new \Exception("MemcachedArray->load(): could not set memcached key ".self::ALL_KEY_PREFIX.$this->namespace. ". Response code: {$code}");
        }
    }

    /**
     * Clears all keys from this namespace
     * @throws \Exception
     */
    public function clear() {
        $this->data = [];
        $this->writeBack();
    }

    /**
     * Set expiration time (in seconds)
     * @param int $expiration
     */
    public function setExpiration($expiration) {
        $this->expiration = $expiration;
    }

    /**
     * @param $expiration
     * @return int
     */
    public function getExpiration($expiration) {
        return $this->expiration;
    }

    /**
     * @param $key
     * @param bool|false $forceRefresh
     * @return mixed
     * @throws \Exception
     */
    public function get($key, $forceRefresh = false) {
        if(!$this->loaded || $forceRefresh) {
            $this->load();
        }

        if(!array_key_exists($key, $this->data)) {
            throw new \Exception("MemcachedArray->load(): cannot find key {$key} in namespace {$this->namespace}.");
        }

        return $this->data[$key];
    }

    /**
     * @param bool|false $forceRefresh
     * @return array
     * @throws \Exception
     */
    public function getAll($forceRefresh = false) {
        if(!$this->loaded || $forceRefresh) {
            $this->load();
        }

        return $this->data;
    }

    /**
     * @param $key
     * @param bool|false $forceRefresh
     * @return bool
     * @throws \Exception
     */
    public function exists($key, $forceRefresh = false) {
        if(!$this->loaded || $forceRefresh) {
            $this->load();
        }

        return array_key_exists($key, $this->data);
    }

    /**
     * @param $key
     * @param $value
     * @param bool|false $forceRefresh
     * @throws \Exception
     */
    public function set($key, $value, $forceRefresh = false) {
        if(!$this->loaded || $forceRefresh) {
            $this->load();
        }

        $this->data[$key] = $value;
        $this->writeBack();
    }

    /**
     * @return bool
     */
    public function beenLoaded() {
        return $this->loaded;
    }

    /**
     * Get an existing memcached namespace or create one if it does not exist.
     * @param string $namespace
     * @param int|null $expiration
     * @return mixed
     */
    public static function getNamespace($namespace, $expiration = NULL) {
        if(!isset(self::$caches[$namespace])) {
            self::$caches[$namespace] = new self($namespace, ($expiration !== NULL ? $expiration : MemcachedManager::EXP_ONE_DAY));
        }

        if($expiration !== NULL && $expiration !== self::$caches[$namespace]->getExpiration()) {
            self::$caches[$namespace]->setExpiration($expiration);
        }

        return self::$caches[$namespace];
    }
}