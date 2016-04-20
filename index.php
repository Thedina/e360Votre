<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);
global $pool, $twig, $gp;
define ('APP_PATH',dirname(__FILE__));
define ('API_VERSION',1);
require_once(APP_PATH."/eprocess360/v3core/src/Configuration.php");
use eprocess360\v3core\Configuration;

//$usingSSL = isset($_SERVER['HTTPS']) && $_SERVER['SERVER_PORT'] == 443;
$selfReferral = isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) !== false;
$hasSSLOpt = false;
$sslCSRExists = Configuration::sslCSRExists();
$hasConnection = false;
$hasConfig = false;
$hasUser = false;


if(file_exists(APP_PATH."/config/ssl-opt.php")) {
    require_once(APP_PATH."/config/ssl-opt.php");
    $hasSSLOpt = true;
    if (file_exists(APP_PATH . "/config/conn.php")) {
        $hasConnection = true;
        require_once(APP_PATH . "/config/conn.php");

        if (file_exists(APP_PATH . "/config/portal.php")) {
            $hasConfig = true;
        }

        $hasUser = Configuration::checkUserExists();
    }
}

date_default_timezone_set('America/Los_Angeles');

try {
    // Load includes, database and connectors
    if($hasConfig && $hasUser) {
        require_once(APP_PATH . "/init/init.php");
    }

// Process page redirection
    ob_start();
    $uri = mod_dir();

    if(!$hasSSLOpt) {
        if($uri[1] != 'setup' || $uri[2] != 'ssl-opt') {
            header('Location: /setup/ssl-opt');
        }
        else {
            loadSetup();
        }
    }
    elseif(defined('REQUIRE_SSL') && REQUIRE_SSL && !$sslCSRExists) {
        if($uri[1] != 'setup' || $uri[2] != 'ssl') {
            header('Location: /setup/ssl');
        }
        else {
            loadSetup();
        }
    }
    elseif(defined('REQUIRE_SSL') && REQUIRE_SSL && $uri[1] == 'setup' && $uri[2] == 'ssl' && !$hasUser) {
        if($uri[3] == 'interstitial') {
            loadSetup();

            if(!Configuration::sslCheckCertInstalled()) {
                Configuration::sslInstallCert();
            }
        }
        elseif($uri[3] == 'get-csr') {
            loadSetup();
        }
        else {
            header('Location: /setup/ssl/interstitial');
        }
    }
    elseif(!$hasConnection) {
        if($uri[1] != 'setup' || $uri[2] != 'conn') {
            header('Location: /setup/conn');
        }
        else {
            loadSetup();
        }
    }
    elseif(!$hasConfig) {
        if(!$selfReferral || ($uri[1] == 'setup' && $uri[2] == 'db-reset')) {
            if($uri[1] != 'setup' || $uri[2] != 'db-reset') {
                header('Location: /setup/db-reset');
            }
            else {
                loadSetup();
            }
        }
        elseif($uri[1] != 'setup' || $uri[2] != 'config') {
            header('Location: /setup/config');
        }
        else {
            loadSetup();
        }
    }
    elseif(!$hasUser) {
        if($uri[1] != 'setup' || $uri[2] != 'user') {
            header('Location: /setup/user');
        }
        else {
            loadSetup();
        }
    }
    else {
        global $pool;
        //TODO Temporary Permission Check
        $currentUser = 1000;
        $paths = [  APP_PATH."/config",
                    $pool->SysVar->get('siteCache'),
                    $pool->SysVar->get('sqlLogs'),
                    APP_PATH.$pool->SysVar->get('localUploadDirectory')];
        foreach($paths as $path){
            $pathPermissions = substr(sprintf('%o', fileperms($path)), -4);
            $reqPermissions = '0777';
            $pathOwner = 1000;
            if($pathPermissions !== $reqPermissions)
                throw new Exception("Permissions are invalid. Directory: ".$path." Permissions: ".$pathPermissions." Required Permissions: ".$reqPermissions);
            if($pathOwner !== $currentUser)
                throw new Exception("Owner is invalid. Directory: ".$path." Current Owner: ".$pathOwner." Required Owner: ".$currentUser);

        }

        require_once('init/routes.php');
    }
    ob_end_flush();
} catch (Exception $e) {
    ob_end_clean();
    if (is_object($twig)) {
        var_dump($e);
        //echo $twig->render('SystemController.error.html.twig', ['Response'=>['error'=>$e]]);
        die();
    }
    show_error($e);
}

/**
 * @return array
 */
function mod_dir() {
    $res = explode('/', (strpos($_SERVER['REQUEST_URI'], '?') ? substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '?')) : $_SERVER['REQUEST_URI']));
    $res = array_pad($res, 5, '');
    return $res;
}

/**
 * Basic Error Message Function
 * @param Exception $e
 */
function show_error(Exception $e) {
    ob_end_clean();
    echo '<html>
	<body>
	<h2>eProcess 360 Error</h2>';
    echo "Message : " . $e->getMessage().'<br/>';
    echo "Code : " . $e->getCode().'<br/><pre>';
    echo stackTrace();
    echo '</pre></body>
	</html>';
    die();
}

function loadSetup() {
    if(file_exists($currentpagefile = 'init/setup.php')) {
        require_once($currentpagefile);
    }
}

/**
 * @return string
 */
function stackTrace() {
    $stack = debug_backtrace();
    $output = 'Stack trace:' . PHP_EOL;

    $stackLen = count($stack);
    for ($i = 1; $i < $stackLen; $i++) {
        $entry = $stack[$i];

        $func = $entry['function'] . '(';
        $argsLen = count($entry['args']);
        for ($j = 0; $j < $argsLen; $j++) {
            $func .= $entry['args'][$j];
            if ($j < $argsLen - 1) $func .= ', ';
        }
        $func .= ')';

        $output .= '#' . ($i - 1) . ' ' . $entry['file'] . ':' . $entry['line'] . ' - ' . $func  .PHP_EOL;
    }

    return $output;
}
