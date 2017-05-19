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
     * @param  \Phalcon\Mvc\Micro   $app
     * @return \Phalcon\Mvc\Micro
     */
    public function __invoke(\Phalcon\Mvc\Micro $app)
    {
        return $this->run($app);
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
     * @param null|mixed $name
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
        $this->getDi()->setShared('request', function () {
            return new \Peanut\Phalcon\Http\Request();
        });
    }

    public function initResponse()
    {
        $this->getDi()->setShared('response', function () {
            return new \Peanut\Phalcon\Http\Response();
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
                throw new \Peanut\Exception($configFile.' can\'t be loaded.');
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return $config;
    }

    public function initEnvironment()
    {
        if ($stage = getenv('STAGE_NAME')) {
            throw new \Peanut\Exception('stage를 확인할수 없습니다.');
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

    /**
     * @param $config
     */
    protected function initialize(\Phalcon\Mvc\Micro $app)
    {
    }

    protected function initRouter()
    {
        $routes = $this->getConfigFile(__BASE__.'/routes.yml');
        $this->getDi()->setShared('router', function () use ($routes) {
            $router = new \Peanut\Phalcon\Mvc\Router\Rules\Hash();
            $router->group($routes);

            return $router;
        });
    }

    /**
     * @param  \Phalcon\Mvc\Micro   $app
     * @return \Phalcon\Mvc\Micro
     */
    private function run(\Phalcon\Mvc\Micro $app)
    {
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
}
