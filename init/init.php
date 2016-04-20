<?php
namespace eprocess360\v3core;

use App\System;
const API_VERSION = 1;
const API_PATH = '/api/v'.API_VERSION;
const CONTROLLER_ID_ALLOCATION = 1023; // maximum number of auto-configured system id
const CONTROLLER_ID_ANONYMOUS_ALLOCATION = 2047; // maximum number of anonymous Controller id
const CONTROLLER_ID_DATABASE_AUTOINDEX = 2048;
error_reporting(E_ALL);
ini_set('display_errors', 'on');

// Composer
require_once(APP_PATH . "/vendor/autoload.php");

// Declare operating variables
global $debug, $debug_layout, $pool;
$debug = false;
$debug_layout = true;
$pool = Pool::getInstance();
$pool->add(SysVar::getInstance(), 'SysVar');
$pool->add((object)[], 'Temp');
//$pool->add(User::getEmpty(), 'User');

$session = Session::getInstance();
$pool->add($session, 'Session');
if($session->user->isIdentified())
    $session->user->setPermissions(true);
$pool->add($session->user, 'User');

global $twig, $twig_loader;
$twig_loader = new \Twig_Loader_Filesystem('/');
$twig = new \Twig_Environment($twig_loader, array(
    'cache' => $pool->SysVar->get('siteCache'),
    'debug' => true
));


date_default_timezone_set($pool->SysVar->get('siteTimeZone'));