<?php

namespace eprocess360\v3core\tests\Uploads;
use eprocess360\v3core\tests\base\DBTestBase;
use eprocess360\v3core\File;


class UploadTest extends DBTestBase
{
    public function getDataSet() {
        return $this->createArrayDataSet([
            'uploads'=>[
                [
                    'idupload'=>'33574',
                    'idsubmittal'=>'300',
                    'filename'=>'app.yaml',
                    'description'=>'blh blah',
                    'iddocumenttype'=>'1',
                    'uploaddate'=>'2015-08-12 19:29:16',
                    'iduser'=>'1',
                    'filedirectory'=>'/2015-08-12/100/cxAQ',
                    'idproject'=>'100',
                    'status'=>'1',
                    'size'=>'381',
                    'cloud_timestamp'=>'2015-08-12 00:00:00',
                    'islocal'=>'0'
                ],
                [
                    'idupload'=>'33578',
                    'idsubmittal'=>'300',
                    'filename'=>'d_e1m1.mid',
                    'description'=>'test',
                    'iddocumenttype'=>'1',
                    'uploaddate'=>'2015-08-13 16:45:04',
                    'iduser'=>'1',
                    'filedirectory'=>'/2015-08-13/100/taze',
                    'idproject'=>'100',
                    'status'=>'1',
                    'size'=>'17756',
                    'cloud_timestamp'=>NULL,
                    'islocal'=>'1'
                ],
                [
                    'idupload'=>'33577',
                    'idsubmittal'=>'301',
                    'filename'=>'Composer-Setup.exe',
                    'description'=>'blhblah',
                    'iddocumenttype'=>'2',
                    'uploaddate'=>'2015-08-12 19:56:32',
                    'iduser'=>'2',
                    'filedirectory'=>'/2015-08-12/100/GLVM',
                    'idproject'=>'105',
                    'status'=>'1',
                    'size'=>'675472',
                    'cloud_timestamp'=>'2015-08-12 00:00:00',
                    'islocal'=>'1'
                ]
            ]
        ]);
    }

    public function testLoad() {
        $u = new File();
        $u->load(33574);
        $this->assertEquals($u->getID(), 33574);
    }

    public function testUpdate() {
        $u = new File();
        $u->load(33574);
        $u->update(['description'=>'hello world!']);

        $resultTable = $this->getConnection()->createQueryTable(
            'uploads', 'SELECT * FROM uploads WHERE idupload = 33574'
        );

        $expectedTable = $this->createArrayTable('uploads', [
            [
                'idupload'=>'33574',
                'idsubmittal'=>'300',
                'filename'=>'app.yaml',
                'description'=>'hello world!',
                'iddocumenttype'=>'1',
                'uploaddate'=>'2015-08-12 19:29:16',
                'iduser'=>'1',
                'filedirectory'=>'/2015-08-12/100/cxAQ',
                'idproject'=>'100',
                'status'=>'1',
                'size'=>'381',
                'cloud_timestamp'=>'2015-08-12 00:00:00',
                'islocal'=>'0'
            ]
        ]);

        $this->assertTablesEqual($resultTable, $expectedTable);
    }

    public function testInsert() {
        $createData = [
            'idupload'=>'33579',
            'idsubmittal'=>'302',
            'filename'=>'testfile.txt',
            'description'=>'insert upload',
            'iddocumenttype'=>'3',
            'uploaddate'=>'2015-08-14 20:55:00',
            'iduser'=>'1',
            'filedirectory'=>'/2015-08-14/100/AbCD',
            'idproject'=>'105',
            'status'=>'1',
            'size'=>'68000',
            'cloud_timestamp'=>NULL,
            'islocal'=>'1'
        ];

        $u = new File($createData);
        $u->insert();

        $resultTable = $this->getConnection()->createQueryTable(
            'uploads', 'SELECT * FROM uploads WHERE idupload = 33579'
        );

        $expectedTable = $this->createArrayTable('uploads', [$createData]);

        $this->assertTablesEqual($resultTable, $expectedTable);
    }

    public function testGetAll() {
        $ul = File::getAll();
        $this->assertCount(3, $ul);
        $this->assertEquals($ul[0]->getID(), 33574);
        $this->assertEquals($ul[1]->getID(), 33578);
        $this->assertEquals($ul[2]->getID(), 33577);
    }

    public function testGetByID() {
        $u = File::getByID(33578);
        $this->assertEquals($u->getID(), 33578);
    }

    public function testGetByFilter() {
        $ul = File::getByFilter(['idsubmittal'=>['op'=>'=', 'val'=>'300']]);
        $this->assertCount(2, $ul);
        $this->assertEquals($ul[0]->getID(), 33574);
        $this->assertEquals($ul[1]->getID(), 33578);
    }

    public function testGetByProject() {
        $ul = File::getByProject(105);
        $this->assertCount(1, $ul);
        $this->assertEquals($ul[0]->getID(), 33577);
    }

    public function testGetBySubmittal() {
        $ul = File::getBySubmittal(301);
        $this->assertCount(1, $ul);
        $this->assertEquals($ul[0]->getID(), 33577);
    }

    public function testGetLocalOnly() {
        $ul = File::getLocalOnly();
        $this->assertCount(1, $ul);
        $this->assertEquals($ul[0]->getId(), 33578);
    }

    public function testGetLocalExpired() {
        $ul = File::getLocalExpired(1);
        $this->assertCount(1, $ul);
        $this->assertEquals($ul[0]->getId(), 33577);
    }
}