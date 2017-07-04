<?php
namespace Peanut\Phalcon\Mvc\Router\Route;

class Php extends \Peanut\Phalcon\Mvc\Router\Route
{
    public function register() : void
    {
        $this->mount($this->getRoute());
    }
    /**
     * @return $this
     */
    private function chainInit() : self
    {
        $this->methods = parent::getMethods();
        $this->pattern = '';

        return $this;
    }

    /**
     * @param  $pattern
     * @return $this
     */
    public function pattern($pattern) : self
    {
        $this->pattern = trim($pattern, '/');

        return $this;
    }

    /**
     * @param  array   $methods
     * @return $this
     */
    public function methods($methods = []) : self
    {
        if (false === is_array($methods)) {
            $methods = func_get_args();
        }

        if (!$methods) {
            $methods = parent::getMethods();
        }

        $this->methods = array_map('strtoupper', $methods);

        return $this;
    }

    /**
     * @param  $callback
     * @throws \Exception
     */
    public function group($callback) : void
    {
        if (func_num_args() === 2) {
            list($prefix, $callback) = func_get_args();
        } else {
            $prefix = [];
        }

        if ($callback instanceof \Closure) {
            array_push($this->groupParts, $prefix);
            $callback = $callback->bindTo($this);
            $callback();
            array_pop($this->groupParts);
        } else {
            $msg = debug_backtrace()[0];
            $msg = 'Closure can\'t be loaded'.PHP_EOL.'in '.$msg['file'].', line '.$msg['line'];
            throw new \Exception($msg);
        }

        //return $this;
    }

    /**
     * @param $handler
     * @param $pattern
     */
    public function get($handler, $pattern = '') : void
    {
        if (func_num_args() === 2) {
            list($pattern, $handler) = func_get_args();
        }

        if (parent::getMethods() !== $this->methods) {
            throw new ChainingException();
        }

        $this->routeHandler['GET'][$this->getUri($pattern)] = $handler;
        $this->chainInit();
    }

    /**
     * @param $handler
     * @param $pattern
     */
    public function post($handler, $pattern = '') : void
    {
        if (func_num_args() === 2) {
            list($pattern, $handler) = func_get_args();
        }

        if (parent::getMethods() !== $this->methods) {
            throw new ChainingException();
        }

        $this->routeHandler['POST'][$this->getUri($pattern)] = $handler;
        $this->chainInit();
    }

    /**
     * @param $handler
     * @param $pattern
     */
    public function put($handler, $pattern = '') : void
    {
        if (func_num_args() === 2) {
            list($pattern, $handler) = func_get_args();
        }

        if (parent::getMethods() !== $this->methods) {
            throw new ChainingException();
        }

        $this->routeHandler['PUT'][$this->getUri($pattern)] = $handler;
        $this->chainInit();
    }

    /**
     * @param $handler
     * @param $pattern
     */
    public function patch($handler, $pattern = '') : void
    {
        if (func_num_args() === 2) {
            list($pattern, $handler) = func_get_args();
        }

        if (parent::getMethods() !== $this->methods) {
            throw new ChainingException();
        }

        $this->routeHandler['PATCH'][$this->getUri($pattern)] = $handler;
        $this->chainInit();
    }

    /**
     * @param $handler
     * @param $pattern
     */
    public function head($handler, $pattern = '') : void
    {
        if (func_num_args() === 2) {
            list($pattern, $handler) = func_get_args();
        }

        if (parent::getMethods() !== $this->methods) {
            throw new ChainingException();
        }

        $this->routeHandler['HEAD'][$this->getUri($pattern)] = $handler;
        $this->chainInit();
    }

    /**
     * @param $handler
     * @param $pattern
     */
    public function options($handler, $pattern = '') : void
    {
        if (func_num_args() === 2) {
            list($pattern, $handler) = func_get_args();
        }

        if (parent::getMethods() !== $this->methods) {
            throw new ChainingException();
        }

        $this->routeHandler['OPTIONS'][$this->getUri($pattern)] = $handler;
        $this->chainInit();
    }

