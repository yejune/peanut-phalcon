<?php
namespace Peanut\Phalcon\Pdo;

class Mysql extends \Phalcon\Db\Adapter\Pdo\Mysql
{
    /**
     * @param $descriptor
     * @param mixed $connect
     */
    public function connect(array $connect = null)
    {
        if (true === isset($connect['timezone'])) {
            $connect['options'][\PDO::MYSQL_ATTR_INIT_COMMAND] = "SET time_zone = '".$connect['timezone']."'";
        }
        if (true === isset($connect['persistent'])) {
            $connect['options'][\Pdo::ATTR_PERSISTENT] = $connect['persistent'];
        }
        try {
            $this->_pdo = new \Pdo($connect['dsn'], $connect['username'], $connect['password'], $connect['options']);
        } catch (\Throwable $e) {
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

    public function begin($nesting = NULL) {
        return parent::begin($nesting);
    }

    public function commit($nesting = NULL) {
        if (parent::getTransactionLevel()) {
            return parent::commit($nesting);
        } else {
            throw new TransactionException('There is no active transaction', \Peanut\Constant::TRANSACTION_NOT_FOUND);
        }
    }

    public function rollback($nesting = NULL) {
        if (parent::getTransactionLevel()) {
            $result = false;
            while (parent::getTransactionLevel()) {
                $result = parent::rollback($nesting);
                if(false === $result) {
                    return false;
                }
            }
            return $result;
        } else {
            throw new TransactionException('There is no active transaction', \Peanut\Constant::TRANSACTION_NOT_FOUND);
        }
    }

    /**
     * @param  $callback
     * @throws \Exception
     * @return mixed
     */
    public function transaction(callable $callback)
    {
        try {
            $this->begin();
            $return = call_user_func_array($callback, [$this]);
            if($return) {
                $this->commit('asdf');
            }

            if(false === $return) {
                throw new \Peanut\Exception('Transaction Failure', \Peanut\Constant::TRANSACTION_FAILURE);
            }
            return $return;
        } catch (\Throwable $e) {
            $this->rollback();
            throw new TransactionException($e);
        }
    }
}

class TransactionException extends \Peanut\Exception
{
    public function currentTrace() {
        $trace = $this->getTrace();
        foreach($trace as $row) {
            if(
                true === isset($row['file'])
                && $row['class'] == 'Peanut\Phalcon\Pdo\Mysql'
                && $row['function'] == 'transaction'
            ) {
                return $row;
            }
        }
        return false;
    }
    public function __construct($e, $code = 0)
    {
        parent::__construct($e, $code);
        $current = $this->currentTrace();

        $this->setFile($current['file']);
        $this->setLine($current['line'].' {Closure}');
    }
}
