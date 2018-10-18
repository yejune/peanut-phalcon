<?php declare(strict_types=1);

namespace Peanut;

class Form
{
    public $spec;

    public function __construct(array $spec = [])
    {
        $this->spec = $spec;
    }

    public function validation(array $data = [])
    {
        $validation = new \Peanut\Form\Validation;

        return $validation->validate($this->spec, $data);
    }

    public function write(array $data = [])
    {
        $generation = new \Peanut\Form\Generation;

        return $generation->write($this->spec, $data);
    }

    public function read(array $data = [])
    {
        $generation = new \Peanut\Form\Generation;

        return $generation->read($this->spec, $data);
    }

    public function list(array $data = [])
    {
        $generation = new \Peanut\Form\Generation;

        return $generation->list($this->spec, $data);
    }
}
