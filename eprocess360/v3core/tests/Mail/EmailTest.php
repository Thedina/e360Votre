<?php

namespace eprocess360\v3core\tests\Mail;
use eprocess360\v3core\tests\base\DBTestBase;
use eprocess360\v3core\Mail\MailConfig;
use eprocess360\v3core\Mail\Template;
use eprocess360\v3core\Mail\Email;


class EmailTest extends DBTestBase
{
    const DUMMY_TEMPLATE_ENTRY = [
        "idTemplate"=>"1",
        "idController"=>"1",
        "templateName"=>"testTemplate",
        "template"=>"Hello World!
                     <!-- htmlBody -->
                     <p>Hello {{ name }}</p>
                     <!-- textBody -->
                     Hello {{ name }}"
    ];

    const DUMMY_MAILLOG_ENTRY = [
        "idMail"=>"555",
        "idUser"=>"100",
        "idTemplate"=>"1",
        "vars"=>"{\"name\":\"Jacob\"}",
        "firstDate"=>"2015-08-25 12:30:00",
        "fakeMail"=>"0"
    ];

    const DUMMY_MAILLOG_ENTRY_2 = [
        "idMail"=>"556",
        "idUser"=>"100",
        "idTemplate"=>"1",
        "vars"=>"{\"name\":\"Jacob\"}",
        "firstDate"=>"2015-08-25 13:00:00",
        "fakeMail"=>"0"
    ];
    //testpass99
    //cvnlnzynnfwafgbk
    const SMTP_CONFIG = [
        'host'=>'smtp.gmail.com',
        'port'=>'587',
        'user'=>'e360smtp@gmail.com',
        'password'=>'testpass99',
        'fromEmail'=>'e360smtp@gmail.com',
        'fromName'=>'E360 SMTP',
        'mailOff'=>'0'
    ];

    public function getDataSet() {
        return $this->createArrayDataSet([
            "MailTemplates"=>[
                self::DUMMY_TEMPLATE_ENTRY
            ],
            "MailLog"=>[
                self::DUMMY_MAILLOG_ENTRY
            ],
            "Users"=>[
                [
                    "idUser"=>"100",
                    "email"=>"sender@test.test",
                    "firstName"=>"Sending",
                    "lastName"=>"User"
                ],
                [
                    "idUser"=>"101",
                    "email"=>"recipient@test.test",
                    "firstName"=>"Recipient",
                    "lastName"=>"User"
                ],
                [
                    "idUser"=>"102",
                    "email"=>"another@test.test",
                    "firstName"=>"Another",
                    "lastName"=>"User"
                ],
                [
                    "idUser"=>"105",
                    "email"=>"jacob@wc-3.com",
                    "firstName"=>"Jacob",
                    "lastName"=>"Privalsky"
                ]
            ],
            "MailRecipients"=>[
                [
                    "idMail"=>"555",
                    "idUser"=>"101"
                ],
                [
                    "idMail"=>"555",
                    "idUser"=>"102"
                ],
                [
                    "idMail"=>"556",
                    "idUser"=>"101"
                ]
            ],
            "MailFiles"=>[
                [
                    "idMail"=>"556",
                    "idFile"=>"100"
                ]
            ]
        ]);
    }

    public function testLoad() {
        $entry = self::DUMMY_MAILLOG_ENTRY;

        $e = new Email();
        $e->load((int)$entry['idMail']);
        $this->assertEquals((int)$entry['idMail'], $e->getIdMail());
    }

    public function testGetByID() {
        $entry = self::DUMMY_MAILLOG_ENTRY;
        $e = Email::getByID((int)$entry['idMail']);
        $this->assertEquals((int)$entry['idMail'], $e->getIdMail());
    }


    public function testInsert() {
        $entry = self::DUMMY_MAILLOG_ENTRY_2;
        unset($entry['idMail']);

        $e = new Email();
        $e->loadRaw($entry);
        $e->insert();
        $entry['idMail'] = $e->getIDMail();

        $resultML = $this->getConnection()->createQueryTable(
            'MailLog', 'SELECT * FROM MailLog'
        );

        $expectedML = $this->createArrayTable('MailLog', [
            self::DUMMY_MAILLOG_ENTRY,
            $entry
        ]);

        $this->assertTablesEqual($expectedML, $resultML);

        $entry2 = [
            "idUser"=>"101",
            "idTemplate"=>"0",
            "vars"=>"[]",
            "firstDate"=>NULL,
            "fakeMail"=>"0"
        ];
        $e2 = new Email(['105','107'], NULL, [], $entry2['idUser'], ['101']);
        $e2->insert();
        $entry2['idMail'] = $e2->getIDMail();

        $resultML = $this->getConnection()->createQueryTable(
            'MailLog', 'SELECT * FROM MailLog'
        );

        $expectedML = $this->createArrayTable('MailLog', [
            self::DUMMY_MAILLOG_ENTRY,
            $entry,
            $entry2
        ]);

        $this->assertTablesEqual($expectedML, $resultML);

        $resultMR = $this->getConnection()->createQueryTable(
            "MailRecipients", "SELECT * FROM MailRecipients WHERE idMail = ".(int)$entry2['idMail']
        );

        $expectedMR = $this->createArrayTable('MailRecipients', [
            [
                "idMail"=>$entry2['idMail'],
                "idUser"=>"105"
            ],
            [
                "idMail"=>$entry2['idMail'],
                "idUser"=>"107"
            ]
        ]);

        $this->assertTablesEqual($expectedMR, $resultMR);

        $resultMF = $this->getConnection()->createQueryTable(
            "MailFiles", "SELECT * FROM MailFiles WHERE idMail = ".(int)$entry2['idMail']
        );

        $expectedMF = $this->createArrayTable('MailFiles', [
            [
                "idMail"=>$entry2['idMail'],
                "idFile"=>"101"
            ]
        ]);

        $this->assertTablesEqual($expectedMF, $resultMF);
    }


    public function testUpdate() {
        $entry = self::DUMMY_MAILLOG_ENTRY;
        $entry['vars'] = "{\"name\":\"Kira\"}";

        $e = new Email();
        $e->loadRaw($entry);
        $e->update();

        $resultTable = $this->getConnection()->createQueryTable(
            'MailLog', 'SELECT * FROM MailLog'
        );

        $expectedTable = $this->createArrayTable('MailLog', [
            $entry
        ]);

        $this->assertTablesEqual($expectedTable, $resultTable);
    }

    public function testSend() {
        $cfg = new MailConfig(self::SMTP_CONFIG);
        $t = Template::getByID(1);
        $e = new Email([105], $t, ['name'=>'Jacob']);
        $idNew = $e->insert();

        $e2 = Email::getByID($idNew);

        $sent = $e2->send($cfg);
        var_dump($sent);

        /*$e2 = Email::getByID($idSent);
        $this->assertEquals('Hello World!', $e2->getSubject());
        $this->assertEquals('<p>Hello Jacob</p>', $e2->getBodyHTML());
        $this->assertEquals('Hello Jacob', $e2->getBodyText());*/
    }
}