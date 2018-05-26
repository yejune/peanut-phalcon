<?php
namespace Peanut\Phalcon\Mvc;

use Phalcon\Http\ResponseInterface;

class Application extends \Phalcon\Mvc\Application
{
    /**
     * Handles a MVC request
     * @param null|mixed $uri
     */
    public function handle($uri = null) : ?ResponseInterface
    {
        // var $dependencyInjector, $eventsManager, $router, $dispatcher, $response, $view,
        //     $module, $moduleObject, $moduleName, $className, $path,
        //     $implicitView, $returnedResponse, $controller, $possibleResponse,
        //     renderStatus, $matchedRoute, $match;

        $dependencyInjector = $this->_dependencyInjector;
        if (false === is_object($dependencyInjector)) {
            throw new Exception('A dependency injection object is required to access internal services');
        }

        $eventsManager = /*<ManagerInterface> */$this->_eventsManager;

        /**
         * Call boot event, $this allow the developer to perform initialization actions
         */
        if (true === is_object($eventsManager)) {
            if ($eventsManager->fire('application:boot', $this) === false) {
                return false;
            }
        }

        $router = /*<RouterInterface> */$dependencyInjector->getShared('router');

        /**
         * Handle the URI pattern (if any)
         */
        $router->handle($uri);

        /**
         * If a 'match' callback was defined in the $matched route
         * The whole $dispatcher+view behavior can be overridden by the developer
         */
        $matchedRoute = $router->getMatchedRoute();
        if (true === is_object($matchedRoute)) {
            $match = $matchedRoute->getMatch();
            if ($match !== null) {
                if ($match instanceof \Closure) {
                    $match = \Closure::bind($match, $dependencyInjector);
                }

                /**
                 * Directly call the $match callback
                 */
                $possibleResponse = call_user_func_array($match, $router->getParams());

                /**
                 * If the returned value is a string return it as body
                 */
                if (true === is_string($possibleResponse)) {
                    $response = /*<ResponseInterface> */$dependencyInjector->getShared('response');
                    $response->setContent($possibleResponse);

                    return $response;
                }

                /**
                 * If the returned string is a ResponseInterface use it as $response
                 */
                if (true === is_object($possibleResponse)) {
                    if ($possibleResponse instanceof ResponseInterface) {
                        $possibleResponse->sendHeaders();
                        $possibleResponse->sendCookies();

                        return $possibleResponse;
                    }
                }
            }
        }

        /**
         * If the $router doesn't return a valid $module we use the default $module
         */
        $moduleName = $router->getModuleName();
        if (!$moduleName) {
            $moduleName = $this->_defaultModule;
        }

        $moduleObject = null;

        /**
         * Process the $module definition
         */
        if ($moduleName) {
            if (true === is_object($eventsManager)) {
                if ($eventsManager->fire('application:beforeStartModule', $this, $moduleName) === false) {
                    return false;
                }
            }

            /**
             * Gets the $module definition
             */
            $module = $this->getModule($moduleName);

            /**
             * A $module definition must ne an array or an object
             */
            if (false === is_array($module) && false === is_object($module)) {
                throw new Exception("Invalid $module definition");
            }

            /**
             * An array $module definition contains a $path to a $module definition class
             */
            if (true === is_array($module)) {

                /**
                 * Class name used to load the $module definition
                 */
                if (false === isset($module['className'])) {
                    $className = 'Module';
                }

                /**
                 * If developer specify a $path try to include the file
                 */
                if (true === isset($module['path'])) {
                    $path = $module['path'];
                    if (!class_exists($className, false)) {
                        if (!file_exists($path)) {
                            throw new Exception("Module definition $path '".$path."' doesn't exist");
                        }

                        require $path;
                    }
                }

                $moduleObject = /*<ModuleDefinitionInterface> */$dependencyInjector->get($className);

                /**
                 * 'registerAutoloaders' and 'registerServices' are automatically called
                 */
                $moduleObject->registerAutoloaders($dependencyInjector);
                $moduleObject->registerServices($dependencyInjector);
            } else {

                /**
                 * A $module definition object, can be a Closure instance
                 */
                if (!($module instanceof \Closure)) {
                    throw new Exception("Invalid $module definition");
                }

                $moduleObject = call_user_func_array($module, [$dependencyInjector]);
            }

            /**
             * Calling afterStartModule event
             */
            if (true === is_object($eventsManager)) {
                $eventsManager->fire('application:afterStartModule', $this, $moduleObject);
            }
        }

        /**
         * Check whether use implicit $views or not
         */
        $implicitView = $this->_implicitView;

        if ($implicitView === true) {
            $view = /*<ViewInterface> */$dependencyInjector->getShared('view');
        }

        /**
         * We get the parameters from the $router and assign them to the $dispatcher
         * Assign the values passed from the $router
         */
        $dispatcher = /*<DispatcherInterface> */$dependencyInjector->getShared('dispatcher');
        $dispatcher->setModuleName($router->getModuleName());
        $dispatcher->setNamespaceName($router->getNamespaceName());
        $dispatcher->setControllerName($router->getControllerName());
        $dispatcher->setActionName($router->getActionName());
        $dispatcher->setParams($router->getParams());
        $dispatcher->setPaths($matchedRoute->getPaths());/* add */

        /**
         * Start the $view component (start output buffering)
         */
        if ($implicitView === true) {
            $view->start();
        }

        /**
         * Calling beforeHandleRequest
         */
        if (true === is_object($eventsManager)) {
            if ($eventsManager->fire('application:beforeHandleRequest', $this, $dispatcher) === false) {
                return false;
            }
        }

        /**
         * The $dispatcher must return an object
         */
        $controller = $dispatcher->dispatch();

        /**
         * Get the latest value returned by an action
         */
        $possibleResponse = $dispatcher->getReturnedValue();

        /**
         * Returning false from an action cancels the $view
         */
        if (true === is_bool($possibleResponse) && $possibleResponse === false) {
            $response = /*<ResponseInterface> */$dependencyInjector->getShared('response');
        } else {

            /**
             * Returning a string makes use it as the body of the $response
             */
            if (true === is_string($possibleResponse)) {
                $response = /*<ResponseInterface> */$dependencyInjector->getShared('response');
                $response->setContent($possibleResponse);
            } else {

                /**
                 * Check if the returned object is already a $response
                 */
                $returnedResponse = ((true === is_object($possibleResponse)) && ($possibleResponse instanceof ResponseInterface));

                /**
                 * Calling afterHandleRequest
                 */
                if (true === is_object($eventsManager)) {
                    $eventsManager->fire('application:afterHandleRequest', $this, $controller);
                }

                /**
                 * If the $dispatcher returns an object we try to render the $view in auto-rendering mode
                 */
                if ($returnedResponse === false && $implicitView === true) {
                    if (true === is_object($controller)) {
                        $renderStatus = true;

                        /**
                         * $This allows to make a custom $view render
                         */
                        if (true === is_object($eventsManager)) {
                            $renderStatus = $eventsManager->fire('application:viewRender', $this, $view);
                        }

                        /**
                         * Check if the $view process has been treated by the developer
                         */
                        if (renderStatus !== false) {

                            /**
                             * Automatic render based on the latest $controller executed
                             */
                            $view->render(
                                $dispatcher->getControllerName(),
                                $dispatcher->getActionName()
                            );
                        }
                    }
                }

                /**
                 * Finish the $view component (stop output buffering)
                 */
                if ($implicitView === true) {
                    $view->finish();
                }

                if ($returnedResponse === true) {

                    /**
                     * We don't need to create a $response because there is one already created
                     */
                    $response = $possibleResponse;
                } else {
                    $response = /*<ResponseInterface> */$dependencyInjector->getShared('response');
                    if ($implicitView === true) {

                        /**
                         * The content returned by the $view is passed to the $response service
                         */
                        $response->setContent($view->getContent());
                    }
                }
            }
        }

        /**
         * Calling beforeSendResponse
         */
        if (true === is_object($eventsManager)) {
            $eventsManager->fire('application:beforeSendResponse', $this, $response);
        }

        /**
         * Check whether send headers or not (by default yes)
         */
        if ($this->_sendHeaders) {
            $response->sendHeaders();
        }

        /**
         * Check whether send cookies or not (by default yes)
         */
        if ($this->_sendCookies) {
            $response->sendCookies();
        }

        /**
         * Return the $response
         */
        return $response;
    }
}
