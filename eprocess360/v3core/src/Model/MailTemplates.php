<?php

namespace eprocess360\v3core\Model;
use eprocess360\v3core\Keydict\Entry\FixedString64;
use eprocess360\v3core\Model;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Keydict\Entry\PrimaryKeyInt;
use eprocess360\v3core\Keydict\Entry\IdInteger;
use eprocess360\v3core\Keydict\Entry\Text;

class MailTemplates extends Model
{
    public static function keydict() {
        return Table::build(
            PrimaryKeyInt::build('idTemplate', 'Template ID'),
            IdInteger::build('idController', 'Controller ID')->joinsOn(ProjectControllers::model()),
            FixedString64::build('templateName', 'Template Name'),
            Text::build('template', 'Template'),
            Text::build('varsUsed', 'Variables Used')
        )->setName('MailTemplates')->setLabel('Mail Templates');
    }
}