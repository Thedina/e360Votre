<?php
//CREATE INDEX idx_sent ON MailQueue(sent);
//CREATE INDEX idx_failed ON MailQueue(failed);

namespace eprocess360\v3core\Model;
use eprocess360\v3core\Model;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\TinyInteger;
use eprocess360\v3core\Keydict\Entry\Datetime;


class MailQueue extends Model
{
    public static function keydict() {
        return Table::build(
            PrimaryKeyInt::build('idMail', 'Mail ID')->joinsOn(MailLog::model())->setMeta('foreignPrimaryKey'),
            TinyInteger::build('sent', 'Sent Flag'),
            TinyInteger::build('failed', 'Failed Flag'),
            TinyInteger::build('tries', 'Tries'),
            Datetime::build('firstDate', 'First Date'),
            Datetime::build('lastDate', 'Last Date')
        )->setName('MailQueue')->setLabel('Mail Queue');
    }
}