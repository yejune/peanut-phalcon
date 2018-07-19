<?php
namespace Peanut\Validator;

class Validate
{
    public static $methods  = [];

    public $defaultMessages = [
        'required'    => 'This field is required.',
        'remote'      => 'Please fix this field.',
        'email'       => 'Please enter a valid email address.',
        'url'         => 'Please enter a valid URL.',
        'date'        => 'Please enter a valid date.',
        'dateISO'     => 'Please enter a valid date (ISO).',
        'number'      => 'Please enter a valid number.',
        'digits'      => 'Please enter only digits.',
        'equalTo'     => 'Please enter the same value again.',
        'maxlength'   => 'Please enter no more than {0} characters.',
        'minlength'   => 'Please enter at least {0} characters.',
        'rangelength' => 'Please enter a value between {0} and {1} characters long.',
        'range'       => 'Please enter a value between {0} and {1}.',
        'max'         => 'Please enter a value less than or equal to {0}.',
        'min'         => 'Please enter a value greater than or equal to {0}.',
        'mincount'    => 'Please enter a value greater than or equal to {0}.',
        'step'        => 'Please enter a multiple of {0}.',
        'unique'      => 'unique',
    ];
    public $messages       = [];
    public $rules          = [];
    public $errors         = [];
    public $data           = [];
    public $debug          = false;
    public $throwException = false;
    public $payload        = [];

    public function __construct($spec = [], $data = [], $files = [])
    {
        if (true === isset($spec['rules'])) {
            $this->rules = $spec['rules'];
        }

        if (true === isset($spec['messages'])) {
            $this->messages = $spec['messages'];
        }
        $this->data = $this->merge($data, $files);
    }
    public function merge(array &$array1, array &$array2)
    {
        $merged = $array1;

        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset($merged [$key]) && is_array($merged [$key])) {
                $merged [$key] = $this->merge($merged [$key], $value);
            } else {
                $merged [$key] = $value;
            }
        }

        return $merged;
    }
    public function getValue($name)
    {
        $name  = str_replace(']', '', $name);
        $names = explode('[', $name);
        $data = $this->data;

        foreach ($names as $name) {
            if (true === isset($data[$name])) {
                $data = $data[$name];
            } else {
                return false;
            }
        }

        return $data;
    }
    public function getMethod($name)
    {
        $callback = static::$methods[$name] ?? null;
        if (true === is_callable($callback)) {
            return $callback->bindTo($this);
        }

        return false;
    }
    public function sprintf($format, $param)
    {
        if (false === is_array($param)) {
            $param = [$param];
        }
        $format = preg_replace("/\{([0-9]+)\}/", '%s', $format);

        return call_user_func_array('sprintf', array_merge([$format], $param));
    }
    public static function addMethod($name, $callback)
    {
        static::$methods[$name] = $callback;
    }
    public function valid()
    {
        $this->errors = [];

        foreach ($this->rules as $fieldName => $rules) {
            $cleanFieldName = preg_replace('#\[\]$#','',$fieldName);// javascript에서의 배열 네임과 php에서의 배열네임간의 차이 제거

            $value = $this->getValue($cleanFieldName);

            $this->payload[$cleanFieldName] = $value;

            if (false !== $value && true === is_array($value)) {
                if (false === \Peanut\is_assoc($value)) {
                    $data = $value;
                } else {
                    if (current($value)) {
                        $data = [0 => $value];
                    } else {
                        $data = [0 => null];
                    }
                }
            } elseif (false !== $value) {
                $data = [0 => $value];
            } else {//값이 없음
                $data = [0 => null];
            }

            $fieldSize = count($data);
            foreach ($data as $dataValue) {

                // file upload error
                if (is_array($dataValue) && isset($dataValue['error']) && $dataValue['error']) {
                    switch ($dataValue['error']) {
                        case \UPLOAD_ERR_INI_SIZE:
                            $message = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
                            break;
                        case \UPLOAD_ERR_FORM_SIZE:
                            $message = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
                            break;
                        case \UPLOAD_ERR_PARTIAL:
                            $message = 'The uploaded file was only partially uploaded';
                            break;
                        case \UPLOAD_ERR_NO_FILE:
                            $message = 'No file was uploaded';
                            break;
                        case \UPLOAD_ERR_NO_TMP_DIR:
                            $message = 'Missing a temporary folder';
                            break;
                        case \UPLOAD_ERR_CANT_WRITE:
                            $message = 'Failed to write file to disk';
                            break;
                        case \UPLOAD_ERR_EXTENSION:
                            $message = 'File upload stopped by extension';
                            break;
                        default:
                            $message = 'Unknown upload error';
                            break;
                    }
                    $this->errors[$cleanFieldName]['upload'] = [
                        'param'   => $ruleParam,
                        'value'   => $dataValue,
                        'message' => $message,
                    ];
                }
                foreach ($rules as $ruleName => $ruleParam) {
                    if ($callback = $this->getMethod($ruleName)) {
                        if (!$callback($dataValue, $cleanFieldName, $ruleParam)) {
                            $message = $this->messages[$cleanFieldName][$ruleName]
                                       ?? ($this->defaultMessages[$ruleName] ?? 'error');

                            $error = [
                                'param'   => $ruleParam,
                                'value'   => $dataValue,
                                'message' => $this->sprintf($message, $ruleParam),
                            ];
                            if (1 < $fieldSize) {
                                $this->errors[$cleanFieldName][][$ruleName] = $error;
                            } else {
                                $this->errors[$cleanFieldName][$ruleName] = $error;
                            }
                            unset($this->payload[$cleanFieldName]);
                        }
                    } else {
                        if ($this->debug) {
                            //not support
                            $this->errors[$cleanFieldName][$ruleName] = [
                                'param'   => $ruleParam,
                                'value'   => $dataValue,
                                'message' => 'not support',
                            ];
                        }
                    }
                }
            }
        }
        if ($this->errors) {
            if ($this->throwException) {
                $e = new ValidateException('Invalid Parameter', 400);
                $e->setErrors($this->errors);
                throw $e;
            }

            return false;
        }

        return true;
    }
    public function getErrors()
    {
        return $this->errors;
    }
    public function getLength($value)
    {
        if (true === is_array($value)) {
            $length = count($value);
        } else {
            $length = count(preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY));
        }

        return $length;
    }
    public function getPayload()
    {
        return $this->payload;
    }
    public function optional($value)
    {
        $callback = $this->getMethod('required');
        // 값이 있으면 false로 보내서 다음 check를 하게 한다.
        return !$callback($value, '', '');
    }
    public function setExceptionOnFailedValid($throwException = true)
    {
        $this->throwException = $throwException;
    }
}

