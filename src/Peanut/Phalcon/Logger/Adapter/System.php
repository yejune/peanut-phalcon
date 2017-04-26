<?php
namespace Peanut\Phalcon\Logger\Adapter;

class System
{
    public $outputFormat = 'json';
    public function printOutput($message, $type = 'log')
    {
        if (false === is_array($message)) {
            $messages = [
                'message' => $message,
            ];
        } else {
            $messages = $message;
        }
        $array = [
            'adapter'  => 'system',
            'type'     => $type,
            'messages' => $messages,
            'time'     => date('Y-m-d\TH:i:sP'),
        ];
        if ('json' == $this->outputFormat) {
            $format = json_encode($array);
        } else {
            $format = http_build_query($array);
        }
        error_log($format);
    }
    public function setOutputFormat($format)
    {
        $this->outputFormat = $format;
    }
    public function debug($message)
    {
        $this->printOutput($message, __METHOD__);
    }
    public function info($message)
    {
        $this->printOutput($message, __METHOD__);
    }
    public function notice($message)
    {
        $this->printOutput($message, __METHOD__);
    }
    public function warning($message)
    {
        $this->printOutput($message, __METHOD__);
    }
    public function error($message)
    {
        $this->printOutput($message, __METHOD__);
    }
    public function critical($message)
    {
        $this->printOutput($message, __METHOD__);
    }
    public function log($message)
    {
        $this->printOutput($message, __METHOD__);
    }
}
