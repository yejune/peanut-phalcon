<?php
namespace Phalcon\Mvc;

use Phalcon\DiInterface;
use Phalcon\Di\Injectable;
use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\Micro\Exception;
use Phalcon\Mvc\Micro\LazyLoader;
use Phalcon\Di\ServiceInterface;
use Phalcon\Http\ResponseInterface;
use Phalcon\Mvc\Router\RouteInterface;
use Phalcon\Mvc\Nano\MiddlewareInterface;

//use Phalcon\Mvc\Micro\Collection;
//use Phalcon\Mvc\Micro\CollectionInterface ;

/**
 * Phalcon\Mvc\Nano
 *
 * With Phalcon you can create "Nano-Framework like" applications. By doing this, you only need to
 * write a minimal amount of code to create a PHP application. Nano applications are suitable
 * to small applications, APIs and prototypes in a practical way.
 *
 *<code>
 *
 * $app = new \Phalcon\Mvc\Nano();
 *
 * $app->get('/say/welcome/{name}', function ($name) {
 *    echo "<h1>Welcome $name!</h1>";
 * });
 *
 * $app->handle();
 *
 *</code>
 */
class Nano extends Injectable implements \ArrayAccess
{
    protected $_dependencyInjector;

    protected $_handlers;

    protected $_router;

    protected $_stopped;

    protected $_notFoundHandler;

    protected $_errorHandler;

    protected $_activeHandler;

    protected $_beforeHandlers;

    protected $_afterHandlers;

    protected $_finishHandlers;

    protected $_returnedValue;

    /**
     * Phalcon\Mvc\Nano constructor
     */
    public function __construct(DiInterface $dependencyInjector = null)
    {
        if (true === is_object($dependencyInjector)) {
            if ($dependencyInjector instanceof DiInterface) {
                $this->setDi($dependencyInjector);
            }
        }
    }

    /**
     * Sets the DependencyInjector container
     */
    public function setDI(DiInterface $dependencyInjector)
    {
        /**
         * We automatically set ourselves as application service
         */
        if (!$dependencyInjector->has('application')) {
            $dependencyInjector->set('application', $this);
        }

        $this->_dependencyInjector = $dependencyInjector;
    }

    /**
     * Maps a route to a handler without any HTTP method constraint
     *
     * @param string routePattern
     * @param callable handler
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function map(string $routePattern, $handler) : RouteInterface
    {
        ////var $router, $route;

        /**
         * We create a router even if there is no one in the DI
         */
        $router = $this->getRouter();

        /**
         * Routes are added to the router
         */
        $route = $router->add($routePattern);

        /**
         * Using the id produced by the router we store the handler
         */
        $this->_handlers[$route->getRouteId()] = $handler;

