<?php
namespace eprocess360\v3core;
use eprocess360\v3core\Updater\Client;
use eprocess360\v3core\Updater\Exceptions\ClientInvalidException;
use eprocess360\v3core\Updater\Exceptions\DatabaseUnavailableException;
use eprocess360\v3core\Updater\Exceptions\ModuleNotReadyException;
use eprocess360\v3core\Updater\Exceptions\SettingsUnavailableException;
use eprocess360\v3core\Updater\Exceptions\TableUnavailableException;
use eprocess360\v3core\Updater\HttpRequest;
use eprocess360\v3core\Updater\InterfaceModule;
use eprocess360\v3core\Updater\Module\SQLSync;
use eprocess360\v3core\Updater\Request;
use eprocess360\v3core\Updater\Response;
use eprocess360\v3core\Updater\System;
use eprocess360\v3core\Updater\HttpAccept;
use kirakero\doujinpressv2\Router;
/**
 * @property  int cfg_client_id
 * @property  string cfg_master_host
 * @property  string cfg_requests_table
 * @property  string cfg_auth_table
 * @property  string cfg_attachment_path
 * @property  GlobalId global_id
 * @property  string cfg_master
 * @property  int cfg_master_id
 */
class Updater implements InterfaceModule
{
    /**
     * @var Updater The reference to *Singleton* instance of this class
     */
    private static $instance;
    /** @var Router $router */
    private $router;
    const MODULE_NAME = 'updater';
    const PRIVATE_KEY = '8zikF6dfdfjLk7ULieEnrMnwvcQCGqUV';

    public function getModuleName()
    {
        return self::MODULE_NAME;
    }

    public function getModuleSpace()
    {
        return self::MODULE_NAME;
    }

    public function getModulePath()
    {
        return '/update';
    }

    public function hasManualSettings()
    {
        return true;
    }

