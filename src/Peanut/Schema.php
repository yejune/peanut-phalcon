<?php
namespace Peanut;

class Schema
{
    public $schema;
    public $data     = [];
    public $lang     = '';
    public $rules    = [];
    public $messages = [];
    public function __construct($path, $data = [], $lang = 'ko')
    {
        if (true === is_object($path)) {
            $schema = $path;
        } elseif (true === is_array($path)) {
            $schema = json_decode(json_encode($path));
        } else {
            $schema = json_decode(json_encode(yaml_parse(file_get_contents($path))));
        }
        $this->schema = $schema;
        $this->data   = $data;
        $this->lang   = $lang;
    }
    public function getInfo()
    {
        return [
            'action'   => $this->schema->action,
            'method'   => $this->schema->method,
            'name'     => $this->schema->name,
            'fields'   => $this->getHtml(),
            'spec'     => $this->getSpec(),
        ];
    }
    public function getHtml()
    {
        $typeField = '\\Peanut\\Schema\\Fields\\'.ucfirst($this->schema->type).'Field';
        $field     = new $typeField($this->schema, [], $this->data, $this->lang);

        return $field->fetch();
    }
    public function getSpec()
    {
        $rule = $this->schema->type.'Rule';
        $this->{$rule}($this->schema);
        //pr($this->rules);
        //pr($this->messages);
        //    exit();
        return [
            'rules'    => $this->rules,
            'messages' => $this->messages,
        ];
    }
    public function objectRule($schema, $path = [])
    {
        foreach ($schema->properties as $propKey => $propValue) {
            $rule   = $propValue->type.'Rule';
            $path2  = array_merge($path, [$propKey]);
            $this->{$rule}($propValue, $path2);
        }
    }
    public function groupRule($schema, $path = [])
    {
        foreach ($schema->properties as $propKey => $propValue) {
            $rule  = $propValue->type.'Rule';
            $path2 = array_merge($path, [$propKey]);
            $this->{$rule}($propValue, $path2);
        }
    }
    public function arrayRule($schema, $path = [])
    {
        $this->rules[$this->name($path)]    = $this->getRules($schema->rules??[]);
        $this->messages[$this->name($path)] = $this->getMessages($schema->messages??[]);
    }
    public function phoneRule($schema, $path = [])
    {
        $this->rules[$this->name($path)]    = $this->getRules($schema->rules??[]);
        $this->messages[$this->name($path)] = $this->getMessages($schema->messages??[]);
    }
    public function booleanRule($schema, $path = [])
    {
        $this->rules[$this->name($path)]    = $this->getRules($schema->rules??[]);
        $this->messages[$this->name($path)] = $this->getMessages($schema->messages??[]);
    }
    public function selectRule($schema, $path = [])
    {
        $this->rules[$this->name($path)]    = $this->getRules($schema->rules??[]);
        $this->messages[$this->name($path)] = $this->getMessages($schema->messages??[]);
    }
    public function textRule($schema, $path = [])
    {
        $this->rules[$this->name($path)]    = $this->getRules($schema->rules??[]);
        $this->messages[$this->name($path)] = $this->getMessages($schema->messages??[]);
    }
    public function passwordRule($schema, $path = [])
    {
        $this->rules[$this->name($path)]    = $this->getRules($schema->rules??[]);
        $this->messages[$this->name($path)] = $this->getMessages($schema->messages??[]);
    }
    public function textareaRule($schema, $path = [])
    {
        $this->rules[$this->name($path)]    = $this->getRules($schema->rules??[]);
        $this->messages[$this->name($path)] = $this->getMessages($schema->messages??[]);
    }
    public function hiddenRule($schema, $path = [])
    {
        $this->rules[$this->name($path)]    = $this->getRules($schema->rules??[]);
        $this->messages[$this->name($path)] = $this->getMessages($schema->messages??[]);
    }
    public function captchaRule($schema, $path = [])
    {
        $this->rules[$this->name($path)]    = $this->getRules($schema->rules??[]);
        $this->messages[$this->name($path)] = $this->getMessages($schema->messages??[]);
    }
    public function name($path)
    {
        $var  = array_shift($path);
        if ($path) {
//            $var .= '['.implode('][', $path).']';
            $var .= '_'.implode('_', $path);
        }

        return $var;
    }
    public function getRules($rules)
    {
        return (array)$rules;
    }
    public function getMessages($message)
    {
        if (true === isset($message->{$this->lang})) {
            return (array)$message->{$this->lang};
        }

        return $message;
    }
}

function array_mix(array $array1 = [], array $array2 = [])
{
    $merged = $array1;
    foreach ($array2 as $key => &$value) {
        if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
            $merged [$key] = array_mix($merged [$key], $value);
        } else {
            $merged [$key] = $value;
        }
    }

    return $merged;
}
