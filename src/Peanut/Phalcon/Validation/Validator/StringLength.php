<?php
namespace Peanut\Phalcon\Validation\Validator;

use Phalcon\Validation;
use Phalcon\Validation\Validator;
use Phalcon\Validation\Exception;
use Phalcon\Validation\Message;

class StringLength extends Validator
{
    public function strlen($str)
    {
        preg_match_all('/[\xEA-\xED][\x80-\xFF]{2}|./u', $str, $match);
        $m     = $match[0];
        $slen  = count($m);
        $count = 0;

        for ($i = 0; $i < $slen; $i++) {
            //if(isset($m[$i]) == false) break;
            $count += (strlen($m[$i]) > 1) ? 2 : 1;
        }

        return $count;
    }

    /**
     * Executes the validation
     */
    public function validate(Validation $validation, /*string! */$field)
    {
        // At least one of 'min' or 'max' must be set
        $isSetMin = $this->hasOption('min');
        $isSetMax = $this->hasOption('max');

        if (!$isSetMin && !$isSetMax) {
            throw new Exception('A minimum or maximum must be set');
        }

        $value = $validation->getValue($field);
        $label = $this->getOption('label');
        if (gettype($label) == 'array') {
            $label = $label[$field];
        }
        if (empty($label)) {
            $label = $validation->getLabel($field);
        }

        $code = $this->getOption('code');
        if (gettype($code) == 'array') {
            $code = $code[$field];
        }

        $length = $this->strlen($value);

        /**
         * Maximum length
         */
        if ($isSetMax) {
            $maximum = $this->getOption('max');
            if (gettype($maximum) == 'array') {
                $maximum = $maximum[$field];
            }
            if ($length > $maximum) {

                // Check if the developer has defined a custom message
                $message      = $this->getOption('messageMaximum');
                $replacePairs = [':field' => $label, ':max' =>  $maximum];

                if (gettype($message) == 'array') {
                    $message = $message[$field];
                }

                if (empty($message)) {
                    $message = $validation->getDefaultMessage('TooLong');
                }

                $validation->appendMessage(
                    new Message(
                        strtr($message, $replacePairs),
                        $field,
                        'TooLong',
                        $code
                    )
                );

                return false;
            }
        }

        /**
         * Minimum length
         */
        if ($isSetMin) {
            $minimum = $this->getOption('min');
            if (gettype($minimum) == 'array') {
                $minimum = $minimum[$field];
            }
            if ($length < $minimum) {

                // Check if the developer has defined a custom message
                $message      = $this->getOption('messageMinimum');
                $replacePairs = [':field' => $label, ':min' =>  $minimum];

                if (gettype($message) == 'array') {
                    $message = $message[$field];
                }

                if (empty($message)) {
                    $message = $validation->getDefaultMessage('TooShort');
                }

                $validation->appendMessage(
                    new Message(
                        strtr($message, $replacePairs),
                        $field,
                        'TooShort',
                        $code
                    )
                );

                return false;
            }
        }

        return true;
    }
}
