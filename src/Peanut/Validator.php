<?php
namespace Peanut;

class Valid
{
    public $requestMethod    = 'get';
    public $basedir          = __BASE__.'/app/specs';
    public $path             = '';
    public $errors           = [];
    public $spec             = [];
    public $securitySpec     = [];
    public $mode             = 'nomral'; //strict
    public $queryParameters  = [];
    public $pathParameters   = [];
    public $bodyParameters   = [];
    public $headerParameters = [];
    public $authorization    = '';

    /**
     * @method __construct
     * @param  Phalcon\DI\FactoryDefault $di [description]
     */
    public function __construct(\Phalcon\DI\FactoryDefault $di)
    {
        $this->setBasedir(__BASE__.'/app/specs');

        $this->setQueryParameters($di->getShared('request')->getQuery());
        $this->setBodyParameters($di->getShared('request')->getBody());
        $this->setHeaderParameters($di->getShared('request')->getHeaders());
        $this->setPathParameters($di->getShared('router')->getMatchedRoute()->getPaths());

        $this->setMethod($di->getShared('request')->getMethod());
        $this->setPath($di->getShared('router')->getMatchedRoute()->getPattern());
    }

    public function setQueryParameters(array $param = [])
    {
        unset($param['_url']);
        $this->queryParameters = $param;
    }

    public function setPathParameters(array $params = [])
    {
        $this->pathParameters = $params;
    }

    public function setBodyParameters(array $params = [])
    {
        $this->bodyParameters = $params;
    }

    public function setHeaderParameters(array $params = [])
    {
        $this->headerParameters = $params;
    }

    public function getQueryParameter($name)
    {
        if (false === isset($this->queryParameters[$name])) {
            return null;
        } else {
            if (true === is_array($this->queryParameters[$name])) {
                return 0 === count($this->queryParameters[$name]) ? null : $this->queryParameters[$name];
            } else {
                return 0 === strlen($this->queryParameters[$name]) ? null : $this->queryParameters[$name];
            }
        }
    }

    public function getPathParameter($name)
    {
        if (false === isset($this->pathParameters[$name])) {
            return null;
        } else {
            if (true === is_array($this->pathParameters[$name])) {
                return 0 === count($this->pathParameters[$name]) ? null : $this->pathParameters[$name];
            } else {
                return 0 === strlen($this->pathParameters[$name]) ? null : $this->pathParameters[$name];
            }
        }
    }

    public function getFormParameter($name)
    {
        if (false === isset($this->bodyParameters[$name])) {
            return null;
        } else {
            if (true === is_array($this->bodyParameters[$name])) {
                return 0 === count($this->bodyParameters[$name]) ? null : $this->bodyParameters[$name];
            } else {
                return 0 === strlen($this->bodyParameters[$name]) ? null : $this->bodyParameters[$name];
            }
        }
    }

    public function getBodyParameters()
    {
        if (true === is_array($this->bodyParameters)) {
            return 0 === count($this->bodyParameters) ? null : $this->bodyParameters;
        } else {
            return 0 === strlen($this->bodyParameters) ? null : $this->bodyParameters;
        }
    }

    /**
     * @method setBasedir
     * @param  string     $basedir
     */
    public function setBasedir($basedir)
    {
        $this->basedir = $basedir;
    }

    /**
     * [getSpecFilename description]
     * @method getSpecFilename
     * @return string
     */
    public function getSpecFilename()
    {
        return $this->basedir
               .$this->path
               .'/'
               .$this->requestMethod
               .'.yml';
    }

    public function setSpec($swagger)
    {
        if (
            true === isset($swagger['paths'][$this->path])
            && true === isset($swagger['paths'][$this->path][$this->requestMethod])
            ) {
            $this->setSpecByParameter($swagger['paths'][$this->path][$this->requestMethod]);
        } else {
            if ($this->mode == 'strict') {
                throw new \Exception(strtoupper($this->requestMethod).' "'.$this->path.'" spec not exists');
            }
        }
    }

