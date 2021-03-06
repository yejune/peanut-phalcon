<?php
namespace Peanut\Phalcon\Mvc;

use Phalcon\DI\FactoryDefault;

class Dispatcher extends \Phalcon\Mvc\Dispatcher
{
    public $_previousPaths         = null;
    public $_paths                 = [];
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
        $this->_previousPaths         = $this->_paths;

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
            if (true === is_array($forward['params'])) {
                $this->_params = $forward['params'];
            } else {
                $this->_params = explode('/', $forward['params']);
            }
        }
        $this->_paths     = $forward;/* add */
        $this->_finished  = false;
        $this->_forwarded = true;
    }

    public function getPreviousParams()
    {
        return $this->_previousParams;
    }
    public function getPreviousPaths()
    {
        return $this->_previousPaths;
    }
    public function setPaths($paths)
    {
        $this->_paths = $paths;
    }
    public function getPaths()
    {
        return $this->_paths;
    }
    public function getPath($name)
    {
        return $this->getPaths()[$name] ?? '';
    }
    public function getPrevious()
    {
        return new \Peanut\Phalcon\Mvc\Dispatcher\Previous($this);
    }
    public function getForwardPaths($pattern, $path, $forward = [])
    {
        if (1 === preg_match($pattern, $path, $m)) {
            foreach ($m as $key => $value) {
                if (false === is_numeric($key)) {
                    if ($key == 'namespace') {
                        $forward[$key] = $value;
                    } elseif ($key == 'controller') {
                        $forward[$key] = ucfirst($value);
                    } elseif ($key == 'action') {
                        $forward[$key] = strtolower($_SERVER['REQUEST_METHOD'] ?? 'get').ucfirst($value);
                    } elseif ($key == 'params') {
                        $forward[$key] = $value;
                    } else {
                        $forward[$key] = $value;
                    }
                }
            }
        }

        return $forward;
    }
}
