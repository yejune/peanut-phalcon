<?php
namespace Peanut;

class Store
{
    protected static $_instance;
    private $_variables = [];

    /**
     * reset variables
     */
    public function __destruct()
    {
        static::instance()->_variables = null;
    }

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
     * @param null|mixed $key
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
     * @param mixed $arg
     */
    public static function append($arg)
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
}
