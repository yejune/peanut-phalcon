<?php
namespace Peanut\Phalcon\Mvc;

class Model extends \Phalcon\Mvc\Model
{
    public function setRelated($alias, $properties)
    {
        $this->_related[$alias][] = $properties;
    }
}
