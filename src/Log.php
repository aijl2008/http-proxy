<?php


namespace Ajl;


class Log
{
    static function debug($message)
    {
        if (defined('LOG_LEVEL_DEBUG')) {
            error_log(date('Y-m-d H:i:s') . ' DEBUG ' . $message);
        }
    }

    static function info($message)
    {
        error_log(date('Y-m-d H:i:s') . ' INFO ' . $message);
    }

    static function warning($message)
    {
        error_log(date('Y-m-d H:i:s') . ' WARNING ' . $message);
    }

    static function error($message)
    {
        error_log(date('Y-m-d H:i:s') . ' ERROR ' . $message);
    }
}