    public function setSpecFile($filename)
    {
        if (true === file_exists($filename)) {
            $this->setSpecByParameter($this->decodeFile($filename));
        } else {
            if ($this->mode == 'strict') {
                throw new \Exception('spec file not exists');
            } else {
                return [];
            }
        }
    }

    public function decodeFile($filename)
    {
        if (false === file_exists($filename)) {
            throw new Exception($filename.' file not exists');
        }
        $contents = file_get_contents($filename);

        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        switch ($ext) {
            case 'yaml':
            case 'yml':
                $result = yaml_parse($contents, true);
                break;

            case 'json':
                $result = json_decode($contents, true);
                if (json_last_error()) {
                    throw new \Exception($filename.' Invalid JSON syntax');
                }
                break;

            default:
                throw new \Exception($ext.' not support');
                break;
        }

        return $result;
    }

    public function setPath($path)
    {
        $this->path = '/'.trim(preg_replace('#/{([^/]+)}$#', '', $path), '/');
    }

    public function setMethod($method)
    {
        $this->requestMethod = strtolower($method);
    }

    public function setSpecByParameter($spec)
    {
        if (true === isset($spec['security'])) {
            $this->securitySpec = $spec['security'];
        }
        $this->spec = (true === isset($spec['parameters']) ? $spec['parameters'] : []);
    }

    public function isAssoc($array)
    {
        $keys = array_keys($array);

        return $keys !== array_keys($keys);
    }

    public function &getSpec()
    {
        return $this->spec;
    }

    public function security()
    {
        // TODO : basic, oauth 등 작업
        foreach ($this->securitySpec as $securities) {
            foreach ($securities as $name => $config) {
                if ('Bearer' == $name) {
                    if (false === isset($this->headerParameters['Authorization'])) {
                        $this->errors['Authorization']['require'] = 'token not found';
                    } else {
                        $this->authorization = $this->headerParameters['Authorization'];
                    }
                }
            }
        }
    }
}

class Validator extends Valid
{
    public function validate()
    {
        $this->security();

        foreach ($this->getSpec() as &$parameter) {
            switch ($parameter['in']) {
                case 'query':
                    $queryValue = $this->getQueryParameter($parameter['name']);
                    $this->checkType($queryValue, $parameter);
                    break;
                case 'path':
                    $pathValue = $this->getPathParameter($parameter['name']);
                    $this->checkType($pathValue, $parameter);
                    break;
                case 'body':
                    // body는 하나의 매개 변수만 있을 수 있다.
                    // 매개 변수의 이름은 매개 변수 자체에 영향을 주지 않으며, 설명 목적으로만 사용.
                    $bodyValue = $this->getBodyParameters();

                    if (false === isset($parameter['name'])) {
                        $parameter['name'] = 'body';
                    }
                    if (null === $bodyValue && true === $parameter['required']) {
                        $this->errors[$parameter['name']]['require'] = 'not found';
                    } elseif (null !== $bodyValue) {
                        $this->propertyKeys[] = $parameter['name'];
                        $this->checkProperty($bodyValue, $parameter['schema']);
                        array_pop($this->propertyKeys);
                    }
                    break;
                case 'formData':
                    $bodyValue = $this->getFormParameter($parameter['name']);
                    $this->checkType($bodyValue, $parameter);
                    break;
            }
        }

        // 요청된 각 매개 변수가 swagger 파일 안에 정의되어있는지 확인한다.
        if ($this->mode == 'strict') {
            $this->strict();
        }

        return $this->errors;
    }

    public function checkSpec($in, $variableName)
    {
        foreach ($this->getSpec() as $parameter) {
            if ($parameter['in'] == $in && $parameter['name'] == $variableName) {
                return true;
            }
        }

        $this->errors[$variableName][$in] = $variableName.' should not be specified';
    }

