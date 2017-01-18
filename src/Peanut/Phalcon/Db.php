<?php
namespace Peanut\Phalcon;

class Db
{
    /**
     * @var mixed
     */
    public static $instance;

    /**
     * @param  $name
     * @throws \PDOException|\Exception
     * @return \Pdo
     */
    public static function name($name)
    {
        $di = \Phalcon\Di::getDefault();

        if (true === isset($di['databases'][$name])) {
            if (false === isset(self::$instance[$name])) {
                try {
                    $dsn   = explode(':', $di['databases'][$name]['dsn']);
                    $class = '\\Peanut\\Phalcon\Db\\'.ucfirst($dsn[0]);

                    self::$instance[$name] = new $class($di['databases'][$name]);
                } catch (\Throwable $e) {
                    throw new \Exception($e->getMessage());
                }
            }

            return self::$instance[$name];
        }
        throw new \Exception($name.' config not found');
    }
}
