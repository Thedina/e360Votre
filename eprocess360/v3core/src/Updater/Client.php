<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 7/8/2015
 * Time: 10:52 AM
 */

namespace eprocess360\v3core\Updater;
use eprocess360\v3core\Toolbox;
use eprocess360\v3core\Updater;

class Client
{
    public function __construct($client_id = false)
    {
        $this->data = [
            'client_id'=>0,
            'client_auth'=>''
        ];
        $this->is_valid = false;
        if ($client_id) {
            $this->load($client_id);
        }
        return $this;
    }

    public static function findFromSignature(HttpAccept $httpAccept)
    {
        $updater = Updater::getInstance();
        $client = new Client();
        if ($httpAccept->validated && $httpAccept->getSignatureClientId() && $httpAccept->getSignatureClientAuth()) {
            $client_id = preg_replace('/[^0-9]/', '', $httpAccept->getSignatureClientId());
            $client_auth = preg_replace('/[^0-9a-zA-Z]/', '', $httpAccept->getSignatureClientAuth());
            $client_ip = preg_replace('/[^0-9\\.]/', '', $httpAccept->getSignatureClientIp());
            $result = _f(sql("SELECT * FROM `{$updater->local_db}`.`{$updater->cfg_auth_table}` WHERE `client_id` = '{$client_id}' AND `client_auth` = '{$client_auth}' AND `system_ip` = '{$client_ip}'"));
            if ($result) {
                $client = new Client();
                $client->loadRaw($result);
            }
        }
        return $client;
    }

    private function load($client_id)
    {
        $updater = Updater::getInstance();
        $results = sql("SELECT * FROM `{$updater->local_db}`.`{$updater->cfg_auth_table}` WHERE `client_id` = {$client_id}");
        if ($results && sizeof($results)) {
            $this->data = array_shift($results);
            $this->is_valid = true;
        }
    }

    public function getIsMaster()
    {
        $updater = Updater::getInstance();
        if ($updater->getIsMaster() && $this->getClientId() == $updater->client->getClientId()) {
            return true;
        } elseif (!$updater->getIsMaster() && $updater->getMasterId() == $this->getClientId()) {
            return true;
        }
        return false;
    }

    public function loadRaw($result_row)
    {
        $this->data = $result_row;
        $this->is_valid = true;
        return $this;
    }

    public static function create(System $system)
    {
        $updater = Updater::getInstance();
        if (!$system->is_valid) {
            throw new \Exception('Bad request: System invalid.');
        }
        $new_auth = self::generateAuth($system);

        if (sql("INSERT INTO `{$updater->local_db}`.`{$updater->cfg_auth_table}` (`client_auth`,`system_id`,`system_host`,`system_ip`,`system_protocol`) VALUES ('{$new_auth}','{$system->getName()}','{$system->host}','{$system->ip}','{$system->protocol}')")) {
            return new Client(_iid());
        }
        throw new \Exception('Failed to create Client');
    }

    public static function addFromDigest($digest)
    {
        $updater = Updater::getInstance();
        $pieces = explode('##',$digest);
        if (!md5($pieces[0].Updater::PRIVATE_KEY) == $pieces[1])
            throw new \Exception("Invalid digest.");
        $content = explode('#',$pieces[0]);
        $pieces_expected = ['public_key','client_id','client_auth','system_id','system_host','system_ip','system_protocol'];
        $data = array_combine($pieces_expected, array_slice($content,1));
        $updater->saveSettings(['cfg_public_key'=>$data['public_key']]);
        sql($sql = "INSERT INTO `{$updater->local_db}`.`{$updater->cfg_auth_table}` (`client_id`,`client_auth`,`system_id`,`system_host`,`system_ip`,`system_protocol`) VALUES ('{$data['client_id']}','{$data['client_auth']}','{$data['system_id']}','{$data['system_host']}','{$data['system_ip']}','{$data['system_protocol']}')");
        return new Client((int)$data['client_id']);
    }

    public static function remove(System $system)
    {
        $updater = Updater::getInstance();
        if ($system->is_valid) {
            throw new \Exception('Bad request: System invalid.');
        }
        $sql = "DELETE FROM `{$updater->local_db}`.`{$updater->cfg_auth_table}` WHERE `system_id` = '{$system->name}' AND `system_host` = '{$system->host}' AND `system_ip` = '{$system->ip}'";
        sql($sql);
    }

    private static function generateAuth(System $system)
    {
        $md5 = substr(md5($system->name),0,8) . substr(md5($system->ip),0,8);
        return $md5 . Toolbox::generateSalt(16);
    }

    public function __get ($key) {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
        throw new \Exception('Key {$key} does not exist');
    }

    public function getClientId()
    {
        if ($this->is_valid) {
            return $this->data['client_id'];
        }
        throw new \Exception('Client is invalid.');
    }

    public function getClientAuth()
    {
        if ($this->is_valid) {
            return $this->data['client_auth'];
        }
        throw new \Exception('Client is invalid.');
    }

    public function getClientName()
    {
        if ($this->is_valid) {
            return $this->data['system_id'];
        }
        throw new \Exception('Client is invalid.');
    }

    public function getSystemHost()
    {
        if ($this->is_valid) {
            return $this->data['system_host'];
        }
        throw new \Exception('Client is invalid.');
    }

    public function getSystemIp()
    {
        if ($this->is_valid) {
            return $this->data['system_ip'];
        }
        throw new \Exception('Client is invalid.');
    }

    public function getSystemProtocol()
    {
        if ($this->is_valid) {
            return $this->data['system_protocol'];
        }
        throw new \Exception('Client is invalid.');
    }

    public function getLastSync()
    {
        if ($this->is_valid) {
            return $this->data['lastsync'];
        }
        throw new \Exception('Client is invalid.');
    }

    public function __isset ($key) {
        if (isset($this->data[$key])) {
            return true;
        }
        return false;
    }

    public function getApiPath()
    {
        return "http://{$this->system_host}/update/api";
    }

    public function getDigest()
    {
        $updater = Updater::getInstance();
        $key = $updater->getMasterPrivateKey();
        $data = [
            $this->data['client_id'],
            $this->data['client_auth'],
            $this->data['system_id'],
            $this->data['system_host'],
            $this->data['system_ip'],
            $this->data['system_protocol']
        ];
        unset($data['lastsync']);
        if ($this->is_valid) {
            $digest = $key.implode('#', $data);
            $digest = $digest .'##'. md5($digest.Updater::PRIVATE_KEY);
            return $digest;
        }
        throw new \Exception('Client is invalid.');
    }
}