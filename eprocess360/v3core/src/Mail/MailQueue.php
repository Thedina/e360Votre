<?php

namespace eprocess360\v3core\Mail;
use eprocess360\v3core\DB;
use eprocess360\v3core\Logger;
use eprocess360\v3core\Model;
use eprocess360\v3core\Keydict\Table;


class MailQueue
{
    const MIN_WAIT_RETRY = 300;
    const MAX_TRIES = 5;

    /**
     * @var Table $data
     */
    private $data;

    /**
     * @param array $rowData
     */
    public function __construct($rowData = []) {
        //$idMail, $status, $tries, $firstDate
        //$rowData = ['idMail'=>$idMail, 'templateName'=>$name, 'template'=>$template];
        $this->data = Model\MailQueue::keydict();
        $this->data->acceptArray($rowData);
    }

    public function load($idMail) {
        $rows = DB::sql("SELECT * FROM MailQueue WHERE idMail = ".(int)$idMail);
        if(!empty($rows)) {
            $this->data->wakeup($rows[0]);
        }
    }

    public function insert() {
        $this->data->insert();
    }

    public function update() {
        $this->data->update();
    }

    /**
     * Check if enough time has elapsed to resend this email, according to a
     * basic exponential backoff strategy
     * @return bool
     */
    public function canRetry() {
        $ts_now = strtotime(date('Y-m-d H:i:s'));
        $tries = $this->data->tries->get();

        if($this->data->lastDate->get() !== NULL) {
            $ts_last = strtotime($this->data->lastDate->get());
            if($tries < 1) {
                return true;
            }
            elseif($ts_now - $ts_last >= self::MIN_WAIT_RETRY * pow(2, $tries - 1)) {
                return true;
            }
            else {
                return false;
            }
        }
        else {
            return true;
        }
    }

    /**
     * Update the metadata for this Queue entry to reflect the results of an
     * attempt to send the associated email.
     * @param $sent
     * @param $force
     * @throws \eprocess360\v3core\Keydict\Exception\InvalidValueException
     */
    public function tried($sent = false, $force = false) {
        $date = date('Y-m-d H:i:s');
        $tries = $this->data->tries->get() + 1;

        if(!$this->data->firstDate->get()) {
            $this->data->firstDate->set($date);
        }

        $this->data->lastDate->set($date);
        $this->data->tries->set($tries);
        $this->data->sent->set((int)$sent);

        if($tries > self::MAX_TRIES && !$force) {
            $this->data->failed->set(1);
        }

        $this->update();
    }

    /**
     * Load the Email (with Template) referred to by this queue entry
     * @param boolean $withProject
     * @return Email
     */
    public function getEmail($withProject = false) {
        return Email::getByID($this->data->idMail->get(), $withProject);
    }

    /**
     * @return int
     */
    public function getIDMail() {
        return (int)$this->data->idMail->get();
    }

    /**
     * @return bool
     */
    public function getSent() {
        return (bool)$this->data->sent->get();
    }

    /**
     * @return bool
     */
    public function getFailed() {
        return (bool)$this->data->failed->get();
    }

    /**
     * @return int
     */
    public function getTries() {
        return (int)$this->data->tries->get();
    }

    /**
     * @return Table
     */
    public function getData() {
        return $this->data;
    }

    /**
     * @param $idMail
     * @param $firstDate
     * @return MailQueue
     */
    public static function build($idMail, $firstDate) {
        $rowData = ['idMail'=>$idMail, 'firstDate'=>$firstDate];
        return new self($rowData);
    }

    /**
     * @param $idMail
     * @return MailQueue
     */
    public static function getByID($idMail) {
        $q = new MailQueue();
        $q->load($idMail);
        return $q;
    }

    /**
     * Add a Queue entry from $mail
     * @param Email $mail
     */
    public static function addMail(Email $mail) {
        $q = self::build($mail->getIDMail(), $mail->getDateAdded());
        $q->insert();
    }

    /**
     * Get every Queue entry from the DB for unsent messages
     * @return array
     * @throws \Exception
     */
    public static function getUnsentQueued() {
        $queue = [];

        //Where not sent flag or failed flag
        $rows = DB::sql("SELECT mq.* FROM MailQueue mq WHERE mq.sent = 0 AND mq.failed = 0");

        foreach($rows as $r) {
            $queue[] = new self($r);
        }

        return $queue;
    }
}