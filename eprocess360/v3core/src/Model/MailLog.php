<?php
//CREATE INDEX idx_idUser ON MailLog(idUser);
//CREATE INDEX idx_idTemplate ON MailLog(idTemplate);


namespace eprocess360\v3core\Model;
use eprocess360\v3core\Model;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\Datetime;
use eprocess360\v3core\Keydict\Entry\TinyInteger;
use eprocess360\v3core\Keydict\Entry\JSONArrayText;
use eprocess360\v3core\Keydict\Entry\Bits8;
use eprocess360\v3core\Keydict\Entry\Bit;


class MailLog extends Model
{
    public static function keydict() {
        return Table::build(
            PrimaryKeyInt::build('idMail', 'Mail ID'),
            IdInteger::build('idUser', 'User ID'),
            IdInteger::build('idProject', 'Project ID'),
            IdInteger::build('idTemplate', 'Template ID')->joinsOn(MailTemplates::model()),
            JSONArrayText::build('vars', 'Template Variables'),
            Datetime::build('dateAdded', 'Date Added'),
            TinyInteger::build('fakeMail', 'Fake/Testing Flag')
        )->setName('MailLog')->setLabel('Mail Log');
    }
}