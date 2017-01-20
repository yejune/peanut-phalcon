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

        curl_setopt($ch, CURLOPT_HEADER, false);

        if ($this->requestMethod == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->datas);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($this->headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        }

        $output = curl_exec($ch);

        $statuscode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $statustext = curl_getinfo($ch);

        if ($statuscode == 204) {
        } elseif ($statuscode != 200) {
            if (! $output) {
                throw new \Exception(curl_error($ch).' '.curl_errno($ch));
            }
            throw new \Exception($output);
        }

        return $output;
    }
}
