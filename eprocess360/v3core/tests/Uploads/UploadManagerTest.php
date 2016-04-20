<?php

namespace eprocess360\v3core {
    use eprocess360\v3core\tests\Uploads\UploadManagerTest;

    function move_uploaded_file($filename, $destination) {
        return UploadManagerTest::mockMoveUploadedFile($filename, $destination);
    }
}

namespace eprocess360\v3core\tests\Uploads {

    use eprocess360\v3core\Controller\Controller;
    use eprocess360\v3core\Controller\Dummy;
    use eprocess360\v3modules\FolderRoot\FolderRoot;
    use eprocess360\v3core\Model\ProjectControllers;
    use eprocess360\v3core\Model\Projects;
    use eprocess360\v3core\tests\base\DBTestBase;
    use org\bovigo\vfs\vfsStream;
    use eprocess360\v3core\Pool;
    use eprocess360\v3core\SysVar;
    use eprocess360\v3core\User;
    use eprocess360\v3core\Files\Folder;
    use eprocess360\v3core\Files\File;
    use eprocess360\v3core\UploadManager;

    class UploadManagerTest extends DBTestBase
    {
        const HASH_LENGTH = 32;
        /**
         * @var \org\bovigo\vfs\vfsStreamDirectory $vfsroot
         */
        private static $vfsRoot;
        private static $vfsPath;
        private $randName;

        private function fakeUploadedFile($filename) {
            $path = self::$vfsPath.'/tmp-'.$filename;
            file_put_contents($path, "I'm an uploaded file!");
            //vfsStream::newFile($path)->at(self::$vfsRoot)->setContent("I'm an uploaded file!");
        }

        private function setUpFakeUpload()
        {
            $this->randName = 'phpolEwmB';

            $_FILES = [
                'test-file' => [
                    'name' => 'test.file',
                    'type' => 'application/octet-stream',
                    'tmp_name' =>$this->randName,
                    'error' => 0,
                    'size' => 64000
                ]
            ];

            $_REQUEST = [
                'uploadtype' => [
                    0 => '1'
                ],
                'desc' => [
                    0 => 'testing'
                ],
                'submit' => 'Submit'
            ];

            $this->fakeUploadedFile($this->randName);
        }

        private function setUpUser() {
            global $pool;

            User::register('Test', 'User', 'test@user.com', 'testpass123', '5555555555');
            $user = User::login('test@user.com', 'testpass123');
            $pool->add($user, 'User');
        }

        private function setUpController() {
            global $pool;

            Dummy::register('Dummy');
            $pool->add(Controller::getProjectController(1), 'Controller');
        }

        private function tearDownController() {
            global $pool;
            $pool->Controller->dropTable();
        }

        private static function setUpVfs() {
            if(!is_object(self::$vfsRoot)) {
                self::$vfsRoot = vfsStream::setup('uploads');
                self::$vfsPath = vfsStream::url('uploads');
            }
        }

        public function getDataSet()
        {
            return $this->createArrayDataSet([
                'Files' => [],
                'Folders'=>[],
                'Roles'=>[
                    [
                        'idSystemRole'=>'1',
                        'title'=>'Administrator',
                        'idController'=>'0',
                        'flags_0'=>'31'
                    ]
                ],
                'UserRoles'=>[
                  [
                      'idUser'=>'1',
                      'idSystemRole'=>'1',
                      'idProject'=>NULL,
                      'idLocalRole'=>NULL,
                      'grantedBy'=>NULL
                  ]
                ],
                'Projects'=>[
                    [
                        'idProject'=>'1',
                        'idController'=>'1',
                        'state'=>'1',
                        'title'=>'Testing',
                        'description'=>'Testing',
                        'status_0'=>NULL
                    ]
                ],
                'SystemVariables'=>[
                    [
                        'syskey'=>'localUploadDirectory',
                        'value'=>'uploads/',
                        'ini'=>NULL,
                        'json'=>'0'
                    ],
                    [
                        'syskey'=>'userSalt',
                        'value'=>'83h&3rF*)FHb',
                        'ini'=>NULL,
                        'json'=>'0'
                    ],
                    [
                        'syskey'=>'siteUrl',
                        'value'=>'v3core-jp.eprocess360.com',
                        'ini'=>NULL,
                        'json'=>'0'
                    ],
                    [
                        'syskey'=>'passwordMinLength',
                        'value'=>'8',
                        'ini'=>NULL,
                        'json'=>'0'
                    ],
                    [
                        'syskey'=>'passwordMaxLength',
                        'value'=>'32',
                        'ini'=>NULL,
                        'json'=>'0'
                    ],
                    [
                        'syskey'=>'passwordRequiresLetter',
                        'value'=>'1',
                        'ini'=>NULL,
                        'json'=>'0'
                    ],
                    [
                        'syskey'=>'passwordRequiresNumber',
                        'value'=>'1',
                        'ini'=>NULL,
                        'json'=>'0'
                    ]
                ]
            ]);
        }

        public function setUp()
        {
            global $pool;

            $pool = Pool::getInstance();
            $pool->add(SysVar::getInstance(), 'SysVar');

            self::setUpVfs();
            parent::setUp();
        }

        public function testVfsStreamSanity()
        {
            $filename = md5(microtime()) . '.txt';

            $this->assertFalse(self::$vfsRoot->hasChild($filename));
            file_put_contents(self::$vfsPath . '/' . $filename, 'hello world!');
            $this->assertTrue(self::$vfsRoot->hasChild($filename));
        }

        public function testMakeLocalDir()
        {
            $dirname = md5(microtime());

            $um = new UploadManager(self::$vfsPath);
            $um->makeLocalDir($dirname);

            $this->assertTrue(self::$vfsRoot->hasChild($dirname));
        }

        public function testCollectFiles()
        {
            $this->setUpFakeUpload();

            $um = new UploadManager(self::$vfsPath);
            $files = $um->collectFiles();
            $this->assertEquals($files, [
                0 => [
                    'desc' => 'testing',
                    'uploadtype' => '1',
                    'name' => 'test.file',
                    'type' => 'application/octet-stream',
                    'tmp_name' =>$this->randName,
                    'error' => 0,
                    'size' => 64000
                ]
            ]);
        }

        public function testProcessUploads()
        {
            global $pool;

            $this->setUpFakeUpload();

            $this->setUpUser();
            $this->setUpController();

            try {
                $folderRoots = $pool->Controller->getFolderRoots();
                /**
                 * @var FolderRoot $root
                 */
                $root = $folderRoots->uploadTest;
                $folder = $root->addNewFolder(1, 'testFolder');
                $folder->insert();

                $um = new UploadManager(self::$vfsPath);
                $ul = $um->processUploads($folder);

                $this->assertCount(1, $ul);
                $this->assertEquals($ul[0]->getFileName(), 'test.file');
                $uldb = File::getAll();
                $this->assertCount(1, $uldb);
                $this->assertEquals($uldb[0]->getFileName(), 'test.file');
            }
            finally {
                $this->tearDownController();
            }
        }

        public static function mockMoveUploadedFile($filename, $destination) {
            self::setUpVfs();
            return rename(self::$vfsPath.'/tmp-'.$filename, $destination);
        }
    }
}