    /**
     * Returns the *Singleton* instance of this class.
     *
     * @return Updater The *Singleton* instance.
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    protected function __construct()
    {
        $this->local_db = MYSQL_DATABASE;
        $this->sysvar = 'updaterCFG';
        $this->data = [];
        $this->focus = null;
        $this->modules = [];
        /** @var Client client */
        $this->client = new Client();
        $this->system = new System();
        $this->menus = [];
    }

    public function __get($key)
    {
        if (isset($this->data[$key]))
            return $this->data[$key];
        else
            throw new \Exception("Key {$key} does not exist.", 500);
    }

    public function __isset($key)
    {
        if (isset($this->data[$key]))
            return true;
        else
            return false;
    }

    public function route($dir)
    {
        global $twig;
        $response_json = [];
        $response_html = '';
        $parameters = []; //unused
        $exception = false;
        $api = false;
        if (is_array($dir)) {
            $parameters = $dir;
            $dir = $dir[0];
        }
        try {
            // save basic settings
            if (isset($_POST['form_submit']) && $_POST['module'] == '/') {
                $save = [];
                $this->checkDatabaseReady();
                $settings = $this->defaultSettings();
                foreach ($settings as $key => $value) {
                    if (isset($_POST[$key])) {
                        $save[$key] = $_POST[$key];
                    }
                }
                unset($key);
                if (sizeof($save)==sizeof($settings)) {
                    $this->saveSettings($save);
                } else {
                    throw new \Exception('Invalid request.');
                }
            }
            // ready check
            try {
                $this->ready();
            } catch (SettingsUnavailableException $e) {
                // show page to configure settings
                $settings = $this->defaultSettings();
                $response_html = $twig->render('updater.settings.html.twig', ['settings' => $settings, 'labels' => $this->availableSettings(), 'module' => '/', 'first_time' => true, 'exception' => ['message' => $e->getMessage()]]);
                $exception = true;
            } catch (TableUnavailableException $e) {
                // make tables
                $this->dropTables();
                $this->createTables();
            }

            if ($exception) {
                if ($response_html) return $response_html;
                elseif ($response_json) return $response_json;
                else throw new \Exception('Empty response.', 500);
            }
            // create router
            $this->router = new Router($this->getModulePath());

            // load request routes
            $this->loadRoutes($this, $this->router);

            // load modules
            $this->loadModules();
            $modules_loaded = false;
            $ignore_post = false;
            while (!$modules_loaded && !$exception) {
                try {
                    $this->modulesReady();
                    $modules_loaded = true;
                } catch (ModuleNotReadyException $e) {
                    // the focus module is not ready
                    /** @var InterfaceModule $module */
                    $module = $this->focus;
                    $module_okay = false;
                    if ($module->hasManualSettings()) {
                        $save = [];
                        $settings = $module->defaultSettings();

                        if (!$ignore_post && $_POST['form_submit'] && $_POST['module'] == get_class($module)) {

                            foreach ($settings as $key => $value) {
                                if (isset($_POST[$key])) {
                                    $save[$key] = $_POST[$key];
                                }
                            }
                            unset($key);
                            if (sizeof($save)==sizeof($settings)) {
                                try {
                                    $module->register($save);
                                    $module->createTables();
                                    $module_okay = true;
                                    $ignore_post = true;
                                } catch (\Exception $e) {

                                }
                            }
                        }
                        if (!$module_okay) {
                            $response_html = $twig->render('updater.settings.html.twig', ['settings' => $settings, 'labels' => $module->availableSettings(), 'module' => get_class($module), 'module_name' => $module->getModuleName(), 'first_time' => true, 'exception' => ['message' => $e->getMessage()]]);
                            $exception = true;
                        }
                    } else {
                        $module->register($module->defaultSettings());
                    }
                }
            }

            if ($exception) {
                if ($response_html) return $response_html;
                elseif ($response_json) return $response_json;
                else throw new \Exception('Empty response.', 500);
            }
            try {
                $this->client = new Client($this->cfg_client_id);
                if (!$this->client->is_valid) {
                    throw new ClientInvalidException("Client id {$this->cfg_client_id} is not a valid client.");
                }
            } catch (\Exception $e) {
                // system needs to register self
                if (self::isSecurityKey($this->cfg_master)) {
                    $this->doPromoteSelfToMaster();
                } else {
                    $this->setMaster(Client::addFromDigest($this->cfg_master));
                    $this->doRegisterSelfWithMaster();
                }
            }

            $return = $this->router->response();

            if (!strlen($return)) {
                throw new \Exception('Empty response.', 500);
            }

            return $return;
        } catch (\Exception $e) {
            return $api ? self::JSON_ERROR($e) : self::HTML_ERROR($e);
        }
    }

    public function debug($string)
    {
        var_dump($string);
    }

    protected function setMaster(Client $client)
    {
        $this->saveSettings(['cfg_master_id'=>$client->getClientId()]);
    }

    protected static function isSecurityKey($key)
    {
        $results = preg_match('/^#[a-zA-Z0-9]{32}#$/', $key);
        return $results;
    }

    public function htmlMethods() {
        global $twig;
        $interface_base = [
            'menus' => $this->menus, // $this->getMenu()
            'updater' => $this
        ];
        $methods = [
            '' => function () use ($interface_base, $twig) {
                $this->data['cfg_master'] = $this->getIsMaster() ? $this->client->getDigest() : $this->data['cfg_master'];
                $interface_data = array_merge($interface_base, ['client'=> $this->client, 'settings' => $this->data, 'labels'=> $this->availableSettings(), 'modules' => $this->modules]);
                return $twig->render('updater.status.html.twig', $interface_data);
            },
            '/clients/:offset?' => function ($args) use ($interface_base, $twig) {
                // list clients with pagination
                $results = $this->getClients(isset($args->offset)?$args->offset:null);
                $interface_data = array_merge($interface_base, ['results'=>$results]);
                return $twig->render('updater.clients.html.twig', $interface_data);
            },
            '/client/:idclient' => function ($args) use ($twig) {
                $client = new Client($args->idclient);
                return $twig->render('updater.client.view.html.twig', [
                    'menus'=>$this->getMenu(),
                    'client'=>$client
                ]);
            },
            '/requests/:offset?' => function ($args) use ($interface_base, $twig) {
                // list requests with pagination
                $results = $this->getRequests(isset($args->offset)?$args->offset:null);
                $interface_data = array_merge($interface_base, ['results'=>$results]);

                return $twig->render('updater.requests.html.twig', $interface_data);
            },
            '/request/:idrequest' => function ($args) use ($twig) {
                $request = new Request($args->idrequest);
                if ($request->hasParent()) {
                    $request->loadParent();
                }
                if ($request->hasChild()) {
                    $request->loadChild();
                }
                return $twig->render('updater.request.view.html.twig', [
                    'menus'=>$this->getMenu(),
                    'request'=>$request
                ]);
            },

            '/remove' => function ($confirm = false) use ($interface_base, $twig) {
                $interface_data = array_merge($interface_base, ['state'=>$confirm]);
                return $twig->render('updater.remove.html.twig', $interface_data);
            },
            '/settings' => function () use ($interface_base, $twig) {
                $interface_data = array_merge($interface_base, ['client'=> $this->client, 'settings' => $this->data, 'labels'=> $this->availableSettings(), 'modules' => $this->modules]);
                return $twig->render('updater.settings.html.twig', $interface_data);
            },
            '/debug/routes' => function () {
                ob_start();
                var_dump(array_keys($this->router->routes['GET']));
                $return = ob_get_contents();
                ob_end_clean();
                return $return;
            },
            '/debug/menus' => function () {
                ob_start();
                var_dump($this->getMenu());
                $return = ob_get_contents();
                ob_end_clean();
                return $return;
            }
        ];
        return $methods;
    }

    public function getModuleSubSpace()
    {
        return '';
    }

    public static function loadRoutes(InterfaceModule $module, Router $router)
    {
        foreach ($module->jsonMethods() as $path=>$function) {
            $router->request(['GET','POST'], '/api'.$module->getModuleSubSpace().$path, $function);
        }
        foreach ($module->htmlMethods() as $path=>$function) {
            $router->request(['GET','POST'], $module->getModuleSubSpace().$path, $function);
        }
    }

    /**
     * @param Request $request
     * @return Request
     * @throws ClientInvalidException
     * @throws SQLSync\Exception\InvalidRequestException
     * @throws \Exception
     */
    public function sendRequestFacade(Request $request)
    {
        $http_request = new Updater\HttpRequest($request);
        $http_request->sign()->addressTo($this->getMaster());
        $response = $http_request->send();

        if ($response->validateResponse()) {
            $response_request = Updater\Request::fromHttpAcceptBody($response);
            $response_request->storeStatus();
            return $response_request;
        } else {
            $request->update(Updater\Request::STATUS_FAILED);
            throw new \Exception('Response did not validate.');
        }
    }

    public function jsonMethods()
    {
        $methods = [
            '/request/accept' => function () {
                try {
                    $httpAccept = HttpAccept::fromPhpInput();
                    $httpAccept->validateRequest();
                    $request = Request::store($httpAccept);
                    $response = new Response($httpAccept);
                    $response->addReceipt($request->update(Request::STATUS_RECEIVED));
                    $response->sign();
                    $package = $response->getPackage();
                    return json_encode($package);
                } catch (\Exception $e) {
                    return self::JSON_ERROR($e);
                }
            },
            '/register' => function () {
                try {
                    $httpAccept = HttpAccept::fromPhpInput();
                    $httpAccept->validateRequest();
                    $system = System::fromHttpAccept($httpAccept);
                    $client = Client::create($system);
                    $response = new Response($httpAccept);
                    $response->setBody([
                        'digest' => $client->getDigest()
                    ]);
                    $response->sign();
                    $package = $response->getPackage();
                    return json_encode($package);
                } catch (\Exception $e) {
                    return self::JSON_ERROR($e);
                }
            },
            '/next' => function () {

            }
        ];
        return $methods;
    }

    public function hasJsonMethod($function)
    {
        $json = $this->jsonMethods();
        return isset($json[$function]);
    }

    public function availableSettings()
    {
        return [
            'cfg_auth_table' => 'Auth Table',
            'cfg_requests_table' => 'Requests Table',
            'cfg_master' => 'Master Digest',
            'cfg_attachment_path' => 'Attachment Directory'
        ];
    }

    public function getIsMaster()
    {
        return isset($this->data['cfg_is_master']) ? $this->data['cfg_is_master'] : false;
    }

    public function getMasterPrivateKey()
    {
        if ($this->getIsMaster()) {
            return $this->data['cfg_master'];
        } else {
            return substr($this->data['cfg_master'],0,34);
        }
    }

    /**
     * Return an array of Client from the database
     *
     * @param mixed $offset
     * @return array
     */
    private function getClients($offset = false) {
        if ($offset) {
            $offset = (int)$offset;
            $offset = "WHERE `client_id` > {$offset}";
        }
        $sql = "SELECT * FROM `{$this->local_db}`.`{$this->cfg_auth_table}` {$offset} ORDER BY `client_id` ASC LIMIT 30";
        $out = [];
        if ($results = sql($sql)) {
            foreach ($results as $result) {
                $client = new Client(0);
                $client->loadRaw($result);
                $out[] = $client;
            }
            return $out;
        }
        return [];
    }

    /**
     * Return an array of Request from the database
     *
     * @param mixed $offset
     * @return array
     */
    private function getRequests($offset = null) {
        if ($offset!=null) {
            $offset = $this->global_id->cleanse($offset);
            $offset = "WHERE `request_id` > {$offset}";
        }
        $sql = "SELECT *, CAST(SUBSTR(request_id, -6) AS UNSIGNED) as origin_client_id FROM `{$this->local_db}`.`{$this->cfg_requests_table}` {$offset} ORDER BY `request_id` DESC LIMIT 30";
        $out = [];
        if ($results = sql($sql)) {
            foreach ($results as $result) {
                $request = new Request();
                $request->loadRaw($result);
                $out[] = $request;
            }
            return $out;
        }
        return [];
    }

    private function loadModules()
    {
//        $modules = [];
//        foreach (get_declared_classes() as $className) {
//           var_dump(class_implements($className));
//           if (in_array('eprocess360\v3core\Updater\InterfaceModule', class_implements($className))) {
//                $modules[$className] = new $className;
//            }
//        }
//        var_dump($modules);
//        $this->modules = $modules;
        $this->modules = [
            'sqlsync' => new SQLSync($this)
        ];
    }

    /**
     * Load the updater settings
     */
    private function loadSettings()
    {
        global $pool;
        $data = $pool->SysVar->get($this->sysvar);
        if ($data && $data = json_decode($data, true)) {
            foreach ($this->defaultSettings() as $k=>$v) {
                if (!isset($data[$k]) || ($v && !$data[$k])) {
                    throw new SettingsUnavailableException("Setting '{$k}' is missing.");
                }
            }
            $path = $data['cfg_attachment_path'];
            if (!is_writable($path)) {
                throw new SettingsUnavailableException("Attachment storage directory '{$data['cfg_attachment_path']}' is not writeable.");
            }
            $this->data = $data;
            $this->global_id = new GlobalId($this->data['cfg_client_id']);
        } else {
            throw new SettingsUnavailableException("Could not load Updater settings.");
        }
    }

    /**
     * Verify ready or throw useful Exception
     *
     * @throws DatabaseUnavailableException
     * @throws SettingsUnavailableException
     * @throws TableUnavailableException
     */
    public function ready()
    {
        $this->checkDatabaseReady();
        $this->loadSettings();
        $this->checkTablesReady();
    }

    private function setFocus(InterfaceModule $module)
    {
        $this->focus = $module;
    }

    private function modulesReady()
    {
        /** @var InterfaceModule $module */
        foreach ($this->modules as $name => $module) {
            try {
                $module->ready();
                if ($module->hasMenu()) {
                    $this->addMenu($module);
                }
                self::loadRoutes($module, $this->router);
            } catch (ModuleNotReadyException $e) {
                $this->setFocus($module);
                throw $e;
            }
        }
    }

    public function getMenu()
    {
        $menu = $this->getMenuOptions();
        $key = function ($options) {
            foreach ($options as $k=>$o) {
                if (isset($o['insert']) && $o['insert']=='children') {
                    return $k;
                }
            }
            return null;
        };
        if (sizeof($this->menus) && $key = $key($menu) !== null) {
            array_splice($menu,$key+2,1,$this->getChildMenus());
        }
        return $menu;
    }

    public function getChildMenus()
    {
        $menus = [];
        foreach ($this->menus as $module) {
            /** @var InterfaceModule $module */
            if ($module->hasMenu()) {
                $menus[] = $this->prefixChildMenu($module->getMenuOptions());
            }
        }
        return $menus;
    }

    public function hasMenu()
    {
        return true;
    }

    public function prefixChildMenu($option)
    {
        if (is_array($option)) {
            if (isset($option[0])) {
                foreach ($option as &$suboption) {
                    $suboption = $this->prefixChildMenu($suboption);
                }
            } elseif (isset($option['method'])) {
                $option['method'] = $this->getModulePath() . $option['method'];
            } else {
                return $option;
            }
        }
        return $option;
    }

    public function getMenuOptions()
    {
        return [
            [ // key => 0
                'method'=>  '/status',
                'label'=>   'Status'
            ],
            [ // key => 1
                'method'=>  '/requests',
                'label'=>   'Requests'
            ],
            [ // key => 2
                'method'=>  '/clients',
                'label'=>   'Clients'
            ],
            [
                'insert'=>  'children'
            ],
            [
                'label'=>   'More',
                'items'=>   [
                    [
                        'method'=>  'Settings',
                        'label'=>   '/settings'
                    ],
                    [
                        'method'=>  'Modules',
                        'label'=>   '/modules'
                    ]
                ]
            ]
        ];
    }

    private function addMenu(InterfaceModule $module)
    {
        $this->menus[] = $module;
        return sizeof($this->menus) - 1;
    }

    private function checkTablesReady()
    {
        $results = sql("SHOW TABLES LIKE '{$this->cfg_requests_table}'");
        if (!$results || sizeof($results) == 0) {
            throw new TableUnavailableException("Table `{$this->cfg_requests_table}` is missing.");
        }
        $results = sql("SHOW TABLES LIKE '{$this->cfg_auth_table}'");
        if (!$results || sizeof($results) == 0) {
            throw new TableUnavailableException("Table `{$this->cfg_requests_table}` is missing.");
        }
    }

    /**
     * Verify the database is available
     *
     * @throws \Exception
     */
    private function checkDatabaseReady()
    {
        $results = sql("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$this->local_db}'");
        if ($results === false) {
            global $mysqli_error;
            throw new DatabaseUnavailableException('MySQL failed in checkDatabaseReady() with error: '.$mysqli_error);
        }
        if (sizeof($results) == 0) {
            throw new DatabaseUnavailableException("Create database in MySQL first.  Seeking database: {$this->local_db}");
        }
    }

    /**
     * Make self master system
     *
     * @throws \Exception
     */
    private function doPromoteSelfToMaster()
    {
        $system = new System();
        $this->client = Client::create($system);
        try {
            $this->saveSettings(['cfg_is_master'=>1, 'cfg_client_id'=>$this->client->getClientId()]);
        } catch (\Exception $e) {
            Client::remove($system);
            throw new \Exception("Failed to promote to master: The new configuration could not be saved.  Recommend delete client_id {$this->client->getClientId()}.");
        }
    }

    public function getMaster()
    {
        return new Client($this->getMasterId());
    }

    public function getMasterId()
    {
        return $this->cfg_master_id;
    }

    /**
     * Registers the current system with the master
     *
     * @throws \Exception
     */
    private function doRegisterSelfWithMaster()
    {
        if ($this->getIsMaster()) {
            throw new \Exception('Cannot make request to master when this system is the master.');
        }
        $contents = $this->system;
        $request = Request::create($this,'/register', true);
        $http_request = new HttpRequest($request);
        $http_request->setHeaders($contents->getContents());
        $http_request->sign(true)->addressTo($this->getMaster());
        $response = $http_request->send();
        if ($response->validateResponse()) {
            $this->client = Client::addFromDigest($response->getBody('digest'));
            $this->saveSettings(['cfg_client_id'=>$this->client->getClientId()]);
            $request->update(Request::STATUS_COMPLETE);
            return;
        }
        throw new \Exception('Failed to register self with master.');
    }

    /**
     * Get Module setting with key
     *
     * @param InterfaceModule $module
     * @param $key
     * @return mixed
     * @throws \Exception
     */
    public function getModuleSetting(InterfaceModule $module, $key)
    {
        $class = str_replace('\\','_',get_class($module));
        if (isset($this->data[$class][$key])) {
            return $this->data[$class][$key];
        }
        throw new \Exception("Key {$key} does not exist.");
    }

    /**
     * Check is module has a variable with key set
     *
     * @param InterfaceModule $module
     * @param $key
     * @return bool
     */
    public function issetModuleSetting(InterfaceModule $module, $key)
    {
        $class = str_replace('\\','_',get_class($module));
        if (isset($this->data[$class][$key])) {
            return true;
        }
        return false;
    }

    /**
     * Save an array into the settings for Updater
     *
     * @param $settings_array
     */
    public function saveSettings($settings_array)
    {
        global $pool;
        $cfg = $pool->SysVar->get($this->sysvar);
        $cfg = json_decode($cfg, true);
        if (!$cfg) $cfg = [];
        $cfg = array_merge($cfg, $settings_array);
        $pool->SysVar->add($this->sysvar, json_encode($cfg));
        $this->data = $cfg;
    }

    /**
     * Save the modules settings
     *
     * @param InterfaceModule $module
     * @param $settings_array
     */
    public function saveModuleSettings(InterfaceModule $module, $settings_array)
    {
        global $pool;
        $cfg = $pool->SysVar->get($this->sysvar);
        $cfg = json_decode($cfg, true);
        $cfg = array_merge($cfg, [
            str_replace('\\','_',get_class($module)) => $settings_array
        ]);
        $pool->SysVar->add($this->sysvar, json_encode($cfg));
        $this->data = $cfg;
    }

    /**
     * Remove the specified module keys from the settings
     *
     * @param $module InterfaceModule
     */
    public function removeModuleSettings(InterfaceModule $module)
    {
        global $pool;
        $cfg = $pool->SysVar->get($this->sysvar);
        $cfg = json_decode($cfg, true);
        unset($cfg[str_replace('\\','_',get_class($module))]);
        $pool->SysVar->add($this->sysvar, json_encode($cfg));
        $this->data = $cfg;
    }

    /**
     * Store settings
     * @param $settings_array
     */
    public function register($settings_array)
    {
        global $pool;
        $this->data = $settings_array;
        $pool->SysVar->add($this->sysvar, json_encode($this->data));
    }

    /**
     * Default settings for Updater
     */
    public function defaultSettings()
    {
        return [
            'cfg_auth_table' => 'updater_auth',
            'cfg_requests_table' => 'updater_requests',
            'cfg_master' => '#yaKW9wqquOQaYkXkC3ltTthoGFpjECnA#1#eb0a1917090736069rWfzH1t4oNaWfJi#master#master.eprocess360.com#23.236.60.250#http##29d27872365080f4b4b333b7a8df12f3',
            'cfg_attachment_path' => APP_PATH.'/data'
        ];
    }

    /**
     * Remove all Updater settings and module settings from system
     */
    public function unregister()
    {
        global $pool;
        $pool->SysVar->get($this->sysvar,'');
    }

    /**
     * Drop Updater tables, also kills module tables
     */
    public function dropTables()
    {
        /** @var InterfaceModule $module */
        foreach ($this->modules as $module) {
            $module->dropTables();
        }
        sql("DROP TABLE IF EXISTS `{$this->local_db}`.`{$this->cfg_auth_table}`, `{$this->local_db}`.`{$this->cfg_requests_table}`");
    }

    /**
     * Create the tables used by Updater
     */
    public function createTables()
    {
        // create auth
        sql("CREATE TABLE `{$this->local_db}`.`{$this->cfg_auth_table}` (
          `client_id` BIGINT(20) UNSIGNED PRIMARY KEY NOT NULL AUTO_INCREMENT,
          `client_auth` CHAR(32) NOT NULL,
          `system_id` CHAR(32),
          `system_host` CHAR(32),
          `system_ip` CHAR(15),
          `system_protocol` CHAR(5),
          `lastsync` TIMESTAMP NULL DEFAULT NULL
        ) CHAR SET utf8");

        // create requests
        sql("CREATE TABLE `{$this->local_db}`.`{$this->cfg_requests_table}` (
          `request_id` BIGINT(20) UNSIGNED NOT NULL,
          `client_id` INT(8) NOT NULL,
          `lastupdated` TIMESTAMP,
          `module` CHAR(16),
          `function` CHAR(16),
          `status` TINYINT(1) UNSIGNED,
          `attachment` TINYINT(1) UNSIGNED,
          `hash` CHAR(16),
          `parent` BIGINT(20) UNSIGNED,
          `child` BIGINT(20) UNSIGNED,
          PRIMARY KEY (`request_id`)
        ) CHAR SET utf8");

    }

    public static function ERROR_NOT_MASTER()
    {
        return new \Exception("Not a master server.", 501);
    }

    private static function HTML_ERROR()
    {
        if (func_num_args()) {
            /* @var $exception \Exception */
            $exception = func_get_args()[0];
        } else {
            $exception = new \Exception('System internal error.', 500);
        }
        $code = $exception->getCode();
        $message = $exception->getMessage();
        global $twig;
        ob_start();
        debug_print_backtrace();
        $trace = ob_get_contents();
        ob_end_clean();
        return $twig->render('updater.SystemController.error.html.twig',['code'=>$code, 'message'=>$message, 'trace'=>$trace]);
    }

    public static function JSON_ERROR()
    {
        if (func_num_args()) {
            /* @var $exception \Exception */
            $exception = func_get_args()[0];
        } else {
            $exception = new \Exception('System internal error.', 500);
        }
        return json_encode([
            'response' => [
                'status'=>$exception->getCode(),
                'errormessage'=>$exception->getMessage()
            ]
        ]);
    }

    private static function JSON_RESPONSE($data = [])
    {
        $data = array_merge(['response'=>['code'=>200]], $data);
        return json_encode($data);
    }

    public function getClient()
    {
        return $this->client;
    }

}