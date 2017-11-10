<?php
namespace Peanut\Phalcon\Mvc;

class Nano extends \Phalcon\Mvc\Micro
{
    public $nonParameters = false;
    /**
     * @var array
     */
    private $instance = [];
    /**
     * @var mixed
     */
    private $pattern;

    /**
     * Handle the whole request
     *
     * @param  string  $uri
     * @return mixed
     */
    public function handle($uri = null)
    {
        $dependencyInjector = $this->_dependencyInjector;

        if (false === is_object($dependencyInjector)) {
            throw new \Peanut\Exception('A dependency injection container is required to access required micro services');
        }

        try {
            $returnedValue = null;
            $router        = $dependencyInjector->getShared('router');

            foreach ($router->getRoute() as $method => $_routes) {
                foreach ($_routes as $url => $handler) {
                    parent::{$method}($url, $handler);
                }
            }

            $router->handle($uri);
            $matchedRoute = $router->getMatchedRoute();

            if (true === is_object($matchedRoute)) {
                $handler = $this->_handlers[$matchedRoute->getRouteId()];

                if (!$handler) {
                    throw new \Peanut\Exception("Matched route doesn't have an associated handler");
                }

                $this->_activeHandler = $handler;
                $params               = [];

                foreach ($matchedRoute->getPaths() as $name => $key) {
                    $params[$name] = $router->getMatches()[$key] ?? null;
                }

                $method = $this->request->getMethod();
                $parts  = $this->getPatternParts($matchedRoute);

                $routeParam = $router->getParam();

                foreach ($parts as $part) {
                    if (($_method = true === isset($routeParam['MAP'][$part]) ? 'MAP' : '')
                        || ($_method = true === isset($routeParam[$method][$part]) ? $method : '')
                    ) {
                        $check = $routeParam[$_method][$part];

                        foreach ($check as $k => $_handler) {
                            if (true === isset($params[$k])) {
                                $status = $this->callHandler($_handler, [$params[$k]], 'param');

                                if (false === $status) {
                                    return false;
                                }
                            }
                        }
                    }
                }

                $routeBefore = $router->getBefore();
                foreach ($parts as $part) {
                    if (($_method = true === isset($routeBefore['MAP'][$part]) ? 'MAP' : '')
                        || ($_method = true === isset($routeBefore[$method][$part]) ? $method : '')
                    ) {
                        $_handler = $routeBefore[$_method][$part];
                        $status   = $this->callHandler($_handler, $params, 'before');

                        if (false === $status) {
                            return false;
                        }
                    }
                }

                $returnedValue = $this->callHandler($handler, $params);

                $routeAfter = $router->getAfter();

                foreach ($parts as $part) {
                    if (($_method = true === isset($routeAfter['MAP'][$part]) ? 'MAP' : '')
                        || ($_method = true === isset($routeAfter[$method][$part]) ? $method : '')
                    ) {
                        $_handler = $routeAfter[$_method][$part];
                        $status   = $this->callHandler($_handler, $params, 'after');

                        if (false === $status) {
                            return false;
                        }
                    }
                }
            } else {
                $returnedValue = $this->callHandler($this->_notFoundHandler, [], 'notFound');
            }

            $this->_returnedValue = $returnedValue;
        } catch (\Throwable $e) {
            if ($this->_errorHandler) {
                $returnedValue = $this->callHandler($this->_errorHandler, [$e, $e->getCode()], 'error');

                if (true === is_object($returnedValue)
                    && !($returnedValue instanceof \Phalcon\Http\ResponseInterface)
                ) {
                    throw $e;
                }
            } elseif (false !== $returnedValue) {
                throw $e;
            }
        }

        if (true === is_object($returnedValue)
            && $returnedValue instanceof \Phalcon\Http\ResponseInterface
        ) {
            $returnedValue->send();
        } elseif (true === is_string($returnedValue)) {
            echo $returnedValue;
        }

        return $returnedValue;
    }
    public function getHandler($id)
    {
        return $this->_handlers[$id];
    }

    /**
     * @param  $className
     * @return mixed
     */
    private function classLoader($className)
    {
        if (false === isset($this->instance[$className])) {
            $this->instance[$className] = new $className();
        }

        return $this->instance[$className];
    }

    /**
     * @param  $handler
     * @param  array      $args
     * @param  $name
     * @return mixed
     */
    private function callHandler($handler, $args = [], $name = '')
    {
        if (true === is_callable($handler)) {
            if ($this->nonParameters) {
                $status = call_user_func($handler, $this->request, $this->response);
            } else {
                $status = call_user_func_array($handler, $args);
            }
        } elseif (true === is_string($handler)) {
            if (false !== strpos($handler, '->')) {
                $tmp = explode('->', $handler);
                try {
                    $class = $this->classLoader($tmp[0]);
                } catch (\Throwable $e) {
                    throw $e;
                    //throw new \Peanut\Exception(($name ? $name.' ' : '').'\''.$handler.'\' handler is not callable: '.$e->getMessage().' in '.$e->getFile().' line '.$e->getLine());
                }
                if (true === is_callable([$class, $tmp[1]])) {
                    if ($this->nonParameters) {
                        $status = call_user_func([$class, $tmp[1]], $this->request, $this->response);
                    } else {
                        $status = call_user_func_array([$class, $tmp[1]], $args);
                    }
                } else {
                    throw new \Peanut\Exception(($name ? $name.' ' : '').'\''.$handler.'\' handler is not callable');
                }
            } else {
                echo $handler;
                $status = $this->response;
            }
        } else {
            throw new \Peanut\Exception(($name ? $name.' ' : '').str_replace([PHP_EOL, ' '], ['', ' '], print_r($handler, true)).' is not support');
        }

        return $status;
    }

    /**
     * @param  $matchedRoute
     * @return mixed
     */
    private function getPatternParts($matchedRoute)
    {
        $pattern = $matchedRoute->getPattern();

        if ('/' == $pattern) {
            return [$pattern];
        }
        $url          = '';
        $patternParts = [];

        if (false === strpos($pattern, '{')) {
            $patterns = explode('/', $pattern);
        } else {
            $patterns = preg_split('#(?<!\^|\\\)/#', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE);
        }

        foreach ($patterns as $uri) {
            $url .= '/'.$uri;
            $patternParts[] = '/'.trim($url, '/');
        }

        return $patternParts;
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

        if ('Peanut\Phalcon\Mvc\Micro' === $last['class'] && true === in_array(strtoupper($last['function']), \Peanut\Phalcon\Mvc\Router::methods, true)) {
            $message .= $last['function'].'()은 methods()와 chaining될수 없습니다.'.PHP_EOL.'in '.$last['file'].', line '.$last['line'];
        }

        parent::__construct($message);
    }
}
