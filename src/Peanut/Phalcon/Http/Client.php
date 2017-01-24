<?php
namespace Peanut\Phalcon\Http;

class Client
{
    const DEFAULT_CONNECT_TIMEOUT = 10;
    const DEFAULT_TIMEOUT         = 30;

    public $responseBody;
    public $responseHeaders;
    public $responseRaw;
    public $responseError;
    public $responseHttpCode;

    public $options = [];
    public $headers = [];

    private static $instance;

    public function toArray()
    {
        return [
            'body'     => $this->getBody(),
            'headers'  => $this->getLastHeaders(),
            'httpCode' => $this->getHttpCode(),
        ];
    }
    public function getBody()
    {
        return $this->responseBody;
    }
    public function getHeaders()
    {
        return $this->responseHeaders;
    }
    public function getLastHeaders()
    {
        $headers = $this->responseHeaders;

        return array_pop($headers);
    }
    public function getRaw()
    {
        return $this->responseRaw;
    }
    public function getError()
    {
        return $this->responseError;
    }
    public function getHttpCode()
    {
        return $this->responseHttpCode;
    }

    public static function getInstance()
    {
        if (!static::$instance) {
            static::$instance = new self();
        }

        return static::$instance;
    }
    public static function setHeaders($headers = [])
    {
        static::getInstance()->headers = $headers;

        return static::getInstance();
    }
    public static function setOptions($options = [])
    {
        static::getInstance()->options = $options;

        return static::getInstance();
    }
    public static function delete($url, array $parameters = [], array $headers = [], array $options = [])
    {
        static::getInstance()->execute('DELETE', $url, $parameters, $headers, $options);

        return static::getInstance();
    }
    public static function get($url, array $parameters = [], array $headers = [], array $options = [])
    {
        static::getInstance()->execute('GET', $url, $parameters, $headers, $options);

        return static::getInstance();
    }
    public static function post($url, array $parameters = [], array $headers = [], array $options = [])
    {
        static::getInstance()->execute('POST', $url, $parameters, $headers, $options);

        return static::getInstance();
    }
    public static function put($url, array $parameters = [], array $headers = [], array $options = [])
    {
        static::getInstance()->execute('PUT', $url, $parameters, $headers, $options);

        return static::getInstance();
    }
    public static function patch($url, array $parameters = [], array $headers = [], array $options = [])
    {
        static::getInstance()->execute('PATCH', $url, $parameters, $headers, $options);

        return static::getInstance();
    }
    public function execute($method, $url, array $parameters = [], array $headers = [], array $options = [])
    {
        if (in_array($method, ['GET', 'POST', 'PATCH', 'PUT', 'DELETE']) !== true) {
            throw new \Exception('invalid \'method\'!', 400);
        }
        $headers = array_merge($this->headers, $headers);
        $options = array_merge($this->options, $options);

        $curl                = curl_init();
        $curlOptions         = [
            CURLOPT_URL             => $url,
            CURLOPT_CUSTOMREQUEST   => $method,
            CURLOPT_HTTPHEADER      => $headers,
            CURLOPT_CONNECTTIMEOUT  => self::DEFAULT_CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT         => self::DEFAULT_TIMEOUT,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_HEADER          => true,
        ];

        if (array_key_exists('timeout', $options)) {
            $curlOptions[CURLOPT_TIMEOUT] = $options['timeout'];
        }
        switch ($method) {
            case 'GET':
                $curlOptions[CURLOPT_HTTPGET] = true;
                if ($parameters) {
                    $curlOptions[CURLOPT_URL] .= $this->buildQueryString($parameters);
                }
                break;
            case 'POST':
                $curlOptions[CURLOPT_POST] = true;
            case 'DELETE':
                $curlOptions[CURLOPT_CUSTOMREQUEST] = 'DELETE';
            case 'PUT':
            case 'PATCH':
                $contentType       = '';
                $contentTypeExists = false;

                foreach ($curlOptions[CURLOPT_HTTPHEADER] as $header) {
                    if (1 === preg_match('/^content-type/i', $header)) {
                        $contentType       = $header;
                        $contentTypeExists = true;
                        break;
                    }
                }
                if (false === $contentTypeExists) {
                    array_push($curlOptions[CURLOPT_HTTPHEADER], 'Content-Type: application/json');
                    $contentType = 'application/json';
                }

                if (1 === preg_match('/json/i', $contentType)) {
                    $curlOptions[CURLOPT_POSTFIELDS] = json_encode($parameters);
                } else {
                    $curlOptions[CURLOPT_POSTFIELDS] = $parameters;
                }
                break;
            default:
                throw new \Exception('invalid http request method!');
        }
        curl_setopt_array($curl, $curlOptions);

        $response   = curl_exec($curl);
        $curlError  = curl_error($curl);
        $httpCode   = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);

        curl_close($curl);

        if ($curlError) {
            throw new \Exception($curlError);
        }

        $headerContent = substr($response, 0, $headerSize);
        $body          = substr($response, $headerSize);

        $this->responseRaw      = $response;
        $this->responseHeaders  = $this->getHeadersFromCurlResponse($headerContent);
        $this->responseBody     = $body;
        $this->responseHttpCode = $httpCode;

        return [
            'httpCode'  => $this->responseHttpCode,
            'header'    => $this->responseHeaders,
            'body'      => $this->responseBody,
        ];
    }
    public function getHeadersFromCurlResponse($headerContent)
    {
        $headers     = [];
        $arrRequests = explode("\r\n\r\n", trim($headerContent));

        foreach ($arrRequests as $index => $request) {
            foreach (explode("\r\n", $request) as $i => $line) {
                if ($i === 0) {
                    $headers[$index]['Http-Code'] = $line;
                } else {
                    list($key, $value)     = explode(': ', $line);
                    $headers[$index][$key] = $value;
                }
            }
        }

        return $headers;
    }
    public function buildQueryString($parameters = [])
    {
        return array_reduce(
            array_keys($parameters),
            function ($previous, $key) use ($parameters) {
                return $previous
                    .($previous ? '&' : '?')
                    .$key
                    .'='
                    .urlencode($parameters[$key]);
            },
            ''
        );
    }
}
