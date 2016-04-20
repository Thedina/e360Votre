<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 7/8/2015
 * Time: 10:53 AM
 */

namespace eprocess360\v3core\Updater;

use eprocess360\v3core\Updater\Module\SQLSync;

/**
 * @property string host
 */
class System extends Sendable
{
    public function __construct($user_values = [])
    {
        global $pool;
        parent::__construct();
        $this->is_valid = false;
        if ($pool->SysVar->get('siteUrl') && $pool->SysVar->get('siteIP') && is_array($user_values)) {
            $this->data = [
                'name'=>gethostname(),
                'host'=>substr($pool->SysVar->get('siteUrl'),7),
                'ip'=>$pool->SysVar->get('siteIP'),
                'protocal'=>substr($pool->SysVar->get('siteUrl'), 0, substr($pool->SysVar->get('siteUrl'), 5, 1) == 's' ? 5 : 4 )
            ];
            $this->data = array_merge($this->data, $user_values);
            $this->is_valid = true;
        }
        return $this;
    }

    public function getSystemName()
    {
        return $this->data['name'];
    }

    public function isValid()
    {
        return $this->is_valid;
    }

    public function getName()
    {
        if (!$this->isValid()) throw new \Exception("Invalid System object.");
        return $this->data['name'];
    }

    public function __get ($key) {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
        throw new \Exception("Key {$key} does not exist.");
    }

    public static function fromSettings($name, $host, $ip, $protocal)
    {
        return new System([
            'name' => $name,
            'host' => $host,
            'ip' => $ip,
            'protocal' => $protocal
        ]);
    }

    public static function fromHttpAccept(HttpAccept $httpAccept)
    {
        $headers = $httpAccept->getHeaders();
        return new System([
            'name' => $headers['name'],
            'host' => $headers['host'],
            'ip' => $headers['ip'],
            'protocal' => $headers['protocal']
        ]);
    }

}