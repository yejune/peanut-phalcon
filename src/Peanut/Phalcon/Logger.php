<?php
namespace Peanut\Phalcon;

class Logger extends \Phalcon\Logger
{
    private static $instance = null;

    public static function adapter($driver = false)
    {
        if (!$driver) {
            $driver = 'php://stderr';
        }
        if (null === static::$instance) {
            if (false === strpos($driver, 'php://')) {
                static::$instance = new \Phalcon\Logger\Adapter\File(str_replace('./', __BASE__.'/', $driver));
            } else {
                static::$instance = new \Phalcon\Logger\Adapter\Stream($driver);
            }
        }

        return static::$instance;
    }
}
