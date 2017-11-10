<?php
namespace Peanut;

class Bootstrap
{
    /**
     * @var \Phalcon\DI\FactoryDefault
     */
    public $di;

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
}
