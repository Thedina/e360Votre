<?php

namespace eprocess360\v3core;
require_once(APP_PATH . "/vendor/autoload.php");
use eprocess360\v3core\Keydict\Entry;
use eprocess360\v3core\Keydict\Entry\Email;
use eprocess360\v3core\Keydict\Entry\PhoneNumber;
use eprocess360\v3core\Keydict\Entry\Password;
use eprocess360\v3core\Keydict\Entry\FixedString32;
use eprocess360\v3core\Keydict\Entry\String;
use eprocess360\v3core\Keydict\Entry\UnsignedSmallInteger;
use eprocess360\v3core\Keydict\Entry\UnsignedTinyInteger;
use eprocess360\v3core\Keydict\Exception\KeydictException;
use eprocess360\v3core\Keydict\Table;
use eprocess360\v3core\Model\Roles;
use eprocess360\v3core\Controller\Warden\Privilege;


/**
 * Class Configuration
 * @package eprocess360\v25
 */
class Configuration
{
    const AUTOINDEX = 2048;
    const CONFIG_SSL_OPT = '/config/ssl-opt.php';
    const CONFIG_CONN = '/config/conn.php';
    const CONFIG_USER = '/config/user.php';
    const CONFIG_PORTAL = '/config/portal.php';
    const SSL_CUSTOM_CONFIG = [
        'digest_alg'=>'sha256',
        'private_key_bits'=>'2048'
    ];
    const SSL_INFO_FILE = '/config/sslinfo';
    const SSL_DIR = '/ssl';
    const SSL_HELPER_CHECK_CERT = '/helper/sslconfig_dispatch check-cert-installed';
    const SSL_HELPER_INSTALL_CERT = '/helper/sslconfig_dispatch install-cert';
    const SSL_PKEY = '/e360.key';
    const SSL_CERTIFICATE = '/e360cert.pem';
    const SSL_CSR = '/e360.csr';
    const SSL_CRT = '/e360.crt';
    const SSL_EXPIRE_DAYS = 365;
    const TEMPLATE = '/templates/twig/setup.main.html.twig';
    const SYSVAR_PRESETS = '/init/presets/systemVariables.json';
    const CONFIG_NUM_PAGES = 5;

    /**
     * @var Array $presets
     */
    private static $presets = NULL;

    /**
     * Returns the directory above the public_html web directory
     * @return string
     */
    private static function outerDirectory() {
        return substr(APP_PATH, 0, strrpos(APP_PATH, '/'));
    }

    /**
     * Return the path to the ssl subdirectory above public_html
     * @return string
     */
    private static function sslDir() {
        $sslDir = self::outerDirectory().self::SSL_DIR;

        if(!file_exists($sslDir)) {
            mkdir($sslDir);
        }

        return $sslDir;
    }

    /**
     * Create a CSR file based on info entered by the user
     * @param $csrInfo
     */
    private static function sslMakeCSR($csrInfo) {
        $sslDir = self::sslDir();

        if(!file_exists($keyFile = $sslDir.self::SSL_PKEY)) {
            $pkey = openssl_pkey_new(self::SSL_CUSTOM_CONFIG);
            openssl_pkey_export_to_file($pkey, $keyFile);
        }
        else {
            $pkey = openssl_pkey_get_private(file_get_contents($keyFile));
        }

        $csr = openssl_csr_new($csrInfo, $pkey, self::SSL_CUSTOM_CONFIG);
        openssl_csr_export_to_file($csr, $sslDir.self::SSL_CSR);
    }

    private static function sslExportCombinedKeyCert($signed, $keyFile, $certFile) {
        if(copy($keyFile, $certFile)) {
            if(openssl_x509_export($signed, $certString)) {
                return (bool)file_put_contents($certFile, $certString, FILE_APPEND);
            }
            else {
                return false;
            }
        }
        else {
            return false;
        }
    }

