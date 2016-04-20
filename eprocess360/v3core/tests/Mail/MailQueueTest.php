<?php

namespace eprocess360\v3core\tests\Mail;
use eprocess360\v3core\Mail\MailException;
use eprocess360\v3core\tests\base\DBTestBase;
use eprocess360\v3core\Mail\MailQueue;


class MailQueueTest extends  DBTestBase
{
    const DUMMY_QUEUE_ENTRY = [
        'idMail'=>'50',
        'sent'=>'1',
        'failed'=>'0',
        'tries'=>'0',
        'firstDate'=>'2015-08-30 11:00:00',
        'lastDate'=>NULL
    ];

    const DUMMY_QUEUE_ENTRY_2 = [
        'idMail'=>'51',
        'sent'=>'1',
        'failed'=>'0',
        'tries'=>'1',
        'firstDate'=>'2015-08-31 11:00:00',
        'lastDate'=>'2015-08-31 11:05:00'
    ];

    const DUMMY_QUEUE_ENTRY_3 = [
        'idMail'=>'0',
        'sent'=>'0',
        'failed'=>'0',
        'tries'=>'5',
        'firstDate'=>'2015-08-31 11:00:00',
        'lastDate'=>'2015-08-31 11:05:00'
    ];

    const DUMMY_QUEUE_ENTRY_4 = [
        'idMail'=>'0',
        'sent'=>'1',
        'failed'=>'0',
        'tries'=>'1',
        'firstDate'=>'2015-08-31 11:00:00',
        'lastDate'=>'2015-08-31 11:05:00'
    ];

    public function getDataSet() {
        return $this->createArrayDataSet([
            "MailQueue"=>[
                self::DUMMY_QUEUE_ENTRY
            ]
        ]);
    }

    public function testLoad() {
        $entry = self::DUMMY_QUEUE_ENTRY;

        $q = new MailQueue();
        $q->load((int)$entry['idMail']);
        $this->assertEquals((int)$entry['idMail'], $q->getIDMail());
    }

    public function testGetByID() {
        $entry = self::DUMMY_QUEUE_ENTRY;

        $q = MailQueue::getByID((int)$entry['idMail']);

        $this->assertEquals((int)$entry['idMail'], $q->getIDMail());
    }

    public function testInsert() {
        $entry = self::DUMMY_QUEUE_ENTRY_2;
        unset($entry['idMail']);
        $q = new MailQueue($entry);
        $q->insert();

        $resultTable = $this->getConnection()->createQueryTable(
            'MailQueue', 'SELECT * FROM MailQueue'
        );

        $expectedTable = $this->createArrayTable('MailQueue', [
            self::DUMMY_QUEUE_ENTRY,
            self::DUMMY_QUEUE_ENTRY_2
        ]);

        $this->assertTablesEqual($expectedTable, $resultTable);
    }

    public function testUpdate() {
        $entry = self::DUMMY_QUEUE_ENTRY;
        $entry['tries'] = '2';
        $q = new MailQueue($entry);
        $q->update();

        $resultTable = $this->getConnection()->createQueryTable(
            'MailQueue', 'SELECT * FROM MailQueue'
        );

        $expectedTable = $this->createArrayTable('MailQueue', [
            $entry
        ]);

        $this->assertTablesEqual($expectedTable, $resultTable);
    }

    public function testCanRetry() {
        $initialDate = new \DateTime();
        $oneMin = new \DateInterval('PT1M');
        $fiveMins = new \DateInterval('PT5M');
        $twentyMins = new \DateInterval('PT20M');

        //zero tries, one minute, can try
        $entry = self::DUMMY_QUEUE_ENTRY_4;
        $lastDate = clone $initialDate;
        $lastDate->sub($oneMin);
        $entry['lastDate'] = $lastDate->format('Y-m-d H:i:s');
        $entry['tries'] = '0';

        $q = new MailQueue($entry);
        $q->insert();

        $this->assertEquals(true, $q->canRetry());

        //one try, one minute, cannot retry
        $entry = self::DUMMY_QUEUE_ENTRY_4;
        $lastDate = clone $initialDate;
        $lastDate->sub($oneMin);
        $entry['lastDate'] = $lastDate->format('Y-m-d H:i:s');
        $entry['tries'] = '1';

        $q = new MailQueue($entry);
        $q->insert();

        $this->assertEquals(false, $q->canRetry());

        //one try, five minutes, can retry
        $entry = self::DUMMY_QUEUE_ENTRY_4;
        $lastDate = clone $initialDate;
        $lastDate->sub($fiveMins);
        $entry['lastDate'] = $lastDate->format('Y-m-d H:i:s');
        $entry['tries'] = '1';

        $q = new MailQueue($entry);
        $q->insert();

        $this->assertEquals(true, $q->canRetry());

        //three tries, twenty minutes, can retry
        $entry = self::DUMMY_QUEUE_ENTRY_4;
        $lastDate = clone $initialDate;
        $lastDate->sub($twentyMins);
        $entry['lastDate'] = $lastDate->format('Y-m-d H:i:s');
        $entry['tries'] = '3';

        $q = new MailQueue($entry);
        $q->insert();

        $this->assertEquals(true, $q->canRetry());
    }

    public function testTried() {
        $entry = self::DUMMY_QUEUE_ENTRY;

        $q = MailQueue::getByID((int)$entry['idMail']);

        $q->tried(true);

        $this->assertEquals(1, $q->getTries());

        $entry = self::DUMMY_QUEUE_ENTRY_3;

        $q = new MailQueue($entry);
        $q->insert();

        $q->tried(true);

        $this->assertEquals(true, $q->getFailed());
    }

    public function testGetUnsentQueued() {
        $q = new MailQueue(self::DUMMY_QUEUE_ENTRY_3);
        $q->insert();
        $q = new MailQueue(self::DUMMY_QUEUE_ENTRY_4);
        $q->insert();

        $unsent = MailQueue::getUnsentQueued();

        $this->assertCount(1, $unsent);
        $this->assertEquals(5, $unsent[0]->getTries());
    }
}