<?php
namespace Peanut\Bootstrap;

class Basic extends \Peanut\Bootstrap
{
    /**
     * @param  $app
     * @return $app
     */
    public function __invoke($app)
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
     * @param $app
     */
    protected function initialize($app)
    {
    }

    /**
     * @param    $app
     * @return   $app
     */
    private function run($app)
    {
        $app->setDi($this->di);
        $this->initialize($app, $this->di);

        return $app;
    }
}
