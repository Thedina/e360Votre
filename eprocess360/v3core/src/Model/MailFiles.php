<?php
//CREATE INDEX idx_idMail ON MailFiles(idMail);
//CREATE INDEX idx_idFile ON MailFiles(idFile);

namespace eprocess360\v3core\Model;
use eprocess360\v3core\Model;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Keydict\Entry\IdInteger;


class MailFiles extends Model
{
    public static function keydict() {
        return Table::build(
            IdInteger::build('idMail', 'Mail ID')->joinsOn(MailLog::model()),
            IdInteger::build('idFile', 'File ID')->joinsOn(Files::model())
        )->setName('MailFiles')->setLabel('Mail Files');
    }
}