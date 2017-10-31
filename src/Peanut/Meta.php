<?php
namespace Peanut;

class Meta
{
    public function __call($name, $args)
    {
        if (1 === preg_match('#set(?P<name>.*)(?P<isArray>s)?$#U', $name, $m)) {
            $prop = lcfirst($m['name']);

            if (true === isset($m['isArray'])) {
                if (false === isset($this->$prop)) {
                    $this->$prop = [];
                }
                $this->$prop[] = $args[0];
            } else {
                $this->$prop = $args[0];
            }
        }
    }
    public function toArray()
    {
        return (array)$this;
    }
}
