<?php
/**
 * Created by PhpStorm.
 * User: kira
 * Date: 7/17/2015
 * Time: 12:07 PM
 */

namespace eprocess360\v3core\Updater;
use eprocess360\v3core\Updater;

class Signature
{
    public $signature;

    public function sign($registration_request = false)
    {
        global $pool;
        $updater = Updater::getInstance();
        if (!$registration_request)
            $this->signature = [
                'client_id' => $updater->client->getClientId(),
                'client_auth' => $updater->client->getClientAuth(),
                'client_ip' => $pool->SysVar->get('siteIP')
            ];
        else
            $this->signature = [
                'client_id' => '0',
                'client_auth' => '',
                'client_ip' => $pool->SysVar->get('siteIP')
            ];
        $this->is_signed = true;
        return $this;
    }

    public function getSignatureClientId()
    {
        return $this->signature['client_id'];
    }

    public function getSignatureClientAuth()
    {
        return $this->signature['client_auth'];
    }

    public function getSignatureClientIp()
    {
        return $this->signature['client_ip'];
    }
}