<?php
namespace Peanut;

use Peanut\Parser\Spec;

/**
 * $validator = new Validator($specFile, $defaultData);
 * $validator->getSchema();
 * $validator->validate($validData);
 */
class Validator
{
    public $schema;
    public function __construct($specFile, $defaultData = [])
    {
        $config       = Spec::parse($specFile);
        $this->schema = new \Peanut\Schema($config, $defaultData);
    }
    public function validate($validData = [])
    {
        $validate = new \Peanut\Validate($this->schema->getSpec(), $validData);

        return $validate->valid();
    }
    public function getSchema()
    {
        return $this->schema->toArray();
    }
}
