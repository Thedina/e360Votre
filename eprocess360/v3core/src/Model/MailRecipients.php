<?php
//CREATE INDEX idx_idMail ON MailRecipients(idMail);
//CREATE INDEX idx_idUser ON MailRecipients(idUser);

namespace eprocess360\v3core\Model;
use eprocess360\v3core\Model;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Keydict\Entry\IdInteger;


class MailRecipients extends Model
{
    public static function keydict() {
        return Table::build(
            IdInteger::build('idMail', 'Mail ID')->joinsOn(MailLog::model()),
            IdInteger::build('idUser', 'User ID')->joinsOn(Users::model())
        )->setName('MailRecipients')->setLabel('Mail Recipients');
    }
}