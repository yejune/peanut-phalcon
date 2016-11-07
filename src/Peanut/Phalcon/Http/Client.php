<?php
namespace Peanut\Phalcon\Http;

class Client
{
    public $url;
    public $requestMethod = 'GET';
    public $headers       = [];
    public $datas         = [];
    public $cafile        = '';

    public function __construct($requestMethod = 'GET', $url)
    {
        $this->requestMethod = $requestMethod;
        $this->url           = $url;
    }

    public function addHeader($headers = [])
    {
        $this->headers = $headers;
    }

    public function addData($datas = [])
    {
        $this->datas = $datas;
    }

    public function cafile($cafile)
    {
        $this->cafile = $cafile;
    }

    public function send()
    {
        $ch = curl_init($this->url);
        if ($this->requestMethod == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        }
        curl_setopt($ch, CURLOPT_HEADER, false);
        if ($this->requestMethod == 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->datas);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($this->cafile) {
            curl_setopt($ch, CURLOPT_CAINFO, $this->cafile);
        }
        if ($this->headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        }
        $output = curl_exec($ch);
        if (! $output) {
            print curl_errno($ch).': '.curl_error($ch);
        }
        $statuscode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $statustext = curl_getinfo($ch);

        if ($statuscode != 200) {
            throw new \Exception($output);
        }

        return $output;
    }
}
