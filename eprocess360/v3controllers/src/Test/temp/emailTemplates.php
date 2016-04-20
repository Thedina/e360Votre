<?php

use \eprocess360\v3core\Mail\Template;
use \eprocess360\v3core\Mail\MailConfig;
use \eprocess360\v3core\Mail\Email;
use \eprocess360\v3core\Mail\MailQueue;
use \eprocess360\v3core\MailManager;

$mailCFG = [
    'host'=>'smtp.gmail.com',
    'port'=>'587',
    'user'=>'e360smtp@gmail.com',
    'password'=>'testpass99',
    'fromEmail'=>'e360smtp@gmail.com',
    'fromName'=>'E360 SMTP',
    'mailOff'=>'0'
];

/*$templateText = "Hello World!
                     <!-- htmlBody -->
                     <p>Hello {{ name }}</p>
                     <!-- textBody -->
                     Hello {{ name }}";

$t = Template::getByName(2048, 'testTemplate');

if(!$t->getIdTemplate()) {
    $t = Template::create(2048, 'testTemplate', $templateText);
}

$render = $t->render(['name'=>'Jacob']);

$cfg = new MailConfig($mailCFG);
$mail = new Email([1], $t, ['name'=>'Jacob'], 1);
$idMail = $mail->insert();

$mail = Email::getByID($idMail);
$mail->send($cfg);*/

//MailManager::initConfig($mailCFG);
//MailManager::mail([1], 2048, 'testTemplate', ['name'=>'Jacob']);
//MailManager::retryQueueUnsent();

$mail = new \eprocess360\v3modules\Mail\Mail();