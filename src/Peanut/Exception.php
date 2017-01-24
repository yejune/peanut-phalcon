<?php
namespace Peanut;

class Exception extends \Exception
{
    public function __construct($e, $status = '500')
    {
        if (true === is_object($e)) {
            $e = $e->getMessage().' in '.$e->getFile().' on line '.$e->getLine();
        }
        $di = \Phalcon\DI\FactoryDefault::getDefault();
        if (null === $di) {
            $di = new \Phalcon\DI\FactoryDefault;
        }
        $di->setShared('response', function () {
            return new \Peanut\Phalcon\Http\Response();
        });
        $di->getShared('response')->content([
            'status'  => $status,
            'message' => $e,
        ])->send();
        die();
    }
}