    /**
     * @param $handler
     * @param $pattern
     */
    public function any($handler, $pattern = '') : void
    {
        if (func_num_args() === 2) {
            list($pattern, $handler) = func_get_args();
        }

        foreach ($this->methods as $method) {
            $this->routeHandler[$method][$this->getUri($pattern)] = $handler;
        }

        $this->chainInit();
    }

    /**
     * @param $param
     * @param $handler
     * @param $pattern
     */
    public function param($param, $handler, $pattern = '') : void
    {
        if (func_num_args() === 2) {
            list($pattern, $handler) = func_get_args();
        }

        foreach ($this->methods as $method) {
            $this->paramHandler[$method][$this->getUri($pattern)][$param] = $handler;
        }

        $this->chainInit();
    }

    /**
     * @param $handler
     * @param $pattern
     */
    public function before($handler, $pattern = '') : void
    {
        if (func_num_args() === 2) {
            list($pattern, $handler) = func_get_args();
        }

        foreach ($this->methods as $method) {
            $this->beforeHandler[$method][$this->getUri($pattern)] = $handler;
        }

        $this->chainInit();
    }

    /**
     * @param $handler
     * @param $pattern
     */
    public function after($handler, $pattern = '') : void
    {
        if (func_num_args() === 2) {
            list($pattern, $handler) = func_get_args();
        }

        foreach ($this->methods as $method) {
            $this->afterHandler[$method][$this->getUri($pattern)] = $handler;
        }

        $this->chainInit();
    }
}

class ChainingException extends \Exception
{
    /**
     * @param $message
     * @param $code
     * @param \Exception $previous
     */
    public function __construct($message = '', $code = 0, \Exception $previous = null)
    {
        $last = (debug_backtrace()[1]);

        if ('Peanut\Phalcon\Mvc\Micro' === $last['class'] && true === in_array(strtoupper($last['function']), \Peanut\Phalcon\Mvc\Router::METHODS, true)) {
            $message .= $last['function'].'()은 methods()와 chaining될수 없습니다.'.PHP_EOL.'in '.$last['file'].', line '.$last['line'];
        }

        parent::__construct($message);
    }
}

/*


$routes = new \Peanut\Phalcon\Mvc\Router\Route\Php($app);
$routes->group('huga', function () use ($routes) {
    $routes->before(function () {
        echo 'huga before';
    });
    $routes->get(function () {
        echo 'huga index page';
    });
    $routes->get('{name}', function ($name) {
        echo $name;
    });
    $routes->get('view/{view_id:[0-9]+}', function () {
    });
    $routes->get('write', function () {
    });
    $routes->after(function () {
        echo 'huga after';
    });
});
$routes->register();

$routes->group('board', function() use ($routes)
{
    $routes->before(function()
    {
        echo 'board before';
    });
    $routes->get(function()
    {
        echo 'board index page';
    });
    $routes->group('{board_id:[a-z0-9A-Z]+}', function() use ($routes)
    {
        $routes->param('board_id', function($boardId)
        {
            $this->board = $boardId;
            echo 'board id : ' .$boardId;
        });
        $routes->param('view_id', function($viewId)
        {
            $this->view = $viewId;
            echo 'view id : ' .$viewId;
        });
        $routes->get(function($boardId)
        {
            echo 'board index page <b>'.$boardId.'</b>';
        });
        $routes->get('add', function($board)
        {
            echo 'add '.($this->board === $board ? $board : false);
        });
        $routes->get('view/{view_id:[0-9]+}', function($boardId, $viewId)
        {
            echo '<hr />';
            echo $viewId;
            echo '<hr />';
        });
        $routes->get('write', function()
        {

        });
    });
    $routes->after(function()
    {
        echo 'board after';
    });
});
$routes->get('info', function()
{
    phpinfo();
});
$routes->get(function()
{
    echo '/';
});

return $routes;

*/
