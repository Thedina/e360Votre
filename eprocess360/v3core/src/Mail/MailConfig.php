<?php

namespace eprocess360\v3core\Mail;


class MailConfig
{
    private $timeout = 15;
    private $host;
    private $port = 587;
    private $usr;
    private $pwd;
    private $fromEmail;
    private $fromName;
    private $mailOff = false;

    public function __construct($settings) {
        $this->host = $settings['host'];
        $this->pwd = $settings['password'];
        $this->usr = $settings['user'];
        $this->fromEmail = $settings['fromEmail'];
        $this->fromName = $settings['fromName'];

        if(isset($settings['port'])) {
            $this->port = (int)$settings['port'];
        }

        if(isset($settings['timeout'])) {
            $this->timeout = (int)$settings['timeout'];
        }

        if(isset($settings['mailOff'])) {
            $this->mailOff = (bool)$settings['mailOff'];
        }

    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param mixed $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int $port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * @return mixed
     */
    public function getUsr()
    {
        return $this->usr;
    }

    /**
     * @param mixed $usr
     */
    public function setUsr($usr)
    {
        $this->usr = $usr;
    }

    /**
     * @return mixed
     */
    public function getPwd()
    {
        return $this->pwd;
    }

    /**
     * @param mixed $pwd
     */
    public function setPwd($pwd)
    {
        $this->pwd = $pwd;
    }

    /**
     * @return mixed
     */
    public function getFromEmail()
    {
        return $this->fromEmail;
    }

    /**
     * @param mixed $fromEmail
     */
    public function setFromEmail($fromEmail)
    {
        $this->fromEmail = $fromEmail;
    }

    /**
     * @return mixed
     */
    public function getFromName()
    {
        return $this->fromName;
    }

    /**
     * @param mixed $fromName
     */
    public function setFromName($fromName)
    {
        $this->fromName = $fromName;
    }

    /**
     * @return bool
     */
    public function isMailOff() {
        return $this->mailOff;
    }

    /**
     * @param $mailOff
     */
    public function setMailOff($mailOff) {
        $this->mailOff = $mailOff;
    }
}