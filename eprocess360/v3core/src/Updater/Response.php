<?php
/**
 * Created by PhpStorm.
 * User: Kira
 * Date: 7/8/2015
 * Time: 10:53 AM
 */

namespace eprocess360\v3core\Updater;


use eprocess360\v3core\Updater;

class Response extends Signature
{
    public function __construct() {
        $this->body = [];
        $this->headers = [];
        $this->signature = [];
        $this->status = 200;
        $this->is_valid = false;
        $this->httpAccept = null;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setBody($body = [])
    {
        $this->body = array_merge($this->body, $body);
    }

    public function getHash()
    {
        $body = $this->getBody();
        return md5(implode(array_keys('|',$body)).implode('|',$body));
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getHeadersHash()
    {
        $headers = $this->getHeaders();
        return md5(implode(array_keys($headers)).implode($headers));
    }

    public function setHeaders($headers = [])
    {
        $this->headers = array_merge($this->headers, $headers);
    }

    public function addReceipt(Request $request)
    {
        $this->body['receipt'] = [
            'hash' => $request->getReceipt()
        ];
        $this->addRequest($request);
    }

    public function addRequest(Request $request)
    {
        $this->body['request'] = $request->getContents();
    }

    public function addAttachment(Request $request)
    {
        $this->body['attachment'] = $request->getAttachment();
        $this->addRequest($request);
    }

    public function getPackage()
    {
        $package = [
            'signature' => $this->signature,
            'headers' => $this->getHeaders(),
            'hash' => md5($this->getHash().$this->signature['client_auth'].Updater::PRIVATE_KEY),
            'body' => $this->getBody(),
            'status' => $this->status
        ];
        return $package;
    }

    protected function setHttpAccept(HttpAccept $httpAccept)
    {
        $this->httpAccept = $httpAccept;
        $this->is_valid = $this->httpAccept->is_valid;
        return $this;
    }

    public static function fromHttpAccept(HttpAccept $httpAccept)
    {
        $new = new self();
        return $new->setHttpAccept($httpAccept);
    }

}