<?php
namespace Peanut;

use Peanut\Parser\Spec;

/**
 * $validator = new Validator($specFile, $appendSpecData);
 * $validator->getSchema();
 * try {
 *     $validator->validate($validData);
 * } catch {\Exception $e} {
 *     print_r($validator->getErrors());
 * }
 */
class Validator
{
    public $schema;
    public $validate;
    public function __construct($specFile, $appendSpecData = [])
    {
        $config       = Spec::parse($specFile);
        $this->schema = new \Peanut\Schema($config, $appendSpecData);
    }
    public function validate($validData = [])
    {
        $this->validate = new \Peanut\Validator\Validate($this->schema->getSpec(), $validData);

        return $this->validate->valid();
    }
    public function getSchema()
    {
        return $this->schema->toArray();
    }
    public function getErrors()
    {
        return $this->validate->getErrors();
    }
}
