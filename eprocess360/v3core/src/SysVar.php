<?php
/**
 * Created by PhpStorm.
 * User: kira
 * Date: 7/30/2015
 * Time: 11:13 AM
 */

namespace eprocess360\v3core;


use Exception;

class SysVar
{
    private static $instance;
    private $cache;

    /**
     * @return SysVar
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    protected function __construct() {
        $this->cache = MemcachedArray::getNamespace('SysVar');
    }

    /**
     * Load data from SystemVariables DB table into cace
     * @throws Exception
     */
    private function load() {
        $data = [
            'value'=>[],
            'ini'=>[],
            'json'=>[]
        ];
        $results = DB::sql('SELECT * FROM SystemVariables');
        foreach ($results as $result) {
            $data['value'] = $result['value'];
            $data['ini'] = $result['ini'];
            $data['json'] = $result['json'];

            $this->cache->set($result['syskey'], $data);
        }
    }

    /**
     * Set value for key in cache, preserving existing ini and json flags if
     * replacement values are not provided.
     * @param $key
     * @param $data
     */
    private function setKey($key, $data) {
        if(!$this->cache->exists($key)) {
            $prev = [];
        }
        else {
            $prev = $this->cache->get($key);
        }


        $prev['value'] = $data['value'];

        if(isset($data['ini'])) {
            $prev['ini'] = $data['ini'];
        }
        elseif(!isset($prev['ini'])) {
            $prev['ini'] = 0;
        }

        if(isset($data['json'])) {
            $prev['json'] = $data['json'];
        }
        elseif(!isset($prev['json'])) {
            $prev['json'] = 0;
        }

        $this->cache->set($key, $prev);
    }

    /**
     * Check if the key exists, first in the cached then after loading from the
     * DB
     * @param $key
     * @return mixed
     */
    public function has($key) {
        if(!$this->cache->exists($key)) {
            $this->load();
        }

        return $this->cache->exists($key);
    }

    /**
     * @param $key
     * @return mixed
     * @throws Exception
     */
    public function get($key) {
        if ($this->has($key)) {
            $data = $this->cache->get($key);

            if ($data['json']) return json_decode($data['value'], true);
            return $data['value'];
        }
        throw new Exception("Invalid key {$key}.");
    }

    /**
     * @param null $subDirectorySysKey
     * @return mixed|string
     * @throws Exception
     */
    public function siteUrl($subDirectorySysKey = null) {
        return $this->get('siteUrl');
        global $siteUrl;
        if (!$siteUrl) {

            $siteUrl = $this->get('SITE_SSL') ? 'https://' : 'http://';
            $siteUrl .= $this->get('siteHostname');
            if (!isset($_SERVER['HTTPS']) && $this->get('SITE_SSL')) {
                header("Location: {$siteUrl}{$_SERVER['REQUEST_URI']}");
            }
        }
        return $siteUrl.$subDirectorySysKey?$this->get($subDirectorySysKey):'';
    }

    /**
     * @return \Generator
     */
    public function getAll() {
        foreach ($this->cache->getAll() as $key=>$data) {
            if ($data['json']) yield $key => json_decode($data['value'], true);
            yield $key => $data['value'];
        }
    }

    /**
     * Like regular has() except also loads the key to check if the ini flag
     * is set
     * @param $key
     * @return bool
     * @throws Exception
     */
    public function hasIni($key) {
        if ($this->has($key)) {
            $data = $this->get($key);
            return (bool)$data['ini'];
        }
        return false;
    }

    /**
     * Get *all* ini keys - probably more useful than getting one
     * @return \Generator
     */
    public function getAllIni() {
        foreach ($this->cache->getAll() as $key=>$data) {
            if($data['ini']) {
                yield $key => $data;
            }
        }
    }

    /**
     * @param $key
     * @param $value
     * @throws Exception
     */
    public function update($key, $value) {
        $key = DB::cleanse($key);
        $value = DB::cleanse($value);

        $sql = "UPDATE SystemVariables SET `value` = '{$value}' WHERE `syskey` = '{$key}'";
        DB::sql($sql);

        $this->setKey($key, ['value'=>$value]);
    }

    /**
     * @param $key
     * @param $value
     * @param int $ini
     * @param int $json
     * @throws Exception
     */
    public function add($key, $value, $ini = 0, $json = 0) {
        $key = DB::cleanse($key);
        $value = DB::cleanse($value);
        $ini = (int)$ini;
        $json = (int)$json;

        $sql = "INSERT INTO SystemVariables (`syskey`, `value`, `ini`, `json`)  VALUES ('{$key}','{$value}',{$ini}, {$json}) ON DUPLICATE KEY UPDATE `value` = '{$value}', `ini` = {$ini}, `json` = {$json}";
        DB::sql($sql);

        $this->setKey($key, ['value'=>$value, 'ini'=>$ini, 'json'=>$json]);
    }

    private function __clone() {
    }

    private function __wakeup() {
    }
}