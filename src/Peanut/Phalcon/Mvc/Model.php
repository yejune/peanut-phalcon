<?php
namespace Peanut\Phalcon\Mvc;

use Phalcon\DI\FactoryDefault;

class Model extends \Phalcon\Mvc\Model
{
    public function setRelated($alias, $properties) : void
    {
        $this->_related[$alias][] = $properties;
    }
    public static function getModelManager()
    {
        $di = FactoryDefault::getDefault();

        return $di->get('modelsManager');
    }
    public static function getConnection($dbName)
    {
        $di = FactoryDefault::getDefault();

        return $di->get($dbName);
    }
    public function xafterSave()
    {
        $messages = $this->getMessages();

        foreach ($messages as $message) {
            echo $message, "\n";
        }
    }
    /*
    public function _save($data = null, $whiteList = null) : bool
    {
        $result = parent::save($data, $whiteList);
        $trace  = debug_backtrace()[1];

        if (false === $result || parent::getMessages()) {
            foreach (parent::getMessages() as $message) {
                $ex = new \Peanut\Exception($message->getMessage(), 500);
                if (true === isset($trace['file'])) {
                    $ex->setFile($trace['file']);
                    $ex->setLine($trace['line']);
                }
                $ex->setCode(500);
                throw $ex;
            }
        }

        return $result;
    }
    public function save($data = null, $whiteList = null) : bool
    {
        return $this->_save($data, $whiteList);
    }
    public function create($data = null, $whiteList = null) : bool
    {
        return $this->_save($data, $whiteList);
    }
    public function update($data = null, $whiteList = null) : bool
    {
        return $this->_save($data, $whiteList);
    }
    */
}
