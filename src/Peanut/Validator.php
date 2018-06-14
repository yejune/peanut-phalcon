<?php
namespace Peanut;

use Peanut\Parser\Spec;

/**
 * $validator = new Validator($specFile, $appendSpecData ,$defaultData);
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
    public function __construct($specFile, $appendSpecData = [], $defaultData = [])
    {
        $config       = Spec::parse($specFile, $appendSpecData);
        $this->schema = new \Peanut\Schema($config, $defaultData);
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