    public function checkObjectSpec($bodyParameters, $spec)
    {
        if (true === isset($spec['$ref'])) {
            $spec = $this->getReference($spec['$ref']);
        }

        foreach ($bodyParameters as $variableName => $value) {
            $this->propertyKeys[] = $variableName;

            //pr($variableName, $bodyParameters[$variableName]);
            if (true === is_array($value)) {
                if (true === $this->isAssoc($value)) {
                    $this->checkObjectSpec($value, $spec['properties'][$variableName]);
                } else {
                    if (true === isset($spec['properties'][$variableName]['items'])) {
                        $subspec =  $spec['properties'][$variableName]['items'];

                        foreach ($value as $arrayKey => $arrayValue) {
                            $this->propertyKeys[] = '['.$arrayKey.']';

                            if (true === is_array($arrayValue)) {
                                $this->checkObjectSpec($arrayValue, $subspec);
                            } else {
                                //pr($variableName, $arrayKey, $arrayValue, $subspec);
                            }
                            array_pop($this->propertyKeys);
                        }
                    } else {
                    }
                }
            } else {
                if (false === isset($spec['properties']) || false === isset($spec['properties'][$variableName])) {
                    $this->errors[$this->getPropertyKeyName()]['body'] = $value.' body should not be specified';
                } else {
                }
            }
            array_pop($this->propertyKeys);
        }
    }

    public function strict()
    {
        foreach ($this->queryParameters as $variableName => $variableValue) {
            $this->checkSpec('query', $variableName);
        }

        foreach ($this->pathParameters as $variableName => $variableValue) {
            $this->checkSpec('path', $variableName);
        }

        if (1 === count($this->spec) && $this->spec[0]['in'] == 'body') {
            $this->propertyKeys[] = $this->spec[0]['name'];
            $this->checkObjectSpec($this->bodyParameters, $this->spec[0]['schema']);
            array_pop($this->propertyKeys);
        } else {
            foreach ($this->bodyParameters as $variableName => $variableValue) {
                $this->checkSpec('formData', $variableName);
            }
        }

        // TODO : header strict check
    }

    public function getReference($reference)
    {
        if (1 === preg_match('@^(?P<file>)?#/(?P<define>)@', $reference, $match)) {
            if (true === isset($match['define'])) {
                if (true === isset($match['file'])) {
                    $swagger = $this->parserFile($match['file']);
                } else {
                    $swagger = $this->swagger;
                }
                $exploded  = explode('/', $match['define']);
                for ($i = 0, $j = count($exploded);$i < $j;$i++) {
                    $swagger = $swagger[$exploded[$i]];
                }

                return $swagger;
            }
        } else {
            // not support
        }
    }

    public $propertyKeys = [];

    public function getPropertyKeyName()
    {
        return implode('/', $this->propertyKeys);
    }

    public function checkProperty($data, $spec, $parentKey = '')
    {
        if (true === isset($spec['$ref'])) {
            $spec = $this->getReference($spec['$ref']);
        }
        if (false === isset($spec['type'])) {
            if (true === isset($spec['properties'])) {
                $spec['type'] = 'object';
            } elseif (true === isset($spec['items'])) {
                $spec['type'] = 'array';
            }
        }
        switch ($spec['type']) {
            case 'object':
                if (false === is_array($data)) {
                    $this->errors[$this->getPropertyKeyName()]['type'] = 'object type error';
                } else {
                    foreach ($spec['properties'] as $field => $conf) {
                        $this->propertyKeys[] = $field;
                        $required             = (true === isset($spec['required']) && true === in_array($field, $spec['required'], true)) ? true : false;
                        if ($required && false === isset($data[$field])) {
                            $this->errors[$this->getPropertyKeyName()]['required'] = 'required';
                        } elseif (true === isset($data[$field])) {
                            $this->checkProperty($data[$field], $spec['properties'][$field]);
                        } else {
                            //pr($parentKey, $field, $data[$field]);
                        }
                        array_pop($this->propertyKeys);
                    }
                }
                break;
            case 'array':
                if (false === is_array($data)) {
                    $this->errors[$this->getPropertyKeyName()]['type'] = 'array type error';
                } else {
                    foreach ($data as $key => $row) {
                        $this->propertyKeys[] = '['.$key.']';
                        $this->checkProperty($row, $spec['items']);
                        array_pop($this->propertyKeys);
                    }
                }
                break;
            default:
                $spec['name'] = $this->getPropertyKeyName();
                $this->check($data, $spec);
                break;
        }
    }

