<?php
namespace Peanut\Bootstrap;

class Basic extends \Peanut\Bootstrap
{
    /**
     * @param  \Phalcon\Mvc\Micro   $app
     * @return \Phalcon\Mvc\Micro
     */
    public function __invoke(\Phalcon\Mvc\Micro $app)
    {
        return $this->run($app);
    }

    /**
     * @return string
     */
    public function getHttpHost()
    {
        return $this->getDi()->get('request')->getHttpHost();
    }

    /**
     * @param $config
     */
    protected function initialize(\Phalcon\Mvc\Micro $app)
    {
    }

    /**
     * @param  \Phalcon\Mvc\Micro   $app
     * @return \Phalcon\Mvc\Micro
     */
    private function run(\Phalcon\Mvc\Micro $app)
    {
        $app->setDi($this->di);
        $this->initialize($app, $this->di);

        return $app;
    }
}
