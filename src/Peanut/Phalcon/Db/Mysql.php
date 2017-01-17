<?php
namespace Peanut\Phalcon\Db;

class Mysql extends \Phalcon\Db\Adapter\Pdo\Mysql
{
    /**
     * @param $descriptor
     */
    public function connect(array $descriptor = null)
    {
        if (null === $descriptor) {
            $descriptor = $this->_descriptor;
        }

        if (true === isset($descriptor['username'])) {
            $username = $descriptor['username'];
            unset($descriptor['username']);
        } else {
            $username = null;
        }

        if (true === isset($descriptor['password'])) {
            $password = $descriptor['password'];
            unset($descriptor['password']);
        } else {
            $password = null;
        }

        if (true === isset($descriptor['options'])) {
            $options = $descriptor['options'];
            unset($descriptor['options']);
        } else {
            $options = [];
        }

        if (true === isset($descriptor['persistent'])) {
            if ($descriptor['persistent']) {
                $options[\Pdo::ATTR_PERSISTENT] = true;
            }

            unset($descriptor['persistent']);
        }

        if (true === isset($descriptor['dialectClass'])) {
            unset($descriptor['dialectClass']);
        }

        if (true === isset($descriptor['dsn'])) {
            $dsnAttributes = $descriptor['dsn'];
        } else {
            $dsnParts = [];

            foreach ($descriptor as $key => $value) {
                $dsnParts[] = $key.'='.$value;
            }

            $dsnAttributes = implode(';', $dsnParts);
        }

        $options[\Pdo::ATTR_ERRMODE]            = \Pdo::ERRMODE_EXCEPTION;
        $options[\Pdo::ATTR_EMULATE_PREPARES]   = false;
        $options[\Pdo::ATTR_STRINGIFY_FETCHES]  = false;
        $options[\Pdo::ATTR_DEFAULT_FETCH_MODE] = \Pdo::FETCH_ASSOC;

        $this->_pdo = new \Pdo($dsnAttributes, $username, $password, $options);
    }

    /**
     * @param  $statement
     * @param  array           $bindParameters
     * @param  $mode
     * @throws \PDOException
     * @return array
     */
    public function gets($statement, $bindParameters = [], $mode = \Phalcon\Db::FETCH_ASSOC)
    {
        try {
            return parent::fetchAll($statement, $mode, $bindParameters);
        } catch (\PDOException $e) {
            throw $e;
        }
    }

    /**
     * @param  $statement
     * @param  array           $bindParameters
     * @param  $mode
     * @throws \PDOException
     * @return array
     */
    public function get($statement, $bindParameters = [], $mode = \Phalcon\Db::FETCH_ASSOC)
    {
        try {
            return parent::fetchOne($statement, $mode, $bindParameters);
        } catch (\PDOException $e) {
            throw $e;
        }
    }

    /**
     * @param  $statement
     * @param  array           $bindParameters
     * @param  $mode
     * @throws \PDOException
     * @return string
     */
    public function get1($statement, $bindParameters = [], $mode = \Phalcon\Db::FETCH_ASSOC)
    {
        try {
            $results = parent::fetchOne($statement, $mode, $bindParameters);

            if (true === is_array($results)) {
                foreach ($results as $result) {
                    return $result;
                }
            }

            return $results;
        } catch (\PDOException $e) {
            throw $e;
        }
    }

    /**
     * @param  $statement
     * @param  array           $bindParameters
     * @throws \PdoException
     * @return bool
     */
    public function set($statement, $bindParameters = [])
    {
        try {
            return parent::execute($statement, $bindParameters);
        } catch (\PDOException $e) {
            throw $e;
        }
    }

    /**
     * @param  $statement
     * @param  array        $bindParameters
     * @return int|false
     */
    public function setAndGetSequnce($statement, $bindParameters = [])
    {
        if (true === self::set($statement, $bindParameters)) {
            return parent::lastInsertId();
        }

        return false;
    }

    /**
     * @param  $callback
     * @throws \Exception
     * @return mixed
     */
    public function transaction(callable $callback)
    {
        try {
            parent::begin();
            $return = call_user_func($callback);
            parent::commit();

            return $return;
        } catch (\Throwable $e) {
            parent::rollback();
            throw new \Exception($e->getMessage());
        }
    }
}
