<?php
namespace Peanut\Phalcon;

class Pdo
{
    public $scheme;
    public $dsn;
    public $username;
    public $password;
    public $host;
    public $dbname;
    public $charset;
    public $timezone;
    public $persistent;
    public $options = [];

    public function __construct()
    {
        $this->charset  = 'utf8mb4';
        $this->timezone = date_default_timezone_get();
    }
    /**
     * #[username[:password]@][protocol[(address)]]/dbname[?param1=value1&...&paramN=valueN]
     * @param $url
     * @return array
     */
    public function setDsn($url)
    {
        $dbSource = parse_url($url);

        if (isset($dbSource['query'])) {
            parse_str($dbSource['query'], $query);
            if (isset($query['charset'])) {
                $this->charset = $query['charset'];
            }

            if (isset($query['timezone'])) {
                $this->timezone = $query['timezone'];
            }
            if (isset($query['persistent'])) {
                $this->persistent = $query['persistent'];
            }
        }

        $this->scheme   = $dbSource['scheme'];
        $this->host     = $dbSource['host'];
        $this->dbname   = trim($dbSource['path'], '/');
        $this->username = $dbSource['user'];
        $this->password = $dbSource['pass'];
        $this->dsn      = $this->buildDsn();
    }
    public function setOptions($options = [])
    {
        $this->options += $options;
    }
    public function getConfigurate()
    {
        return [
            'dsn'       => $this->dsn,
            'username'  => $this->username,
            'password'  => $this->password,
            'timezone'  => $this->timezone,
            'persistent'=> $this->persistent,
            'options'   => $this->options,
        ];
    }
    public function connect()
    {
        try {
            $class   = '\\Peanut\\Phalcon\\Pdo\\'.ucfirst($this->scheme);

            return new $class($this->getConfigurate());
        } catch (\Throwable $e) {
            throw $e;
        }
    }
    private function buildDsn()
    {
        return $this->scheme.':host='.$this->host.';dbname='.$this->dbname.';charset='.$this->charset;
    }
}
