<?php
namespace Peanut\Phalcon\Mvc;

class Router extends \Phalcon\Mvc\Router
{
    /**
     * Handles routing information received from the rewrite engine
     *
     *<code>
     * // Read the info from the rewrite engine
     * $router->handle();
     *
     * // Manually passing an URL
     * $router->handle("/posts/edit/1");
     *</code>
     * @param null|mixed $uri
     */
    public function handle($uri = null)
    {
        // var realUri, request, currentHostName, routeFound, parts,
        //     params, matches, notFoundPaths,
        //     vnamespace, module,  controller, action, paramsStr, strParams,
        //     route, methods, dependencyInjector,
        //     hostname, regexHostName, matched, pattern, handledUri, beforeMatch,
        //     paths, converters, part, position, matchPosition, converter, eventsManager;

        if (!$uri) {
            /**
             * If 'uri' isn't passed as parameter it reads _GET["_url"]
             */
            $realUri = $this->getRewriteUri();
        } else {
            $realUri = $uri;
        }

        /**
         * Remove extra slashes in the route
         */
        if ($this->_removeExtraSlashes && $realUri != '/') {
            $handledUri = rtrim($realUri, '/');
        } else {
            $handledUri = $realUri;
        }

        $request             = null;
        $currentHostName     = null;
        $routeFound          = false;
        $parts               = [];
        $params              = [];
        $matches             = null;
        $this->_wasMatched   = false;
        $this->_matchedRoute = null;

        $eventsManager = $this->_eventsManager;

        if (is_object($eventsManager)) {
            $eventsManager->fire('router:beforeCheckRoutes', $this);
        }

        /**
         * Routes are traversed in reversed order
         */
        $routes = array_reverse($this->_routes);
        //for route in reverse $this->_routes {
        foreach ($routes as $route) {
            $params  = [];
            $matches = null;

            /**
             * Look for HTTP method constraints
             */
            $methods = $route->getHttpMethods();
            if ($methods !== null) {

                /**
                 * Retrieve the request service from the container
                 */
                if ($request === null) {
                    $dependencyInjector = $this->_dependencyInjector;
                    if ($dependencyInjector instanceof \Phalcon\DiInterface) {
                        throw new Exception("A dependency injection container is required to access the 'request' service");
                    }

                    $request = $dependencyInjector->getShared('request');
                }

                /**
                 * Check if the current method is allowed by the route
                 */
                if ($request->isMethod($methods, true) === false) {
                    continue;
                }
            }

            /**
             * Look for hostname constraints
             */
            $hostname = $route->getHostName();
            if ($hostname !== null) {

                /**
                 * Retrieve the request service from the container
                 */
                if ($request === null) {
                    $dependencyInjector = $this->_dependencyInjector;
                    if ($dependencyInjector instanceof \Phalcon\DiInterface) {
                        throw new Exception("A dependency injection container is required to access the 'request' service");
                    }

                    $request = $dependencyInjector->getShared('request');
                }

                /**
                 * Check if the current hostname is the same as the route
                 */
                if (null === $currentHostName) {
                    $currentHostName = $request->getHttpHost();
                }

                /**
                 * No HTTP_HOST, maybe in CLI mode?
                 */
                if (!$currentHostName) {
                    continue;
                }

                /**
                 * Check if the hostname restriction is the same as the current in the route
                 */
                if (strpos($hostname, '(') !== false) {
                    if (strpos($hostname, '#') === false) {
                        $regexHostName = '#^'.hostname;
                        if (strpos($hostname, ':') === false) {
                            $regexHostName .= '(:[[:digit:]]+)?';
                        }
                        $regexHostName .= '$#i';
                    } else {
                        $regexHostName = $hostname;
                    }
                    $matched = preg_match($regexHostName, $currentHostName);
                } else {
                    $matched = $currentHostName == $hostname;
                }

                if (!$matched) {
                    continue;
                }
            }

            if (true === is_object($eventsManager)) {
                $eventsManager->fire('router:beforeCheckRoute', $this, $route);
            }

            /**
             * If the route has parentheses use preg_match
             */
            $pattern = $route->getCompiledPattern();

            if (strpos($pattern, '^') !== false) {
                $routeFound = preg_match($pattern, $handledUri, $matches);
            } else {
                $routeFound = $pattern == $handledUri;
            }

            /**
             * Check for beforeMatch conditions
             */
            if ($routeFound) {
                if (is_object($eventsManager)) {
                    $eventsManager->fire('router:matchedRoute', $this, $route);
                }

                $beforeMatch = $route->getBeforeMatch();
                if ($beforeMatch !== null) {

                    /**
                     * Check first if the callback is callable
                     */
                    if (!is_callable($beforeMatch)) {
                        throw new Exception('Before-Match callback is not callable in matched route');
                    }

                    /**
                     * Check first if the callback is callable
                     */
                    $routeFound = call_user_func_array($beforeMatch, [$handledUri, $route, $this]);
                }
            } else {
                if (is_object($eventsManager)) {
                    $routeFound = $eventsManager->fire('router:notMatchedRoute', $this, $route);
                }
            }

            if ($routeFound) {

                /**
                 * Start from the default paths
                 */
                $paths = $route->getPaths();
                $parts = $paths;
                /**
                 * Check if the matches has variables
                 */
                if (true === is_array($matches)) {
                    /**
                     * Get the route converters if any
                     */
                    $converters = $route->getConverters();

                    foreach ($paths as $position => $part) {
                        if (false === is_string($part)) {
                            throw new Exception('Wrong key in paths: '.$part);
                        }

                        if (false === is_string($position) && false === is_integer($position)) {
                            continue;
                        }

                        if (true === isset($matches[$position])) {
                            $matchPosition =  $matches[$position];
                            /**
                             * Check if the part has a converter
                             */
                            if (true === is_array($converters)) {
                                if (true === isset($converters[$part])) {
                                    $converter    = $converters[$part];
                                    $parts[$part] = call_user_func_array($converter, [$matchPosition]);
                                    continue;
                                }
                            }

                            /**
                             * Update the parts if there is no converter
                             */
                            $parts[$part] = $matchPosition;
                        } else {

                            /**
                             * Apply the converters anyway
                             */
                            if (true === is_array($converters)) {
                                if (true === isset($converters[$part])) {
                                    $converter    = $converters[$part];
                                    $parts[$part] = call_user_func_array($converter, [$position]);
                                }
                            } else {

                                /**
                                 * Remove the path if the parameter was not matched
                                 */
                                if (true === is_numeric($position)) {
                                    unset($parts[$part]);
                                }
                            }
                        }
                    }

                    /**
                     * Update the matches generated by preg_match
                     */
                    $this->_matches = $matches;
                }

                $this->_matchedRoute = $route;
                break;
            }
        }

        /**
         * Update the wasMatched property indicating if the route was matched
         */
        if ($routeFound) {
            $this->_wasMatched = true;
        } else {
            $this->_wasMatched = false;
        }

        /**
         * The route wasn't found, try to use the not-found paths
         */
        if (!$routeFound) {
            $notFoundPaths = $this->_notFoundPaths;
            if ($notFoundPaths !== null) {
                $parts      = Route::getRoutePaths($notFoundPaths);
                $routeFound = true;
            }
        }

        /**
         * Use default values before we overwrite them if the route is matched
         */
        $this->_namespace  = $this->_defaultNamespace;
        $this->_module     = $this->_defaultModule;
        $this->_controller = $this->_defaultController;
        $this->_action     = $this->_defaultAction;
        $this->_params     = $this->_defaultParams;

        if ($routeFound) {

            /**
             * Check for a namespace
             */
            if (true === isset($parts['namespace'])) {
                $vnamespace = $parts['namespace'];
                if (!is_numeric($vnamespace)) {
                    $this->_namespace = $vnamespace;
                }
                unset($parts['namespace']);
            }

            /**
             * Check for a module
             */
            if (true === isset($parts['module'])) {
                $module = $parts['module'];
                if (!is_numeric($module)) {
                    $this->_module = $module;
                }
                unset($parts['module']);
            }

            /**
             * Check for a controller
             */
            if (true === isset($parts['controller'])) {
                $controller = $parts['controller'];
                if (!is_numeric($controller)) {
                    $this->_controller = $controller;
                }
                unset($parts['controller']);
            }

            /**
             * Check for an action
             */
            if (true === isset($parts['action'])) {
                $action = $parts['action'];
                if (!is_numeric($action)) {
                    $this->_action = $action;
                }
                unset($parts['action']);
            }

            /**
             * Check for parameters
             */
            if (true === isset($parts['params'])) {
                $paramsStr = $parts['params'];
                if (true === is_string($paramsStr)) {
                    $strParams = trim($paramsStr, '/');
                    if ($strParams !== '') {
                        $params = explode('/', $strParams);
                    }
                } else {
                    $params = $parts['params'];
                }

                unset($parts['params']);
            }

            if (count($params)) {
                $this->_params = array_merge($params, $parts);
            } else {
                $this->_params = $parts;
            }
        }

        if (true === is_object($eventsManager)) {
            $eventsManager->fire('router:afterCheckRoutes', $this);
        }
    }
}
