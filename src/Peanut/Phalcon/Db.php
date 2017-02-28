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

        if (true === isset(self::$instance[$name])) {
            return self::$instance[$name];
        }
        throw new \Exception($name.' not connect');
    }

    public static function connect($name, $dsn)
    {
        if (false === isset(self::$instance[$name])) {
            try {
                $dbConfig = self::dsnParser($dsn);
                $class    = '\\Peanut\\Phalcon\\Db\\'.ucfirst($dbConfig['scheme']);

                self::$instance[$name] = new $class($dbConfig);
            } catch (\Throwable $e) {
                throw $e;
            }
        }

        return self::$instance[$name];
    }

    /**
     * @param $url
     * @return array
     */
    private static function dsnParser($url)
    {
        $dbSource = parse_url($url);
        $user     = $dbSource['user'];
        $password = $dbSource['pass'];
        $dsn      = $dbSource['scheme'].':host='.$dbSource['host'].
                    ';dbname='.trim($dbSource['path'], '/').';charset=utf8mb4';

        return [
            'scheme'   => $dbSource['scheme'],
            'dsn'      => $dsn,
            'username' => $user,
            'password' => $password,
        ];
    }
}
