<?php
    date_default_timezone_set('America/Los_Angeles');
    set_time_limit((int)TEST_TIMEOUT);
    define('APP_PATH',strstr(dirname(__FILE__), 'eprocess360', true));

    require_once APP_PATH.'vendor/autoload.php';
    require_once APP_PATH.'includes/_debug.php';
    require_once APP_PATH.'includes/util.php';
    require_once APP_PATH . 'eprocess360/v3core/tests/base/DBTestBase.php';
?>