<?php
namespace Peanut\Phalcon\Logger\Adapter;

class System
{
    public $outputFormat = '';
    public $fifo;
    public function __construct($fifo = null, $outputFormat = null)
    {
        $this->fifo = $fifo;
        if ($outputFormat) {
            $this->outputFormat = $outputFormat;
        }
    }
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
            'type'        => 'php',
            'mode'        => $type,
            'fields'      => $messages,
            'time'        => date('Y-m-d\TH:i:sP'),
            'remote_addr' => $this->getClientIp(),
            'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'request_uri' => $_SERVER['PATH_INFO'] ?? '',
            'host'        => $_SERVER['HTTP_HOST'] ?? '',
        ];
        if ('json' == $this->outputFormat) {
            $format = json_encode($array, JSON_UNESCAPED_UNICODE);
        } else {
            $smessage = [];
            foreach ($messages as $key => $value) {
                $smessage[] = $value;
            }
            $smessage = implode(' ', $smessage);

            $format = sprintf(
                '%s - - [%s] "%s %s %s" "%s" "%s"',
                $this->getClientIp(),
                date('Y-m-d\TH:i:sP'),
                $_SERVER['REQUEST_METHOD'] ?? '',
                $this->getPath(),
                $this->getProtocal(),
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $smessage
            );
        }
        if ($this->fifo) {
            error_log($format.PHP_EOL, 3, $this->fifo);
        } else {
            error_log($format.PHP_EOL);
        }
    }
    public function setOutputFormat($format)
    {
        $this->outputFormat = $format;
    }
    public function debug($message)
    {
        $this->printOutput($message, __FUNCTION__);
    }
    public function info($message)
    {
        $this->printOutput($message, __FUNCTION__);
    }
    public function notice($message)
    {
        $this->printOutput($message, __FUNCTION__);
    }
    public function warning($message)
    {
        $this->printOutput($message, __FUNCTION__);
    }
    public function error($message)
    {
        $this->printOutput($message, __FUNCTION__);
    }
    public function critical($message)
    {
        $this->printOutput($message, __FUNCTION__);
    }
    public function log($message)
    {
        $this->printOutput($message, __FUNCTION__);
    }
    public function getProtocal()
    {
        if (true === isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
            return 'HTTPS';
        }

        return 'HTTP';
    }
    public function getClientIp()
    {
        $ipaddress = '';
        if (true === isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (true === isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (true === isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (true === isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (true === isset($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } elseif (true === isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipaddress = '127.0.0.1';
        }

        return $ipaddress;
    }
    public function getPath()
    {
        if (true === isset($_SERVER['argv'])) {
            return implode(' ', $_SERVER['argv']);
        }
        if (true === isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO']) {
            return $_SERVER['PATH_INFO'];
        }

        return '/';
    }
}