    /**
     * Create a self-signed SSL certificate for temporary use
     * @throws \Exception
     */
    private static function sslMakeTempCertificate() {
        $sslDir = self::sslDir();

        if(!file_exists($csrFile = $sslDir.self::SSL_CSR)) {
            throw new \Exception("Configuration::sslMakeCertificate(): file {$csrFile} does not exist");
        }

        if(!file_exists($keyFile = $sslDir.self::SSL_PKEY)) {
            throw new \Exception("Configuration::sslMakeCertificate(): file {$keyFile} does not exist");
        }

        $signed = openssl_csr_sign('file://'.$csrFile, NULL, 'file://'.$keyFile, self::SSL_EXPIRE_DAYS, self::SSL_CUSTOM_CONFIG);

        self::sslExportCombinedKeyCert($signed, $keyFile, $sslDir.self::SSL_CERTIFICATE);
    }

    /**
     * Create a fully signed certificate (or return false if cannot find a CRT
     * file)
     * @return bool
     * @throws \Exception
     */
    private static function sslMakeSignedCertificate() {
        $sslDir = self::sslDir();

        if(!file_exists($csrFile = $sslDir.self::SSL_CSR)) {
            throw new \Exception("Configuration::sslMakeSignedCertificate(): file {$csrFile} does not exist");
        }

        if(!file_exists($keyFile = $sslDir.self::SSL_PKEY)) {
            throw new \Exception("Configuration::sslMakeSignedCertificate(): file {$keyFile} does not exist");
        }

        if(file_exists($crtFile = $sslDir.self::SSL_CRT)) {
            $signed = openssl_csr_sign('file://'.$csrFile, 'file://'.$crtFile, 'file://'.$keyFile, self::SSL_EXPIRE_DAYS, self::SSL_CUSTOM_CONFIG);
            self::sslExportCombinedKeyCert($signed, $keyFile, $sslDir.self::SSL_CERTIFICATE);
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * Write define() statements to a file, creating it if necessary
     * @param $file
     * @param $defines
     */
    private static function writeDefines($file, $defines, $append = false) {
        $mode = $append ? 'a+' : 'w+';

        $f = fopen($file, $mode);
        fwrite($f, "<?php\n");
        foreach($defines as $k=>$v) {
            fwrite($f, "define('".$k."','".$v."');\n");
        }
        fclose($f);
    }

    /**
     * Drop all existing database table
     * @throws MySQLException
     */
    private static function clearAllTables() {
        $extractCol = 'Tables_in_'.MYSQL_DATABASE;
        $tables = DB::sql("SHOW TABLES");

        if(!empty($tables)) {
            foreach($tables as $t) {
                DB::sql("DROP TABLE IF EXISTS ".$t[$extractCol]);
            }
        }
    }

    /**
     * Initialize the DB
     * @param bool|false $reset
     */
    private static function initDB($reset = false) {
        require_once(APP_PATH."/config/conn.php");

        if($reset) {
            self::clearAllTables();
            if(file_exists(APP_PATH.self::CONFIG_USER))
                unlink(APP_PATH.self::CONFIG_USER);
        }

        foreach(glob(APP_PATH."/eprocess360/v3core/src/Model/*.php") as $file) {
            include($file);
        }
        foreach(glob(APP_PATH."/eprocess360/v3modules/src/*/Model/*.php") as $file) {
            include($file);
        }
        foreach(glob(APP_PATH."/eprocess360/v3controllers/src/*/Model/*.php") as $file) {
            include($file);
        }
        foreach(get_declared_classes() as $className) {
            if(strpos($className, "eprocess360\\v3core\\Model\\") === 0 || strpos($className, "eprocess360\\v3modules") === 0 || strpos($className, "eprocess360\\v3controllers") === 0) {
                /** @var Model $className */
                /** @var Table $table */
                $table = $className::keydict();
                $tableName = $table->getRawName();

                if($tableName !== NULL) {
                    $exists = DB::sql("SHOW TABLES LIKE '{$tableName}'");
                    if (!empty($exists)) {
                        if($reset) {
                            $className::dropTable();
                            $className::createTable();
                        }
                    } else {
                        $className::createTable();
                    }
                    if($tableName === "Controllers"){
                        $sql = "ALTER TABLE Controllers AUTO_INCREMENT = ".self::AUTOINDEX;
                        DB::sql($sql);
                    }
                }
            }
        }
    }

    /**
     * Load config presets from JSON if not loaded
     * @return Array
     */
    private static function loadJSONPresets() {
        if(!is_array(self::$presets)) {
            self::$presets = json_decode(file_get_contents(APP_PATH.self::SYSVAR_PRESETS), true);
        }

        return self::$presets;
    }

    /**
     * Set default values for $fields from config presets
     * @param array $fields
     */
    private static function setFormDefaults(Array $fields) {
        global $pool;

        foreach($fields as $f) {
            /**
             * @var Entry $f
             */
            if($pool->SysVar->has($f->getName())) {
                $f->wakeup($pool->SysVar->get($f->getName()));
            }
            elseif(array_key_exists($f->getName(), self::$presets)) {
                $f->wakeup(self::$presets[$f->getName()]);
            }
        }
    }

    /**
     * Set one or more SysVars directly from config presets
     * @param array $keys
     */
    private static function sysVarFromDefaults(Array $keys) {
        global $pool;

        foreach($keys as $k) {
            $pool->SysVar->add($k, self::$presets[$k]);
        }
    }

    /**
     * Role ID 1 is *always* global admin!
     * @throws MySQLException
     */
    private static function initRoles() {
        $roles = DB::sql("SELECT * FROM Roles WHERE idSystemRole = 1");
        if(!empty($roles)) {
            DB::sql("UPDATE Roles SET title = 'Administrator', idController = 0, flags_0 = ".(int)Privilege::ROLE_ADMIN);
        }
        else {
            DB::sql("INSERT INTO Roles VALUES (1, 'Administrator', 0," . (int)Privilege::ROLE_ADMIN . ")");
        }
    }

    /**
     * Create required local subdirectories with appropriate permissions.
     * @throws \Exception
     */
    private static function initFileSystem() {
        global $pool;

        mkdir($logDir = $pool->SysVar->get('logDirectory'), 0755);
        mkdir($sqlLogDir = $pool->SysVar->get('sqlLogs'), 0755);
        mkdir($uploadDir = APP_PATH.$pool->SysVar->get('localUploadDirectory'), 0755);

        if(!file_exists($logDir)) {
            throw new \Exception("Configuration::initFileSystem(): failed to create log directory.");
        }
        if(!file_exists($sqlLogDir)) {
            throw new \Exception("Configuration::initFileSystem(): failed to create sql log directory.");
        }
        if(!file_exists($uploadDir)) {
            throw new \Exception("Configuration::initFileSystem(): failed to create upload directory.");
        }
    }

    /**
     * Initialize Scheduler tasks from schedulerTasks SysVar
     */
    private static function initSchedulerTasks() {
        global $pool;

        $scheduler = Scheduler::initScheduler();
        $scheduler->initTasks(array_map(function($name) {
            return trim($name);
        }, explode(',', $pool->SysVar->get('schedulerTasks'))));
    }

    /**
     * Check that a CSR file exists
     * @return bool
     */
    public static function sslCSRExists() {
        $sslDir = self::sslDir();
        return file_exists($sslDir.self::SSL_CSR);
    }

    /**
     * Check that the e360cert.pem file exists in the apache SSL directory
     * @return int
     */
    public static function sslCheckCertInstalled() {
        $sslDir = self::sslDir();
        return (int)shell_exec($sslDir.self::SSL_HELPER_CHECK_CERT);
    }

    /**
     * Invoke helper scripts to copy the generated SSL certificate file into
     * the apache SSL directory and restart apache
     */
    public static function sslInstallCert() {
        $sslDir = self::sslDir();
        exec($sslDir.self::SSL_HELPER_INSTALL_CERT.' '.(int)getmypid().' > /dev/null 2>&1 &');
    }

    /**
     * Initiate a download of the CSR file if it exists
     */
    public static function sslServeCSR() {
        $sslDir = self::sslDir();
        if(file_exists($csrFile = $sslDir.self::SSL_CSR)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($csrFile).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($csrFile));
            readfile($csrFile);
        }
    }

    /**
     * Create a global $pool object without going through _init
     */
    public static function initPool() {
        global $pool;
        $pool = Pool::getInstance();
        $pool->add((object)[], 'Temp');
    }

    /**
     * Create a global $twig object without going through _init
     */
    public static function initTwig() {
        //substr(APP_PATH, 0, strrpos(APP_PATH, '/')).'/cache'
        global $twig, $twig_loader;
        $twig_loader = new \Twig_Loader_Filesystem(APP_PATH."/eprocess360/v3controllers/src/SystemController/static/twig");
        global $twig, $twig_loader;
        $twig = new \Twig_Environment($twig_loader, array(
            'cache' => false,
            'debug' => true
        ));
        $twig_loader->addPath(APP_PATH.'/eprocess360/v3controllers/src/SystemController/static/twig');
    }

    /**
     * Flush Memcached
     */
    public static function initMemcached() {
        MemcachedManager::getMemcached()->flush();
    }

    /**
     * Add a SysVar object to $pool without going through _init
     */
    public static function initSysVar() {
        global $pool;

        $pool->add(SysVar::getInstance(), 'SysVar');

        if(!$pool->SysVar->has('configStep')) {
            $pool->SysVar->add('configStep', '0');
        }

        if(!$pool->SysVar->has('siteUrl')) {
            $pool->SysVar->add('siteUrl', (REQUIRE_SSL ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST']);
        }
    }

    /**
     * Check if there are any users in the Users table
     * @return bool
     */
    public static function checkUserExists() {

        return file_exists(APP_PATH.self::CONFIG_USER);
    }

    /**
     * @param array $links
     * @return string
     */
    public static function renderInterstitial(Array $links) {
        global $pool, $twig;
        $pool->add($links, 'Links');

        return $twig->render('setup.interstitial.html.twig', $pool->asArray());
    }

    /**
     * Helper function for creating file upload forms
     * @param $handler
     * @param $label
     * @return Form
     */
    public static function getFileUploadForm($handler, $label) {
        $form = Form::build(0, str_replace('/', '', $handler), $label, 'setup.fileupload.html.twig');
        $form->setHandler('/setup/'.$handler);
        $form->setPublic(true);

        return $form;
    }

    /**
     * Helper function for creating setup forms
     * @param $handler
     * @param $label
     * @param $fields
     * @param array $validators
     * @return static
     */
    public static function getForm($handler, $label, $fields, $validators = []) {
        $form = Form::build(0, str_replace('/', '', $handler), $label, 'setup.main.html.twig');
        $form->setHandler('/setup/'.$handler);
        $form->accepts(...$fields);

        if(!empty($validators)) {
            $keydict = $form->getKeydict();

            foreach ($validators as $k => $func) {
                $keydict->addLateValidator($k, function () use (&$keydict, $func) {
                    $func($keydict);
                });
            }
        }

        $form->setPublic(true);
        global $pool;
        $pool->add(['meta'=>['SystemController'=>['static'=>'../../eprocess360/v3controllers/src/SystemController/static']]], 'Response');

        return $form;
    }

    /**
     * Helper function to handle setup form POST, callback to processing function, and redirect to next step
     * @param Form $form
     * @param string $callback
     */
    public static function acceptForm(Form $form, $callback) {
        $form->acceptPost();
        if (!$form->getKeydict()->hasException()) {
            $nextPage = call_user_func('eprocess360\\v3core\\Configuration::'.$callback, $form->getKeydict());
            if($nextPage !== false) {
                header('Location: ' . $nextPage);
            }
            die();
        }
    }

    /**
     * Get the SSL options form
     * @return Form
     */
    public static function getSSLOptForm() {
        $fields = [
            UnsignedTinyInteger::build('requireSSL', 'Require SSL?', 0)
        ];

        return self::getForm('ssl-opt', 'Require SSL?', $fields);
    }

    /**
     * Get the SSL cert info form
     * @return Form
     */
    public static function getSSLForm() {
        $fields = [
            String::build('commonName', 'Service Address (without http)', $_SERVER['HTTP_HOST']),
            String::build('countryName', 'Country Name Abbreviation', 'US'),
            String::build('stateOrProvinceName', 'State or Province Name', 'California'),
            String::build('localityName', 'Locality Name', 'San Ramon'),
            String::build('organizationName', 'Organization Name', 'West Coast Code Consultants, Inc.'),
            String::build('organizationalUnitName', 'Organization Unit Team', 'Information Technology'),
            Email::build('emailAddress', 'Primary Administrative E-mail Address', 'kira@wc-3.com')
        ];

        return self::getForm('ssl', 'SSL Certificate Info', $fields);
    }

    /**
     * Get the DB connection setup form
     * @return Form
     */
    public static function getConnForm() {
        $fields = [
            String::build('dbHost', 'DB Host'),
            String::build('dbUser', 'DB User'),
            String::build('dbPassword', 'DB Password'),
            String::build('dbName', 'DB Name'),
            UnsignedTinyInteger::build('resetDB', '*DESTROY ALL TABLES!*', 0)
        ];

        return self::getForm('conn', 'Database Setup', $fields);
    }

    /**
     * Get DB reset form
     * @return Form
     */
    public static function getDBResetForm() {
        $fields = [
            UnsignedTinyInteger::build('resetDB', '*DESTROY ALL TABLES!*', 0)
        ];

        return self::getForm('db-reset', 'Reset DB?', $fields);
    }

    /**
     * Get the SysVar/global config form
     * @param $page
     * @return Form
     */
    public static function getConfigForm($page) {
        $formData = [
            0=>[
                'label'=>'Cloud API Config',
                'fields'=>[
                    String::build('cloudStorageProvider', 'Cloud Storage Provider'),
                    String::build('googleAPIKey', 'Google API Key'),
                    String::build('googleAppName', 'Google App Name'),
                    String::build('googleOAuthPresetFile', 'Google OAuth: Presets File', APP_PATH.'/init/presets/slcodev-eprocess360-com-77a25e1f2dbd.json'),
                    String::build('googleUploadBucket', 'Google Upload Bucket')
                ]
            ],
            1=>[
                'label'=>'Email Config',
                'fields'=>[
                    String::build('mailFromAddress', 'Email: From Address'),
                    String::build('mailFromName', 'Email: From Name'),
                    String::build('mailServerHost', 'SMTP Host'),
                    UnsignedSmallInteger::build('mailServerPort', 'SMTP Port'),
                    String::build('mailServerUser', 'SMTP User'),
                    String::build('mailServerPassword', 'SMTP Password')
                ]
            ],
            2=>[
                'label'=>'Site Config',
                'fields'=>[
                    String::build('siteHostname', 'Hostname', $_SERVER['HTTP_HOST']),
                    String::build('siteIP', 'IP Address', $_SERVER['SERVER_ADDR']),
                    String::build('siteCache', 'Twig Cache', self::outerDirectory().'/cache'),
                    String::build('localUploadDirectory', 'Local Upload Directory'),
                    String::build('publicDownloadDirectory', 'Public Download Directory'),
                    String::build('siteTimeZone', 'Time Zone', date_default_timezone_get()),
                    String::build('logDirectory', 'Log Directory', self::outerDirectory().'/logs'),
                    String::build('sqlLogs', 'SQL Log Directory', self::outerDirectory().'/logs/sql'),
                    UnsignedTinyInteger::build('devMode', 'Development Mode?')
                ]
            ],
            3=>[
                'label'=>'Scheduler Config',
                'fields'=>[
                    UnsignedTinyInteger::build('initScheduler', 'Init Scheduler?', 0),
                    String::build('schedulerTasks', 'Scheduler Tasks', implode(',', Scheduler::getAvailableTasks())),
                    String::build('scheduleProvider', 'Schedule Provider')
                ]
            ],
            4=>[
                'label'=>'Portal Config',
                'fields'=>[
                    UnsignedTinyInteger::build('adminOnly', 'Admin Only Mode?'),
                    UnsignedTinyInteger::build('mailOff', 'Email: Disable?'),
                    String::build('passwordMaxLength', 'User Password: Max Length'),
                    String::build('passwordMinLength', 'User Password: Min Length'),
                    UnsignedTinyInteger::build('passwordRequiresLetter', 'User Password: Requires Letter?'),
                    UnsignedTinyInteger::build('passwordRequiresNumber', 'User Password: Requires Number?'),
                    String::build('portalControllers', 'Portal Controllers List'),
                    String::build('productTitle', 'Product Title'),
                    String::build('productDescription', 'Product Description'),
                    String::build('productLogo', 'Product Logo'),
                    String::build('productSmallLogo', 'Product Small Logo'),
                    String::build('supportString', 'Support String')
                ]
            ]
        ];

        self::loadJSONPresets();

        foreach($formData as $fd) {
            self::setFormDefaults($fd['fields']);
        }

        return self::getForm('config/'.$page, $formData[$page]['label'], $formData[$page]['fields']);
    }

    /**
     * Get the create admin user form
     * @return Form
     */
    public static function getUserForm() {
        $fields = [
            String::build('firstName', 'First Name')->setRequired(),
            String::build('lastName', 'Last Name')->setRequired(),
            Email::build('email', 'Email Address')->setRequired(),
            Email::build('email2', 'Repeat Email Address')->setRequired(),
            PhoneNumber::build('phone', 'Phone Number')->setRequired(),
            Password::build('password', 'Password')->setRequired(),
            Password::build('password2', 'Repeat Password')->setRequired()
        ];

        return self::getForm('user', 'Create an Admin User', $fields, [
            function ($keydict) {
                if ($keydict->password->get() != $keydict->password2->get()) {
                    throw new KeydictException("Passwords don't match!");
                }
            },
            function ($keydict) {
                if($keydict->email->get() != $keydict->email2->get()) {
                    throw new KeydictException("Email addresses don't match!");
                }
            }
        ]);
    }

    /**
     * Process SSL options form - right now just creates ssl-opt.php with
     * REQUIRE_SSL define.
     * @param Keydict $keydict
     * @return string
     */
    public static function processSSLOptForm(Keydict $keydict) {
        $requireSSL = (int)$keydict->requireSSL->get();

        self::writeDefines(APP_PATH.self::CONFIG_SSL_OPT, [
            'REQUIRE_SSL'=>$requireSSL
        ]);

        if($requireSSL) {
            return '/setup/ssl';
        }
        else {
            return '/setup/conn';
        }
    }

    /**
     * Process the SSL cert info form to create a CSR file and a temporary
     * (self-signed) certificate.
     * @param Keydict $keydict
     * @return string
     * @throws \Exception
     */
    public static function processSSLForm(Keydict $keydict) {
        self::sslMakeCSR([
            'countryName'=>$keydict->countryName->get(),
            'stateOrProvinceName'=>$keydict->stateOrProvinceName->get(),
            'localityName'=>$keydict->localityName->get(),
            'organizationName'=>$keydict->organizationName->get(),
            'organizationalUnitName'=>$keydict->organizationalUnitName->get(),
            'commonName'=>$keydict->commonName->get(),
            'emailAddress'=>$keydict->emailAddress->get()
        ]);
        self::sslMakeTempCertificate();
        return '/setup/ssl/interstitial';
    }

    /**
     * @param Keydict $keydict
     * @return string
     * @throws \Exception
     * Process the DB connection form
     */
    public static function processConnForm(Keydict $keydict) {
        $connection = new \mysqli($keydict->dbHost->get(), $keydict->dbUser->get(), $keydict->dbPassword->get(), $keydict->dbName->get());
        if($connection->stat()) {
            self::writeDefines(APP_PATH . self::CONFIG_CONN, [
                'MYSQL_HOST' => $keydict->dbHost->get(),
                'MYSQL_USER' => $keydict->dbUser->get(),
                'MYSQL_PASSWORD' => $keydict->dbPassword->get(),
                'MYSQL_DATABASE' => $keydict->dbName->get()
            ]);

            self::initDB($keydict->resetDB->get());
            self::initMemcached();
            self::initSysVar();

            return '/setup/config';
        }
        throw new \Exception("Errors in Database Configuration, please check that your input fields are correct.".$connection->get_warnings());
    }

    /**
     * Process the DB reset form i.e. reset the database if the user asked for it.
     * @param Keydict $keydict
     */
    public static function processDBResetForm(Keydict $keydict) {
        if($keydict->resetDB->get()) {
            self::initDB(true);
            self::initMemcached();
            self::initSysVar();
        }

        return '/setup/config';
    }

    /**
     * Process the SysVar/global config form
     * @param Keydict $keydict
     * @return string
     * @throws \Exception
     */
    public static function processConfigForm(Keydict $keydict) {
        global $pool;

        foreach($keydict->allFields() as $field) {
            /**
             * @var Entry $field
             */
            if($pool->SysVar->has($field->getName())) {
                $pool->SysVar->update($field->getName(), $field->get());
            }
            else {
                $pool->SysVar->add($field->getName(), $field->get());
            }
        }

        $nextPage = (int)$pool->SysVar->get('configStep') + 1;

        if($nextPage >= self::CONFIG_NUM_PAGES) {
            self::afterConfig($keydict);
            return 'user';
        }
        else {
            $pool->SysVar->update('configStep', $nextPage);
            return '/setup/config/'.$nextPage;
        }
    }

    /**
     * Process the create admin user form
     * @param Keydict $keydict
     * @return string
     * @throws Keydict\Exception\InvalidValueException
     * @throws MySQLException
     */
    public static function processUserForm(Keydict $keydict) {
        $user = User::register(
            $keydict->firstName->get(),
            $keydict->lastName->get(),
            $keydict->email->get(),
            $keydict->password->get(),
            $keydict->phone->get()
        );

        if($user) {
            $data = DB::sql("INSERT INTO UserRoles (idUser,idSystemRole) VALUES ({$user->getIdUser()},1)");

            self::writeDefines(APP_PATH.self::CONFIG_USER, $data);

            return '/login';
        }
        else {
            return '/setup/user';
        }
    }

    /**
     * Process a form containing a user-uploaded CRT file. If a signed
     * certificate can be generated from this file, install it and restart
     * apache.
     * @param Keydict $keydict
     * @return string
     * @throws \Exception
     */
    public static function processSSLSign(Keydict $keydict) {
        if(isset($_FILES['fileupload'])) {
            $f = $_FILES['fileupload'];
            $sslDir = self::sslDir();

            move_uploaded_file($f['tmp_name'], $sslDir.self::SSL_CRT);
            if(self::sslMakeSignedCertificate()) {
                self::sslInstallCert();
                return '/';
            }
            else {
                return '/setup/ssl/sign';
            }
        }
        else {
            return '/setup/ssl/sign';
        }
    }

    /**
     * Finish portal setup after config data is set
     * @param Keydict $keydict
     */
    public static function afterConfig(Keydict $keydict) {
        global $pool;

        /** TODO generate unique addressHashSalt OR do we actually need it? */
        self::sysVarFromDefaults(['addressHashSalt']);

        /** TODO Remove userSalt entirely - it is no longer needed for auth because the salt is stored with the password.  */
        $pool->SysVar->add('userSalt', 'yVBG*4%35^hp#hGm$&*H');
        $pool->SysVar->add('publicDownloadURL', 'http://storage.googleapis.com/'.$pool->SysVar->get('googleUploadBucket'));

        self::initFileSystem();
        self::initRoles();

        if($pool->SysVar->get('initScheduler')) {
           self::initSchedulerTasks();
        }

        self::writeDefines(APP_PATH.self::CONFIG_PORTAL, [
            'PORTAL_CONFIG'=>1
        ]);
    }
}