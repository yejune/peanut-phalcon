<?php
namespace Peanut\Phalcon\Mvc\Dispatcher;

class Previous
{
    public $namespaceName = null;
    public $handlerName   = null;
    public $actionName    = null;
    public $params        = [];
    public function __construct($previous)
    {
        $this->namespaceName = $previous->getPreviousNamespaceName();
        $this->handlerName   = $previous->getPreviousControllerName();
        $this->actionName    = $previous->getPreviousActionName();
        $this->params        = $previous->getPreviousParams();
    }
    public function getNamespaceName()
    {
        return $this->namespaceName;
    }
    public function getControllerName()
    {
        return $this->handlerName;
    }
    public function getTaskName()
    {
        return $this->handlerName;
    }
    public function getActionName()
    {
        return $this->actionName;
    }
    public function getParams()
    {
        return $this->params;
    }
}