Validate::addMethod('required', function ($value, $name, $param) {
    if (false === $param) {
        return true;
    }
    if (true === is_array($value) && count($value)) {
        return true;
    }
    if (0 < strlen($value) && 0 !== $value) {
        return true;
    }

    return false;
});

Validate::addMethod('recaptcha', function ($value, $name, $param) {
    return $this->optional($value) || $value;
});

Validate::addMethod('minlength', function ($value, $name, $param) {
    $length = $this->getLength($value);

    return $this->optional($value) || $length >= $param;
});

Validate::addMethod('match', function ($value, $name, $param) {
    return $this->optional($value) || preg_match('/^'.$param.'$/', $value);
});

Validate::addMethod('maxlength', function ($value, $name, $param) {
    $length = $this->getLength($value);

    return $this->optional($value) || $length <= $param;
});

Validate::addMethod('rangelength', function ($value, $name, $param) {
    $length = $this->getLength($value);

    return $this->optional($value) || $length >= $param[0] && $length <= $param[1];
});

Validate::addMethod('min', function ($value, $name, $param) {
    return $this->optional($value) || $value >= $param;
});

Validate::addMethod('max', function ($value, $name, $param) {
    return $this->optional($value) || $value <= $param;
});

Validate::addMethod('range', function ($value, $name, $param) {
    return $this->optional($value) || $value >= $param[0] && $value <= $param[1];
});

// required일때 동작
Validate::addMethod('mincount', function ($value, $name, $param) {
    $elements = $this->getValue($name);
    $count = 0;

    if (true === is_array($elements)) {
        foreach ($elements as $val) {
            if ($val) {
                $count++;
            }
        }
    }

    return $this->optional($value) || $count >= $param;

    return $count >= $param;
});

Validate::addMethod('unique', function ($value, $name, $param) {
    $unique = [];
    $check = false;
    $data = $this->getValue($name);
    if (true === is_array($data)) {
        foreach ($data as $v) {
            if (true === isset($unique[$v])) {
                $check = true;
            }
            $unique[$v] = 1;
        }

        return $this->optional($value) || !$check;//$length == $unique;
    }

    return false;
});

Validate::addMethod('email', function ($value, $name, $param) {
    return $this->optional($value) || filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
});

Validate::addMethod('url', function ($value, $name, $param) {
    return $this->optional($value) || filter_var($value, FILTER_VALIDATE_URL) !== false;
});

Validate::addMethod('date', function ($value, $name, $param) {
    return $this->optional($value) || strtotime($value) !== false;
});

Validate::addMethod('dateISO', function ($value, $name, $param) {
    return $this->optional($value) || preg_match('/^\d{4}[\/\-](0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])$/', $value);
});

Validate::addMethod('number', function ($value, $name, $param) {
    return $this->optional($value) || is_numeric($value);
});

Validate::addMethod('digits', function ($value, $name, $param) {
    return $this->optional($value) || preg_match('/^\d+$/', $value);
});

Validate::addMethod('equalTo', function ($value, $name, $param) {
    if ($this->optional($value)) {
        return true;
    }

    $target = ltrim($param, '#.');

    return $value === $this->getValue($target);
});

class ValidateException extends \Peanut\Exception
{
    public $errors = [];
    public function __construct($e, $statusCode = 400)
    {
        parent::__construct($e, $statusCode);

        $tmp = $this->getTrace()[0] ?? [];
        if (true === isset($tmp['file']) && $tmp['file']) {
            $this->setFile($tmp['file']);
            $this->setLine($tmp['line']);
        }
    }
    public function setErrors($errors)
    {
        $this->errors=$errors;
    }
    public function getErrors()
    {
        return $this->errors;
    }
}
