<?php
namespace Peanut\Phalcon\Mvc;

use Phalcon\DI\FactoryDefault;

class Dispatcher extends \Phalcon\Mvc\Dispatcher
{
    public $_previousNamespaceName = null;
    public $_previousHandlerName   = null;
    public $_previousActionName    = null;
    public $_previousParams        = null;
    /**
     * Forwards the execution flow to another controller/action.
     *
     * <code>
     * $this->dispatcher->forward(
     *     [
     *         "controller" => "posts",
     *         "action"     => "index",
     *     ]
     * );
     * </code>
     *
     * @param array $forward
     * @param mixed $forward
     *
     * @throws \Phalcon\Exception
     */
    public function forward($forward)
    {
        //var namespaceName, controllerName, params, actionName, taskName;

        if ($this->_isControllerInitialize === true) {
            // Note: Important that we do not throw a "_throwDispatchException" call here. This is important
            // because it would allow the application to break out of the defined logic inside the dispatcher
            // which handles all dispatch exceptions.
            throw new \Phalcon\Exception("Forwarding inside a controller's initialize() method is forbidden");
        }

        // @todo Remove in 4.0.x and ensure forward is of type "array"
        if (false === is_array($forward)) {
            // Note: Important that we do not throw a "_throwDispatchException" call here. This is important
            // because it would allow the application to break out of the defined logic inside the dispatcher
            // which handles all dispatch exceptions.
            throw new \Phalcon\Exception('Forward parameter must be an Array');
        }

        // Save current values as previous to ensure calls to getPrevious methods don't return <tt>null</tt>.
        $this->_previousNamespaceName = $this->_namespaceName;
        $this->_previousHandlerName   = $this->_handlerName;
        $this->_previousActionName    = $this->_actionName;
        $this->_previousParams        = $this->_params;

        // Check if we need to forward to another namespace
        if (true === isset($forward['namespace'])) {
            $this->_namespaceName = $forward['namespace'];
        }

        // Check if we need to forward to another controller.
        if (true === isset($forward['controller'])) {
            $this->_handlerName = $forward['controller'];
        } elseif (true === isset($forward['task'])) {
            $this->_handlerName = $forward['task'];
        }

        // Check if we need to forward to another action
        if (true === isset($forward['action'])) {
            $this->_actionName= $forward['action'];
        }

        // Check if we need to forward changing the current parameters
        if (true === isset($forward['params'])) {
            $this->_params = $forward['params'];
        }

        $this->_finished  = false;
        $this->_forwarded = true;
    }
    public function getPreviousNamespaceName()
    {
        return $this->_previousNamespaceName;
    }
    public function getPreviousControllerName()
    {
        return $this->_previousHandlerName;
    }
    public function getPreviousTaskName()
    {
        return $this->_previousHandlerName;
    }
    public function getPreviousActionName()
    {
        return $this->_previousActionName;
    }
    public function getPreviousParams()
    {
        return $this->_previousParams;
    }
    public function getPath($name)
    {
        return $this->getPaths()[$name] ?? '';
    }
    public function getPaths()
    {
        // pr(get_class_methods($this));

        return $this->getDi()->getShared('router')->getMatchedRoute()->getPaths();
    }
    public function getPrevious()
    {
        return new \Peanut\Phalcon\Mvc\Dispatcher\Previous($this);
    }
}
