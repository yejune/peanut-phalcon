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

    /*
    \Peanut\Phalcon\Db::name('master')->sets(
        'insert into test (a,b,c,d) values (:a,:b,:c,:d)', [
            [
                ':a' => 1,
                ':b' => 2,
                ':c' => 1,
                ':d' => 2,
            ],
            [
                ':a' => 1,
                ':b' => 2,
                ':c' => 1,
                ':d' => 2,
            ],
            [
                ':a' => 1,
                ':b' => 2,
                ':c' => 1,
                ':d' => 2,
            ],
        ]
    );
    =>
    insert into test(a,b,c,d) values(:a0, :b0, :c0, :d0),(:a1, :b1, :c1, :d1),(:a2, :b2, :c2, :d2)
    [
      [:a0] => 1
      [:b0] => 2
      [:c0] => 1
      [:d0] => 2
      [:a1] => 1
      [:b1] => 2
      [:c1] => 1
      [:d1] => 2
      [:a2] => 1
      [:b2] => 2
      [:c2] => 1
      [:d2] => 2
    ]
    */
    public function sets($statement, $bindParameters)
    {
        if (
            0 < count($bindParameters)
            && 1 === preg_match('/(?P<control>.*)(?:[\s]+)values(?:[^\(]+)\((?P<holders>.*)\)/Us', $statement, $m)
        ) {
            $holders = explode(',', preg_replace('/\s/', '', $m['holders']));

            $newStatements     = [];
            $newBindParameters = [];
            foreach ($bindParameters as $key => $value) {
                $statements = [];
                foreach ($holders as $holder) {
                    $statements[]                    = $holder.$key;
                    $newBindParameters[$holder.$key] = $value[$holder];
                }
                $newStatements[] = '('.implode(', ', $statements).')';
            }
            $newStatement = $m['control'].' values '.implode(', ', $newStatements);
            try {
                if (parent::execute($newStatement, $newBindParameters)) {
                    return count($bindParameters);
                }
            } catch (\PDOException $e) {
                throw $e;
            }
        }

        return -1;
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
            $return = call_user_func_array($callback, [$this]);
            if (parent::getTransactionLevel()) {
                parent::commit();
            } else {
                throw new \Exception('There is no active transaction');
            }

            return $return;
        } catch (\Throwable $e) {
            if (parent::getTransactionLevel()) {
                parent::rollback();
            }
            throw $e;
        }
    }
}
