<?php
namespace Peanut\Phalcon\Cli;

class Dispatcher extends \Phalcon\Cli\Dispatcher
{
    public function callActionMethod($handler, $actionMethod, array $params = [])
    {
        return call_user_func_array([$handler, $actionMethod], $params);
    }
}
