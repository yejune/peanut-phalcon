<?php
namespace Peanut\Phalcon\Logger\Adapter;

class System
{
    public function debug($message)
    {
        error_log($message);
    }
    public function info($message)
    {
        error_log($message);
    }
    public function notice($message)
    {
        error_log($message);
    }
    public function warning($message)
    {
        error_log($message);
    }
    public function error($message)
    {
        error_log($message);
    }
    public function critical($message)
    {
        error_log($message);
    }
    public function log($message)
    {
        error_log($message);
    }
}
