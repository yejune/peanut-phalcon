<?php
namespace Peanut\Phalcon\Cache\Backend;

class Redis extends \Phalcon\Cache\Backend\Redis
{
    public function setAndGet($name, $lifetime, callable $callback)
    {
        if (false === $this->exists($name)) {
            // $callback->bindTo($this);
            $content = $callback();
            $this->save($name, $content, $lifetime);
        } else {
            $content = $this->get($name);
        }

        return $content;
    }
}
