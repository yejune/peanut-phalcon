<?php
namespace Peanut\Bootstrap;

class Yaml
{
    /**
     * @var \Phalcon\DI\FactoryDefault
     */
    public $di;

    /**
     * @var string
     */
    public $stageName = 'local';
    /**
     * @var bool
     */
    public $debug = false;

    /**
     * @param \Phalcon\DI\FactoryDefault $di
     */
    public function __construct(\Phalcon\DI\FactoryDefault $di)
    {
        $this->setDi($di);
    }

    /**
     * @param \Phalcon\DI\FactoryDefault $di
     */
    public function setDi(\Phalcon\DI\FactoryDefault $di)
    {
        $this->di = $di;
    }

    /**
     * @param  null|string
     * @return \Phalcon\DI\FactoryDefault
     */
    public function getDI($name = null)
    {
        if ($name) {
            return $this->di->get($name);
        }

        return $this->di;
    }

    /**
     * @return string
     */
    public function getHttpHost()
    {
        return $this->getDi()->get('request')->getHttpHost();
    }

    public function initRequest()
    {
        $this->di->setShared('request', function () {
            return new \Peanut\Phalcon\Http\Request();
        });
    }

    public function initResponse()
    {
        $this->di->setShared('response', function () {
            return new \Peanut\Phalcon\Http\Response();
        });
    }

    /**
     * @param array $config
     */
    public function initConfig(array $config)
    {
        $this->di->setShared('config', function () use ($config) {
            return $config; //(new \Phalcon\Config($config))->toArray();
        });
    }

    /**
     * @param  $configFile
     * @return array
     */
    public function getConfigFile($configFile)
    {
        try {
            $config = yaml_parse_file($configFile);

            if (false === is_array($config)) {
                throw new \Exception($configFile.' can\'t be loaded.');
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return $config;
    }

    /**
     * @param  \Phalcon\Mvc\Micro   $app
     * @return \Phalcon\Mvc\Micro
     */
    public function __invoke(\Phalcon\Mvc\Micro $app)
    {
        $config = $this->getConfigFile(__BASE__.'/Bootfile.yml');

        return $this->run($app, $config);
    }

    /**
     * @param $config
     */
    protected function initialize(\Phalcon\Mvc\Micro $app)
    {
        //
    }

    /**
     * @param  \Phalcon\Mvc\Micro   $app
     * @param  array                $config
     * @return \Phalcon\Mvc\Micro
     */
    private function run(\Phalcon\Mvc\Micro $app, array $config)
    {
        $this->initConfig($config);
        $this->initRequest();
        $this->initResponse();
        $this->initEnvironment();
        $this->initRouter();
        $app->setDI($this->di);
        $app->notFound(
            function () use ($app) {
                $app->response->setStatusCode(404, 'Not Found');
                $app->response->setContent('404 Page or File Not Found1');

                return $app->response;
            }
        );
        $app->get('/', function () {
            echo '/';
        });
        $this->initialize($app);

        return $app;
    }

    public function initEnvironment()
    {
        if ($stage = getenv('STAGE_NAME')) {
            throw new \Exception('stage를 확인할수 없습니다.');
        }

        $this->stageName = $stage;
    }

    /**
     * @return string
     */
    public function getStageName()
    {
        return $this->stageName;
    }

    protected function initRouter()
    {
        $routes = $this->getConfigFile(__BASE__.'/routes.yml');

        $this->di->setShared('router', function () use ($routes) {
            $router = new \Peanut\Phalcon\Mvc\Router\Rules\Hash();
            $router->group($routes);

            return $router;
        });
    }
}
