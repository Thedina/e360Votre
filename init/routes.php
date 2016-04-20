<?php
use App\System;
use eprocess360\v3core\Request\Request;
use eprocess360\v3core\Toolbox;

global $pool;

$devMode = (bool)$pool->SysVar->get('devMode');
$sqlLogs = $pool->SysVar->get('sqlLogs');

if($devMode && $sqlLogs)
    file_put_contents ($sqlLogs."/".date('Y-m-d').".txt", "\nCurrent Time: ".date('Y-m-d H:i:s')." ".Request::get()->getActualRequestPath()." \n", FILE_APPEND | LOCK_EX);

$sys = System::build();
$sys->ready()->run();

if($devMode && $sqlLogs) {
    global $sql_stats;
    $writing = 'Query Count:' . $sql_stats['query_count'] . "\n";
    $writing = $writing . 'Query Time:' . $sql_stats['query_time'] . "\n";
    foreach ($sql_stats['queries'] as $query) {
        $writing = $writing . $query['query'] . " , " . $query['time'] . " , " . $query['called_by'];
        $writing = $writing . "\n";
    }
    file_put_contents($sqlLogs."/".date('Y-m-d').".txt", $writing, FILE_APPEND | LOCK_EX);
}