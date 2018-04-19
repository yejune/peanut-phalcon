<?php
namespace Peanut;

class Exception extends \Exception
{
    public function __construct($e, $code = 0, \Throwable $previous = null)
    {
        if (true === is_object($e)) {
            $this->setMessage($e->getMessage());
            $this->setLine($e->getLine());
            $this->setFile($e->getFile());
            $this->setCode($e->getCode());
            $this->setTrace($e->getTrace());
            $this->setPrevious($e->getPrevious());
        } else {
            $this->setMessage($e);
            $this->setCode($code);
            $this->setPrevious($previous);
        }
    }
    public function setLine($line)
    {
        $this->line = $line;
    }
    public function setFile($file)
    {
        $this->file = $file;
    }
    public function setMessage($message)
    {
        $this->message = $message;
    }
    public function setCode($code)
    {
        $this->code = $code;
    }
    public function setTrace($trace)
    {
        $this->trace = $trace;
    }
    public function setPrevious($previous)
    {
        $this->previous = $previous;
    }
    public function throw()
    {
        $this->__toString();
    }
}