        /**
         * The route is returned, the developer can add more things on it
         */
        return $route;
    }

    /**
     * Maps a route to a handler that only matches if the HTTP method is GET
     *
     * @param string routePattern
     * @param callable handler
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function get(string $routePattern, $handler) : RouteInterface
    {
        ////var router, route;

        /**
         * We create a router even if there is no one in the DI
         */
        $router = $this->getRouter();

        /**
         * Routes are added to the router restricting to GET
         */
        $route = $router->addGet($routePattern);

        /**
         * Using the id produced by the router we store the handler
         */
        $this->_handlers[$route->getRouteId()] = $handler;

        /**
         * The route is returned, the developer can add more things on it
         */
        return $route;
    }

    /**
     * Maps a route to a handler that only matches if the HTTP method is POST
     *
     * @param string routePattern
     * @param callable handler
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function post(string $routePattern, $handler) : RouteInterface
    {
        ////var router, route;

        /**
         * We create a router even if there is no one in the DI
         */
        $router = $this->getRouter();

        /**
         * Routes are added to the router restricting to POST
         */
        $route = $router->addPost($routePattern);

        /**
         * Using the id produced by the router we store the handler
         */
        $this->_handlers[$route->getRouteId()] = $handler;

        /**
         * The route is returned, the developer can add more things on it
         */
        return $route;
    }

    /**
     * Maps a route to a handler that only matches if the HTTP method is PUT
     *
     * @param string $routePattern
     * @param callable $handler
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function put(string $routePattern, $handler) : RouteInterface
    {
        //var router, route;

        /**
         * We create a router even if there is no one in the DI
         */
        $router = $this->getRouter();

        /**
         * Routes are added to the router restricting to PUT
         */
        $route = $router->addPut($routePattern);

        /**
         * Using the id produced by the router we store the handler
         */
        $this->_handlers[$route->getRouteId()] = $handler;

        /**
         * The route is returned, the developer can add more things on it
         */
        return $route;
    }

    /**
     * Maps a route to a handler that only matches if the HTTP method is PATCH
     *
     * @param string $routePattern
     * @param callable $handler
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function patch(string $routePattern, $handler) : RouteInterface
    {
        //var router, route;

        /**
         * We create a router even if there is no one in the DI
         */
        $router = $this->getRouter();

        /**
         * Routes are added to the router restricting to PATCH
         */
        $route = $router->addPatch($routePattern);

        /**
         * Using the id produced by the router we store the handler
         */
        $this->_handlers[$route->getRouteId()] = $handler;

        /**
         * The route is returned, the developer can add more things on it
         */
        return $route;
    }

    /**
     * Maps a route to a handler that only matches if the HTTP method is HEAD
     *
     * @param string routePattern
     * @param callable handler
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function head(string $routePattern, $handler) : RouteInterface
    {
        //var router, route;

        /**
         * We create a router even if there is no one in the DI
         */
        $router = $this->getRouter();

        /**
         * Routes are added to the router restricting to HEAD
         */
        $route = $router->addHead($routePattern);

        /**
         * Using the id produced by the router we store the handler
         */
        $this->_handlers[$route->getRouteId()] = $handler;

        /**
         * The route is returned, the developer can add more things on it
         */
        return $route;
    }

    /**
     * Maps a route to a handler that only matches if the HTTP method is DELETE
     *
     * @param string routePattern
     * @param callable handler
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function delete(string $routePattern, $handler) : RouteInterface
    {
        //var router, route;

        /**
         * We create a router even if there is no one in the DI
         */
        $router = $this->getRouter();

        /**
         * Routes are added to the router restricting to DELETE
         */
        $route = $router->addDelete($routePattern);

        /**
         * Using the id produced by the router we store the handler
         */
        $this->_handlers[$route->getRouteId()] = $handler;

        /**
         * The route is returned, the developer can add more things on it
         */
        return $route;
    }

    /**
     * Maps a route to a handler that only matches if the HTTP method is OPTIONS
     *
     * @param string routePattern
     * @param callable handler
     * @return \Phalcon\Mvc\Router\RouteInterface
     */
    public function options(string $routePattern, $handler) : RouteInterface
    {
        //var router, route;

        /**
         * We create a router even if there is no one in the DI
         */
        $router = $this->getRouter();

        /**
         * Routes are added to the router restricting to OPTIONS
         */
        $route = $router->addOptions($routePattern);

        /**
         * Using the id produced by the router we store the handler
         */
        $this->_handlers[$route->getRouteId()] = $handler;

        /**
         * The route is returned, the developer can add more things on it
         */
        return $route;
    }

    /**
     * Mounts a collection of handlers
     */
    public function mount(CollectionInterface $collection) : Nano
    {
        //var mainHandler, handlers, lazyHandler, prefix, methods, pattern, subHandler, realHandler, prefixedPattern, route, handler, name;

        /**
         * Get the main handler
         */
        $mainHandler = $collection->getHandler();
        if (true === empty($mainHandler)) {
            throw new Exception('Collection requires a main handler');
        }

        $handlers = $collection->getHandlers();
        if (!count($handlers)) {
            throw new Exception('There are no handlers to mount');
        }

        if (true === is_array($handlers)) {

            /**
             * Check if handler is lazy
             */
            if ($collection->isLazy()) {
                $lazyHandler = new LazyLoader($mainHandler);
            } else {
                $lazyHandler = $mainHandler;
            }

            /**
             * Get the main prefix for the collection
             */
            $prefix = $collection->getPrefix();

            foreach ($handlers as $handler) {
                if (false === is_array($handler)) {
                    throw new Exception('One of the registered handlers is invalid');
                }

                $methods    = $handler[0];
                $pattern    = $handler[1];
                $subHandler = $handler[2];
                $name       = $handler[3];

                /**
                 * Create a real handler
                 */
                $realHandler = [$lazyHandler, $subHandler];

                if (false === empty($prefix)) {
                    if ($pattern == '/') {
                        $prefixedPattern = $prefix;
                    } else {
                        $prefixedPattern = $prefix.$pattern;
                    }
                } else {
                    $prefixedPattern = $pattern;
                }

                /**
                 * Map the route manually
                 */
                $route = $this->map($prefixedPattern, $realHandler);

                if ((true === is_string($methods) && $methods != '') || true === is_array($methods)) {
                    $route->via($methods);
                }

                if (true === is_string($name)) {
                    $route->setName($name);
                }
            }
        }

        return $this;
    }

    /**
     * Sets a handler that will be called when the router doesn't match any of the defined routes
     *
     * @param callable handler
     * @return \Phalcon\Mvc\Nano
     */
    public function notFound($handler) : Nano
    {
        $this->_notFoundHandler = $handler;

        return $this;
    }

    /**
     * Sets a handler that will be called when an exception is thrown handling the route
     *
     * @param callable handler
     * @return \Phalcon\Mvc\Nano
     */
    public function error($handler) : Nano
    {
        $this->_errorHandler = $handler;

        return $this;
    }

    /**
     * Returns the internal router used by the application
     */
    public function getRouter() : RouterInterface
    {
        //var router;

        $router = $this->_router;
        if (false === is_object($router)) {
            $router = $this->getSharedService('router');

            /**
             * Clear the set routes if any
             */
            $router->clear();

            /**
             * Automatically remove extra slashes
             */
            $router->removeExtraSlashes(true);

            /**
             * Update the internal router
             */
            $this->_router = $router;
        }

        return $router;
    }

    /**
     * Sets a service from the DI
     *
     * @param string  serviceName
     * @param mixed   definition
     * @param boolean shared
     * @return \Phalcon\Di\ServiceInterface
     */
    public function setService(string $serviceName, $definition, boolean $shared) : ServiceInterface
    {
        //var dependencyInjector;

        $dependencyInjector = $this->_dependencyInjector;
        if (false === is_object($dependencyInjector)) {
            $dependencyInjector        = new FactoryDefault();
            $this->_dependencyInjector = $dependencyInjector;
        }

        return $dependencyInjector->set($serviceName, $definition, $shared);
    }

    /**
     * Checks if a service is registered in the DI
     */
    public function hasService(string $serviceName) : boolean
    {
        //var dependencyInjector;

        $dependencyInjector = $this->_dependencyInjector;
        if (false === is_object($dependencyInjector)) {
            $dependencyInjector        = new FactoryDefault();
            $this->_dependencyInjector = $dependencyInjector;
        }

        return $dependencyInjector->has($serviceName);
    }

    /**
     * Obtains a service from the DI
     *
     * @param string serviceName
     * @return object
     */
    public function getService(string $serviceName)
    {
        //var dependencyInjector;

        $dependencyInjector = $this->_dependencyInjector;
        if (false === is_object($dependencyInjector)) {
            $dependencyInjector        = new FactoryDefault();
            $this->_dependencyInjector = $dependencyInjector;
        }

        return $dependencyInjector->get($serviceName);
    }

    /**
     * Obtains a shared service from the DI
     *
     * @param string serviceName
     * @return mixed
     */
    public function getSharedService($serviceName)
    {
        //var dependencyInjector;

        $dependencyInjector = $this->_dependencyInjector;
        if (false === is_object($dependencyInjector)) {
            $dependencyInjector        = new FactoryDefault();
            $this->_dependencyInjector = $dependencyInjector;
        }

        return $dependencyInjector->getShared($serviceName);
    }

    /**
     * Handle the whole request
     *
     * @param string uri
     * @return mixed
     */
    public function handle($uri = null)
    {
        //var dependencyInjector, eventsManager, status = null, router, matchedRoute, handler, beforeHandlers, params, returnedValue, e, errorHandler, afterHandlers, notFoundHandler, finishHandlers, finish, before, after, response;

        $dependencyInjector = $this->_dependencyInjector;
        if (false === is_object($dependencyInjector)) {
            throw new Exception('A dependency injection container is required to access required micro services');
        }

        try {
            $returnedValue = null;

            /**
             * Calling beforeHandle routing
             */
            $eventsManager = $this->_eventsManager;
            if (true === is_object($eventsManager)) {
                if (false === $eventsManager->fire('micro:beforeHandleRoute', $this)) {
                    return false;
                }
            }

            /**
             * Handling routing information
             */
            $router = $dependencyInjector->getShared('router');

            /**
             * Handle the URI as normal
             */
            $router->handle($uri);

            /**
             * Check if one route was matched
             */
            $matchedRoute = $router->getMatchedRoute();
            if (true === is_object($matchedRoute)) {
                $routeId = $matchedRoute->getRouteId();
                if (true === isset($this->_handlers[$routeId])) {
                    throw new Exception("Matched route doesn't have an associated handler");
                }

                //Updating active handler
                $handler              = $this->_handlers[$routeId];
                $this->_activeHandler = $handler;

                /**
                 * Calling beforeExecuteRoute event
                 */
                if (true === is_object($eventsManager)) {
                    if (false === $eventsManager->fire('micro:beforeExecuteRoute', $this)) {
                        return false;
                    } else {
                        $handler = $this->_activeHandler;
                    }
                }

                $beforeHandlers = $this->_beforeHandlers;
                if (true === is_array($beforeHandlers)) {
                    $this->_stopped = false;

                    /**
                     * Calls the before handlers
                     */
                    foreach ($beforeHandlers as $before) {
                        if (true === is_object($before)) {
                            if ($before instanceof MiddlewareInterface) {

                                /**
                                 * Call the middleware
                                 */
                                $status = $before->call($this);

                                /**
                                 * Reload the status
                                 * break the execution if the middleware was stopped
                                 */
                                if ($this->_stopped) {
                                    break;
                                }

                                continue;
                            }
                        }

                        if (false === is_callable($before)) {
                            throw new Exception("'before' handler is not callable");
                        }

                        /**
                         * Call the before handler, if it returns false exit
                         */
                        if (false === call_user_func($before)) {
                            return false;
                        }

                        /**
                         * Reload the 'stopped' status
                         */
                        if ($this->_stopped) {
                            return $status;
                        }
                    }
                }

                $params = $router->getParams();

                /**
                 * Bound the app to the handler
                 */
                if (true === is_object($handler) && $handler instanceof \Closure) {
                    $handler = \Closure::bind($handler, $this);
                }

                /**
                 * Calling the Handler in the PHP userland
                 */
                $returnedValue = call_user_func_array($handler, $params);

                /**
                 * Update the returned value
                 */
                $this->_returnedValue = $returnedValue;

                /**
                 * Calling afterExecuteRoute event
                 */
                if (true === is_object($eventsManager)) {
                    $eventsManager->fire('micro:afterExecuteRoute', $this);
                }

                $afterHandlers = $this->_afterHandlers;
                if (true === is_array($afterHandlers)) {
                    $this->_stopped = false;

                    /**
                     * Calls the after handlers
                     */
                    foreach ($afterHandlers as $after) {
                        if (true === is_object($after)) {
                            if ($after instanceof MiddlewareInterface) {
                                /**
                                 * Call the middleware
                                 */
                                $status = $after->call($this);

                                /**
                                 * break the execution if the middleware was stopped
                                 */
                                if ($this->_stopped) {
                                    break;
                                }

                                continue;
                            }
                        }

                        if (false === is_callable($after)) {
                            throw new Exception("One of the 'after' handlers is not callable");
                        }

                        $status = call_user_func($after);
                    }
                }
            } else {

                /**
                 * Calling beforeNotFound event
                 */
                $eventsManager = $this->_eventsManager;
                if (true === is_object($eventsManager)) {
                    if (false === $eventsManager->fire('micro:beforeNotFound', $this)) {
                        return false;
                    }
                }

                /**
                 * Check if a notfoundhandler is defined and it's callable
                 */
                $notFoundHandler = $this->_notFoundHandler;
                if (false === is_callable($notFoundHandler)) {
                    throw new Exception('Not-Found handler is not callable or is not defined');
                }

                /**
                 * Call the Not-Found handler
                 */
                $returnedValue = call_user_func($notFoundHandler);
            }

            /**
             * Calling afterHandleRoute event
             */
            if (true === is_object($eventsManager)) {
                $eventsManager->fire('micro:afterHandleRoute', $this, $returnedValue);
            }

            $finishHandlers = $this->_finishHandlers;
            if (true === is_array($finishHandlers)) {
                $this->_stopped = false;

                $params = null;

                /**
                 * Calls the finish handlers
                 */
                foreach ($finishHandlers as $finish) {

                    /**
                     * Try to execute middleware as plugins
                     */
                    if (true === is_object($finish)) {
                        if ($finish instanceof MiddlewareInterface) {

                            /**
                             * Call the middleware
                             */
                            $status = $finish->call($this);

                            /**
                             * break the execution if the middleware was stopped
                             */
                            if ($this->_stopped) {
                                break;
                            }

                            continue;
                        }
                    }

                    if (false === is_callable($finish)) {
                        throw new Exception("One of the 'finish' handlers is not callable");
                    }

                    if (null === $params) {
                        $params = [$this];
                    }

                    /**
                     * Call the 'finish' middleware
                     */
                    $status = call_user_func_array($finish, $params);

                    /**
                     * break the execution if the middleware was stopped
                     */
                    if ($this->_stopped) {
                        break;
                    }
                }
            }
        } catch (\Exception $e) {

            /**
             * Calling beforeNotFound event
             */
            $eventsManager = $this->_eventsManager;
            if (true === is_object($eventsManager)) {
                $returnedValue = $eventsManager->fire('micro:beforeException', $this, $e);
            }

            /**
             * Check if an errorhandler is defined and it's callable
             */
            $errorHandler = $this->_errorHandler;
            if ($errorHandler) {
                if (false === is_callable($errorHandler)) {
                    throw new Exception('Error handler is not callable');
                }

                /**
                 * Call the Error handler
                 */
                $returnedValue = call_user_func_array($errorHandler, [$e]);
                if (true === is_object($returnedValue)) {
                    if (!($returnedValue instanceof ResponseInterface)) {
                        throw $e;
                    }
                } else {
                    if (false !== $returnedValue) {
                        throw $e;
                    }
                }
            } else {
                if (false !== $returnedValue) {
                    throw $e;
                }
            }
        }

        /**
         * Check if the returned value is a string and take it as response body
         */
        if (true === is_string($returnedValue)) {
            $response = $dependencyInjector->getShared('response');
            $response->setContent($returnedValue);
            $response->send();
        }

        /**
         * Check if the returned object is already a response
         */
        if (true === is_object($returnedValue)) {
            if ($returnedValue instanceof ResponseInterface) {
                /**
                 * Automatically send the response
                 */
                $returnedValue->send();
            }
        }

        return $returnedValue;
    }

    /**
     * Stops the middleware execution avoiding than other middlewares be executed
     */
    public function stop()
    {
        $this->_stopped = true;
    }

    /**
     * Sets externally the handler that must be called by the matched route
     *
     * @param callable activeHandler
     */
    public function setActiveHandler($activeHandler)
    {
        $this->_activeHandler = $activeHandler;
    }

    /**
     * Return the handler that will be called for the matched route
     *
     * @return callable
     */
    public function getActiveHandler()
    {
        return $this->_activeHandler;
    }

    /**
     * Returns the value returned by the executed handler
     *
     * @return mixed
     */
    public function getReturnedValue()
    {
        return $this->_returnedValue;
    }

    /**
     * Check if a service is registered in the internal services container using the array syntax
     *
     * @param string alias
     * @return boolean
     */
    public function offsetExists($alias) : boolean
    {
        return $this->hasService($alias);
    }

    /**
     * Allows to register a shared service in the internal services container using the array syntax
     *
     *<code>
     *	$app['request'] = new \Phalcon\Http\Request();
     *</code>
     *
     * @param string alias
     * @param mixed definition
     */
    public function offsetSet($alias, $definition)
    {
        $this->setService($alias, $definition);
    }

    /**
     * Allows to obtain a shared service in the internal services container using the array syntax
     *
     *<code>
     *	var_dump($di['request']);
     *</code>
     *
     * @param string alias
     * @return mixed
     */
    public function offsetGet($alias)
    {
        return $this->getService($alias);
    }

    /**
     * Removes a service from the internal services container using the array syntax
     *
     * @param string alias
     */
    public function offsetUnset($alias)
    {
        return $alias;
    }

    /**
     * Appends a before middleware to be called before execute the route
     *
     * @param callable handler
     * @return \Phalcon\Mvc\Nano
     */
    public function before($handler) : Nano
    {
        $this->_beforeHandlers[] = $handler;

        return $this;
    }

    /**
     * Appends an 'after' middleware to be called after execute the route
     *
     * @param callable handler
     * @return \Phalcon\Mvc\Nano
     */
    public function after($handler) : Nano
    {
        $this->_afterHandlers[] = $handler;

        return $this;
    }

    /**
     * Appends a 'finish' middleware to be called when the request is finished
     *
     * @param callable handler
     * @return \Phalcon\Mvc\Nano
     */
    public function finish($handler) : Nano
    {
        $this->_finishHandlers[] = $handler;

        return $this;
    }

    /**
     * Returns the internal handlers attached to the application
     *
     * @return array
     */
    public function getHandlers()
    {
        return $this->_handlers;
    }
}
