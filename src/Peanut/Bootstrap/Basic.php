<?php
namespace Peanut\Bootstrap;

class Basic extends \Peanut\Bootstrap
{
    /**
     * @param  \Phalcon\Mvc\Nano   $app
     * @return \Phalcon\Mvc\Nano
     */
    public function __invoke(\Peanut\Phalcon\Mvc\Nano $app)
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
    protected function initialize(\Peanut\Phalcon\Mvc\Nano $app)
    {
    }

    /**
     * @param  \Phalcon\Mvc\Nano   $app
     * @return \Phalcon\Mvc\Nano
     */
    private function run(\Peanut\Phalcon\Mvc\Nano $app)
    {
        $app->setDi($this->di);
        $this->initialize($app, $this->di);

        return $app;
    }
}
