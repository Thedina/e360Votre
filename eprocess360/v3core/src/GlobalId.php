<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 7/7/2015
 * Time: 2:30 PM
 */

namespace eprocess360\v3core;



class GlobalId {
    public function __construct($client_id = 0)
    {
        $this->client_id = $client_id;
    }

    public function make()
    {
        global $memcache;
        if (!is_object($memcache)) {
            $memcache = new \Memcache;
            $memcache->connect('localhost', 11211) or die ("Could not connect to memcache.");
        }
        if ((int)$memcache->get('global_incr_time') == time()) {
            $incr = $memcache->increment('global_incr', 1);
        } else {
            $incr = 0;
            $memcache->set('global_incr_time', time());
            $memcache->set('global_incr',0);
        }
        $incr++;
        return time().str_pad($incr,4,'0',STR_PAD_LEFT).str_pad($this->client_id,6,'0',STR_PAD_LEFT);
    }

    public function cleanse($id)
    {
        return substr(preg_replace('[^0-9]','',$id),0,20);
    }
}