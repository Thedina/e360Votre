<?php

namespace eprocess360\v3core;


class Logger
{
    const LOG_NOTE = 0;
    const LOG_WARNING = 1;
    const LOG_ERROR = 2;
    const LOG_CRITICAL = 3;

    /**
     * Basic logging function. In theory going to add some sort of error level
     * system but not right now.
     * @param $text
     * @param null $file
     * @param int $level
     */
    public static function log($text, $filename = NULL, $level = self::LOG_NOTE) {
        global $pool;

        if(!strlen($filename)) {
            $filename = 'log-'.date('Y-m-d');
        }

        $filepath = $pool->SysVar->get('logDirectory').'/'.$filename;

        $f = fopen($filepath, 'a');
        fwrite($f, $text."\n");
        fclose($f);
    }
}