<?php
namespace Peanut\Phalcon\Http;

class Request extends \Phalcon\Http\Request
{
    public $bodyParameters    = [];
    public $pathParameters    = [];
    public $segmentParameters = [];
    public $parameters        = [];
    public $requestId;
    public $basepath = null;
    public $fileKeys = ['error', 'name', 'size', 'tmp_name', 'type'];

    public function setBasePath($path)
    {
        $this->basepath = $path;
    }
    public function getBasePath()
    {
        return $this->basepath;
    }
    /**
     * Gets param
     *
     * @param  $key
     * @return $value
     */
    public function getParam($key)
    {
        return $this->getDi()->get('dispatcher')->getParam($key);
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->getDi()->get('dispatcher')->getParams();
    }

    /**
     * @return string
     */
    public function getRequestPath()
    {
        return rtrim($this->getDI()->get('router')->getRewriteUri(), '/');
    }

    /**
     * @return array
     * @param null|mixed $index
     */
    public function getSegment($index = null)
    {
        if (!$this->segmentParameters) {
            $this->getSegments();
        }
        if (null === $index) {
            return $this->segmentParameters;
        }

        return true === isset($this->segmentParameters[$index]) ? $this->segmentParameters[$index] : null;
    }

    public function getSegments()
    {
        if (!$this->segmentParameters) {
            $path     = $this->getRequestPath();
            $segments = [];

            if (false === empty($path)) {
                $segments = explode('/', trim($path, '/'));
            }
            $this->segmentParameters = $segments;
        }

        return $this->segmentParameters;
    }

    // public function getPath($pathname = null)
    // {
    //     if (0 === count($this->pathParameters)) {
    //         $router = $this->getDI()->getShared('router');
    //         pr($router->getMatchedRoute()->getPaths());
    //         pr($router->getMatches());
    //         foreach ($router->getMatchedRoute()->getPaths() as $name => $key) {
    //             //$this->pathParameters[$name] = $router->getMatches()[$key];
    //         }
    //     }
    //     if (null === $pathname) {
    //         return $this->pathParameters;
    //     }

    //     return true === isset($this->pathParameters[$pathname]) ? $this->pathParameters[$pathname] : null;
    // }

    public function getBody($bodyname = null)
    {
        if (!$this->bodyParameters) {
            $this->bodyParameters = $this->getBodyAll();
        }
        if (null === $bodyname) {
            return $this->bodyParameters;
        }

        return true === isset($this->bodyParameters[$bodyname]) ? $this->bodyParameters[$bodyname] : null;
    }
    public function fixPhpFilesArray($data)
    {
        if (!is_array($data)) {
            return $data;
        }
        $keys = array_keys($data);
        sort($keys);
        if ($this->fileKeys != $keys || !isset($data['name']) || !is_array($data['name'])) {
            return $data;
        }
        $files = $data;
        foreach ($this->fileKeys as $k) {
            unset($files[$k]);
        }
        foreach ($data['name'] as $key => $name) {
            $files[$key] = $this->fixPhpFilesArray([
                'error'    => $data['error'][$key],
                'name'     => $name,
                'type'     => $data['type'][$key],
                'tmp_name' => $data['tmp_name'][$key],
                'size'     => $data['size'][$key],
            ]);
        }

        return $files;
    }
    public function convertFileInformation($file)
    {
        $file = $this->fixPhpFilesArray($file);
        if (is_array($file)) {
            $keys = array_keys($file);
            sort($keys);
            if ($keys == $this->fileKeys) {
                if (UPLOAD_ERR_NO_FILE == $file['error']) {
                    $file = null;
                } else {
                    $file = [
                        'name'     => $file['name'],
                        'type'     => $file['type'],
                        'tmp_name' => $file['tmp_name'],
                        'error'    => $file['error'],
                        'size'     => $file['size'],
                    ];
                }
            } else {
                $file = array_map([$this, 'convertFileInformation'], $file);
                if (array_keys($keys) === $keys) {
                    $file = array_filter($file);
                }
            }
        }

        return $file;
    }
    public function getFileAll()
    {
        if (true === isset($_FILES) && $_FILES) {
            return $this->convertFileInformation($_FILES);
        }

        return [];
    }
    public function getBodyAll()
    {
        if ($this->bodyParameters) {
            return $this->bodyParameters;
        }

        $body        = parent::getRawBody();
        $contentType = explode(';', parent::getContentType())[0];

        switch ($contentType) {
            case 'application/x-www-form-urlencoded':
                parse_str($body, $return);

                return $this->bodyParameters = $return;
                break;
            case 'multipart/form-data':
                if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) {
                    throw new \App\Exceptions\Exception(sprintf('The server was unable to handle that much POST data (%s bytes) due to its current configuration', $_SERVER['CONTENT_LENGTH']));
                }

                return $this->bodyParameters = $_POST;
                break;
            case 'application/xml':
                throw new \App\Exceptions\Exception('xml content type not support', 415);
            break;
                break;
            case 'application/json':
            case 'text/javascript':
            default:
                $body = str_replace(' \\', '', $body);
                $json = json_decode($body, true);
                if (0 < strlen($body)) {
                    if ($type = json_last_error()) {
                        switch ($type) {
                            case JSON_ERROR_DEPTH:
                                $message = 'Maximum stack depth exceeded';
                            break;
                            case JSON_ERROR_CTRL_CHAR:
                                $message = 'Unexpected control character found';
                            break;
                            case JSON_ERROR_SYNTAX:
                                $message = 'Syntax error, malformed JSON';
                            break;
                            case JSON_ERROR_NONE:
                                $message = 'No errors';
                            break;
                            case JSON_ERROR_UTF8:
                                $message = 'Malformed UTF-8 characters';
                            break;
                            default:
                                $message = 'Invalid JSON syntax';
                        }
                        throw new \Peanut\Exception($message);
                    }
                } else {
                    $json = [];
                }

                return $this->bodyParameters = $json;
                break;
        }
    }

    public function getIp()
    {
        return parent::getClientAddress(true);
    }

    public function getIp2Long()
    {
        return ip2long($this->getIp());
    }

    public function getRequest($name)
    {
        $post = parent::getPost($name);
        if ($post) {
            return $post;
        }

        return parent::getQuery($name);
    }

    public function extractDomain($domain)
    {
        if (1 === preg_match("/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i", $domain, $matches)) {
            return $matches['domain'];
        }

        return $domain;
    }

    public function getSubDomain($host = null)
    {
        if (null === $host) {
            $host = $_SERVER['HTTP_HOST'] ?? null;
        }
        $domain    = $this->extractDomain($host);
        $subDomain = preg_replace('#(\.)?'.$domain.'#', '', $host);

        return $subDomain ?: 'www';
    }

    public function getSchemeHost()
    {
        return parent::getScheme().'://'.parent::getHttpHost();
    }

    public function isCli()
    {
        return php_sapi_name() == 'cli';
    }

    public function getSliceUri($depth)
    {
        $segments = $this->getSegments();
        if (count($segments) < 2) {
            $url = $this->getRequestPath();
        } else {
            $url = '';
            $i   = 1;
            foreach ($segments as $segment) {
                $url .= '/'.$segment;
                if ($i == $depth) {
                    break;
                }
                $i++;
            }
        }

        return trim($url, '/');
    }

    public function getRequestId()
    {
        if (!$this->requestId) {
            if (true === isset($_SERVER['HTTP_REQUEST_ID'])) {
                $this->requestId = $_SERVER['HTTP_REQUEST_ID'];
            } else {
                $this->requestId = \Peanut\uniqid(32);
            }
        }

        return $this->requestId;
    }

}
