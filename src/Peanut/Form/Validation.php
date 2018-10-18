<?php declare(strict_types=1);

namespace Peanut\Form;

class Validation
{
    public $throwException = false;
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
        'accept'      => 'Please enter a value with a valid mimetype.',
    ];

    public function validate(array $specs, array  $data, string $name = '')
    {
        $valid = true;

        foreach ($specs['properties'] as $propertyKey => $propertyValue) {
            $fixPropertyKey = $propertyKey;
            $isArray        = false;

            if (false !== \strpos($fixPropertyKey, '[]')) {
                $fixPropertyKey = \str_replace('[]', '', $fixPropertyKey);
                $isArray        = true;
            }

            $propertyName = $fixPropertyKey;

            if ($name) {
                $propertyName = $name . '[' . $fixPropertyKey . ']';
            }

            $values = $data[$fixPropertyKey] ?? null;

            if ('objects' === $propertyValue['type']) {
                if (false === $isArray) {
                    $result = $this->validate($propertyValue, $values, $propertyName);

                    if (false === $result) {
                        $valid = false;
                    }
                    unset($data[$fixPropertyKey]);
                } else {
                    if (true === \is_array($values)) {
                        foreach ($values as $valueKey => $valueValue) {
                            $result = $this->validate($propertyValue, $valueValue, $propertyName . '[' . $valueKey . ']');

                            if (false === $result) {
                                $valid = false;
                            }
                            unset($data[$fixPropertyKey][$valueKey]);
                        }

                        if (!$data[$fixPropertyKey]) {
                            unset($data[$fixPropertyKey]);
                        }
                    } else {
                        // false 가 아닌 error임. 디폴트 form struct가 있어서 property spec이 배열이면 배열로 넘어와야 함.
                        throw new \Exception('data not found.');
                    }
                }
            } else {
                // valid check
                if (false === $isArray) {
                    $result = $this->check($propertyName, $propertyValue);

                    if (false === $result) {
                        $valid = false;
                    }

                    unset($data[$fixPropertyKey]);
                } else {
                    foreach ($values as $valueKey => $valueValue) {
                        $result = $this->check($propertyName . '[' . $valueKey . ']', $propertyValue);

                        if (false === $result) {
                            $valid = false;
                        }
                        unset($data[$fixPropertyKey][$valueKey]);
                    }

                    if (!$data[$fixPropertyKey]) {
                        unset($data[$fixPropertyKey]);
                    }
                }
            }
        }

        if ($data && $this->throwException) {
            throw new \Exception('spec, element not equal.');
        }

        return $valid;
    }

    public function check($name, $value)
    {
        return true;
    }
}
