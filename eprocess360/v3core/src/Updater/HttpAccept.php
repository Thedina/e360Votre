<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 7/8/2015
 * Time: 11:25 AM
 */

namespace eprocess360\v3core\Updater;
use eprocess360\v3core\Updater;


/**
 * @property string function
 * @property int request_id
 */
class HttpAccept extends Signature
{
    public function __construct($input_array)
    {
        $this->request = new Request();
        $this->is_valid = false;
        $this->validated = false;
        $this->authenticated = false;
        if ($input_array) {
            $this->data = $input_array;
            $this->origin_ip = $_SERVER['REMOTE_ADDR'];
            $this->is_valid = true;
        }
        return $this;
    }

    public static function fromPhpInput()
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        return new HttpAccept($data);
    }

    public function getClient($key = false)
    {
        if ($key) {
            return $this->data['client'];
        } elseif (isset($this->data['client'][$key])) {
            return $this->data['client'][$key];
        }
        throw new \Exception("Invalid key {$key}");
    }

    public function forRequest(HttpRequest &$request)
    {
        $this->request = $request;
    }

    public function getHeaders()
    {
        return $this->data['headers'];
    }

    public function getHeadersHash()
    {
        $headers = $this->getHeaders();
        return md5(implode(array_keys($headers)).implode($headers));
    }

    public function getBody($key = false)
    {
        if ($key) {
            return $this->data['body'][$key];
        }
        return $this->data['body'];
    }

    public function getResponseHash()
    {
        $body = $this->data['body'];
        $headers = $this->data['headers'];
        return md5(implode(array_keys('|',$body)).implode('|',$body));
    }

    public function validateRequest()
    {
        $md5 = md5($this->data['request']['hash'].$this->getClientAuth().$this->getHeadersHash().Updater::PRIVATE_KEY);
        if ($md5==$this->data['hash']) {
            $this->validated = true;
            return $this;
        }
        throw new \Exception('Bad request.');
    }

    public function validateResponse()
    {
        $md5 = md5($this->getResponseHash().$this->getClientAuth().Updater::PRIVATE_KEY);
        if ($md5==$this->data['hash']) {
            $this->validated = true;
            return $this;
        }
        throw new \Exception('Bad response.');
    }

    public function getClientId()
    {
        return $this->data['signature']['client_id'];
    }

    public function getClientAuth()
    {
        return $this->data['signature']['client_auth'];
    }

    public function getClientIp()
    {
        return $this->data['signature']['client_ip'];
    }

    public function authenticate()
    {
        if ((int)substr($this->data['request']['request_id'],-6,6) == 0) {
            // The client cannot be validate, but the request is probably good
        } else {
            if (isset($this->data['signature'])) {
                $this->signature = $this->data['signature'];
                $this->client = Client::findFromSignature($this);
                $this->authenticated = true;
                return $this;
            }
        }
        throw new \Exception('Bad request.');
    }

    public function isAuthenticated()
    {
        return $this->authenticated && $this->validated;
    }

    public function __get ($key) {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
        throw new \Exception("Key {$key} does not exist.");
    }

    public function getArray()
    {
        return $this->data;
    }

    public function hasAttachment()
    {
        return $this->data['attachment'] ? true : false;
    }

    public function getAttachment()
    {
        if (isset($this->data['attachment'])) {
            return $this->data['attachment'];
        }
        throw new \Exception("Attachment content not present.");
    }
}