<?php
namespace Peanut\Phalcon\Mvc\Router;

class Route //extends \Phalcon\Di\Injectable
{
    const METHODS = ['POST', 'GET', 'PUT', 'PATCH', 'HEAD', 'DELETE', 'OPTIONS'];
    /**
     * @var array
     */
    protected $methods = self::METHODS;
    /**
     * @var array
     */
    protected $groupParts = [];
    /**
     * @var array
     */
    protected $paramHandler = [];
    /**
     * @var array
     */
    protected $beforeHandler = [];
    /**
     * @var array
     */
    protected $afterHandler = [];
    /**
     * @var array
     */
    protected $routeHandler = [];

    protected $application;

    public function __construct(\Phalcon\Mvc\Micro $app)
    {
        $this->application = $app;
    }
    public function getApplication()
    {
        return $this->application;
    }
    /**
     * @param  $uri
     * @return string
     */
    public function getUri($uri = '')
    {
        $url = '';

        if (true === is_array($this->groupParts) && 0 < count($this->groupParts)) {
            $url .= '/'.implode('/', $this->groupParts);
        }

        if ($uri) {
            $url .= '/'.$uri;
        }

        if (!$url) {
            $url = '/';
        }

        return $url;
    }

    /**
     * @return array
     */
    public function getParam()
    {
        return $this->paramHandler;
    }

    /**
     * @return array
     */
    public function getBefore()
    {
        return $this->beforeHandler;
    }

    /**
     * @return array
     */
    public function getAfter()
    {
        return $this->afterHandler;
    }

    /**
     * @return array
     */
    public function getRoute()
    {
        return $this->routeHandler;
    }

    /**
     * @return array
     * @param mixed $name
     */
    public function getMethods()
    {
        return self::METHODS;
    }

    protected function mount(array $routesGroup = []) : void
    {
        $app = $this->getApplication();
        $collections = [];
        foreach ($routesGroup as $method => $routes) {
            foreach ($routes as $path => $handler) {
                if (true === is_string($handler)) {
                    if (true === is_callable($handler)) {
                        $app->{$method}($path, $handler);
                    } elseif (false !== strpos($handler, '->')) {
                        [$className, $methodName] = explode('->', $handler);
                        if (true === isset($collections[$className])) {
                            $collection = $collections[$className];
                            $collection->{$method}($path, $methodName);
                        } else {
                            $collection = new \Phalcon\Mvc\Micro\Collection;
                            $collection->setHandler($className, true);
                            $collection->setLazy(true);
                            $collection->{$method}($path, $methodName);
                            $collections[$className] = $collection;
                        }
                    } else {
                        $app->{$method}($path, function () use ($handler) {
                            echo $handler;
                        });
                    }
                } elseif ($handler instanceof \Closure) {
                    $app->{$method}($path, $handler);
                }
            }
        }
        foreach ($collections as $className => $collection) {
            $app->mount($collection);
        }
    }
}
