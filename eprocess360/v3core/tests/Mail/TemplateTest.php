<?php

namespace eprocess360\v3core\tests\Mail;
use eprocess360\v3core\tests\base\DBTestBase;
use eprocess360\v3core\Mail\Template;


class TemplateTest extends DBTestBase
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

    const DUMMY_TEMPLATE_ENTRY_2 = [
        "idTemplate"=>"2",
        "idController"=>"2",
        "templateName"=>"newTemplate",
        "template"=>"Goodbye Cruel World!
                    <!-- htmlBody -->
                    <p>Goodbye {{ name }}</p>
                    <!-- textBody -->
                    Goodbye {{ name }}"
    ];

    public function getDataSet() {
        return $this->createArrayDataSet([
            "MailTemplates"=>[
                self::DUMMY_TEMPLATE_ENTRY
            ]
        ]);
    }

    public function testLoad() {
        $t = new Template();
        $t->load(1);
        $this->assertEquals('testTemplate', $t->getTemplateName());
    }

    public function testGetByID() {
        $t = Template::getByID(1);
        $this->assertEquals('testTemplate', $t->getTemplateName());
    }

    public function testLoadByName() {
        $t = new Template();
        $t->loadByName(1, 'testTemplate');
        $this->assertEquals('testTemplate', $t->getTemplateName());
    }

    public function testGetByName() {
        $t = Template::getByName(1, 'testTemplate');
        $this->assertEquals('testTemplate', $t->getTemplateName());
    }

    public function testInsert() {
        $entry = self::DUMMY_TEMPLATE_ENTRY_2;
        $t = new Template($entry['idController'], $entry['templateName'], $entry['template']);
        $t->insert();

        $resultTable = $this->getConnection()->createQueryTable(
            'MailTemplates', 'SELECT * FROM MailTemplates'
        );

        $expectedTable = $this->createArrayTable('MailTemplates', [
            self::DUMMY_TEMPLATE_ENTRY,
            self::DUMMY_TEMPLATE_ENTRY_2
        ]);

        $this->assertTablesEqual($expectedTable, $resultTable);
    }

    public function testUpdate() {
        $entry = self::DUMMY_TEMPLATE_ENTRY;

        $t = Template::getByName($entry['idController'], $entry['templateName']);
        $this->assertEquals($entry['templateName'], $t->getTemplateName());

        $entry['templateName'] = 'Updated!';
        $t->setTemplateName($entry['templateName']);
        $t->update();

        $resultTable = $this->getConnection()->createQueryTable(
            'MailTemplates', 'SELECT * FROM MailTemplates'
        );

        $expectedTable = $this->createArrayTable('MailTemplates', [
            $entry
        ]);

        $this->assertTablesEqual($expectedTable, $resultTable);
    }

    public function testRender() {
        $entry = self::DUMMY_TEMPLATE_ENTRY;

        $t = Template::getByName($entry['idController'], $entry['templateName']);

        $render = $t->render(['name'=>'Jacob']);

        $this->assertEquals('Hello World!', $render['subject']);
        $this->assertEquals('<p>Hello Jacob</p>', $render['bodyHTML']);
        $this->assertEquals('Hello Jacob', $render['bodyText']);
    }
}