    public function checkType($data, $parameter)
    {
        if ($parameter['type'] == 'array') {
            if (null === $data) {
                $this->checkRequiredByParameter($data, $parameter);
            } elseif (false === is_array($data)) {
                $this->errors[$parameter['name']]['type'] = 'array type error';
            } else {
                foreach ($data as $key => $row) {
                    $this->checkRequiredByParameter($row, $parameter);
                    if (strlen($row)) {
                        $this->checkEnum($row, $parameter);
                        $parameter['items']['name'] = $parameter['name'];
                        $this->check($row, $parameter['items']);
                    }
                }
            }
        } else {
            $this->checkRequiredByParameter($data, $parameter);
            if (strlen($data)) {
                $this->checkEnum($data, $parameter);
                $this->check($data, $parameter);
            }
        }
    }

    public function checkRequiredByParameter($data, $parameter)
    {
        if (null === $data && true === $parameter['required']) {
            $this->errors[$parameter['name']]['required'] = 'required';
        } elseif (null === $data) {
            return;
        }
    }

    public function checkRequiredByProperty($data, $property)
    {
        $required = (true === isset($spec['required']) && true === in_array($field, $spec['required'], true)) ? true : false;
        if ($required && false === isset($data[$field])) {
            $this->errors[$parentKey.'/'.$field]['required'] = 'required';
        } elseif (null === $data && true === $property['required']) {
            $this->errors[$property['name']]['required'] = 'required';
        } elseif (null === $data) {
            return;
        }
    }

    private function checkEnum($data, $parameter)
    {
        if (true === isset($parameter['enum']) && false === in_array($data, $parameter['enum'], true)) {
            $this->errors[$parameter['name']]['enum'] = 'enum error';
        }
    }

    private function check($data, $parameter)
    {
        if (true === is_array($data)) {
            $this->errors[$parameter['name']]['type'] = $parameter['type'].' type error';

            return;
        }
        // TODO : pattern, format 검사
        switch ($parameter['type']) {
            case 'integer':
                if (0 === preg_match('#^(\+|\-)?([0-9]+)$#', $data)) {
                    $this->errors[$parameter['name']]['type'] = 'integer type error';
                }
                break;
            case 'number':
                if (0 === preg_match('#^(\+|\-)?([0-9]+)(\.([0-9]+))?$#', $data)) {
                    $this->errors[$parameter['name']]['type'] = 'number type error';
                }
                break;
            case 'boolean':
                if (false === is_bool($data)) {
                    $this->errors[$parameter['name']]['type'] = 'boolean type error';
                }
                break;
            case 'string':
                if (false === is_string($data)) {
                    $this->errors[$parameter['name']]['type'] = 'string type error';
                }
                break;
            default:
                if (gettype($data) !== $parameter['type']) {
                    $this->errors[$parameter['name']]['type'] = $parameter['type'].' type error';
                }
                break;
        }
    }
}

/*
$ref - As a JSON Reference
format (See Data Type Formats for further details)
title
description (GFM syntax can be used for rich text representation)
default (Unlike JSON Schema, the value MUST conform to the defined type for the Schema Object)
multipleOf
maximum
exclusiveMaximum
minimum
exclusiveMinimum
maxLength
minLength
pattern
maxItems
minItems
uniqueItems
maxProperties
minProperties
required
enum
type
*/
