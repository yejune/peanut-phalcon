<?php
namespace Peanut;

class Store
{
    private $_variables = [];
    protected static $_instance;

    /**
     * Singleton instance
     */
    public static function instance()
    {
        if (null === static::$_instance) {
            static::$_instance = new static();
        }

        return static::$_instance;
    }

    /**
     * get variables
     */
    public static function get($key = null)
    {
        if ($key && true === isset(static::instance()->_variables[$key])) {
            return static::instance()->_variables[$key];
        }

        return static::instance()->_variables;
    }

    /**
     * set variables
     */
    public static function set($arg)
    {
        if (true === is_array($arg)) {
            static::instance()->_variables = $arg + static::instance()->_variables;
        } else {
            if (count(func_get_args()) > 1) {
                $val = func_get_arg(1);
                if (true === is_array($val)) {
                    if (false === isset(static::instance()->_variables[$arg])) {
                        static::instance()->_variables[$arg] = [];
                    }
                    static::instance()->_variables[$arg] = $val + static::instance()->_variables[$arg];
                } else {
                    static::instance()->_variables[$arg] = $val;
                }
            }
        }
    }

    /**
     * reset variables
     */
    public function __destruct()
    {
        static::instance()->_variables = null;
    }
}
