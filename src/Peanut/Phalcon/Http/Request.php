<?php
namespace Peanut\Phalcon\Http;

class Request extends \Phalcon\Http\Request
{
    public $bodyParameters    = [];
    public $pathParameters    = [];
    public $segmentParameters = [];
    /**
     * Sets request raw body
     *
     * @param  $rawBody
     * @return $this
     */
    public function setRawBody($rawBody)
    {
        $this->_rawBody = $rawBody;

        return $this;
    }

    /**
     * Sets $_POST parameter
     *
     * @param  $name
     * @param  $value
     * @return $this
     */
    public function setPost($name, $value)
    {
        $_POST[$name] = $value;

        return $this;
    }

    /**
     * Sets $_REQUEST parameter
     *
     * @param  $name
     * @param  $value
     * @return $this
     */
    public function setRequestParameter($name, $value)
    {
        $_REQUEST[$name] = $value;

        return $this;
    }

    /**
     * Sets $_GET parameter
     *
     * @param  $name
     * @param  $value
     * @return $this
     */
    public function setQuery($name, $value)
    {
        $_GET[$name] = $value;

        return $this;
    }

    /**
     * Sets $_SERVER parameter
     *
     * @param  $name
     * @param  $value
     * @return $this
     */
    public function setServer($name, $value)
    {
        $_SERVER[$name] = $value;

        return $this;
    }

    /**
     * Sets request header
     *
     * @param  $name
     * @param  $value
     * @return $this
     */
    public function setHeader($name, $value)
    {
        $this->setServer($name, $value);

        return $this;
    }

    /**
     * Sets and converts raw body to JSON
     *
     * @param  $rawBody
     * @return $this
     */
    public function setJsonRawBody($rawBody)
    {
        $this->_rawBody = json_encode($rawBody);

        return $this;
    }

    /**
     * Sets request HTTP HOST
     *
     * @param  $httpHost
     * @return $this
     */
    public function setHttpHost($httpHost)
    {
        $this->setServer('HTTP_HOST', $httpHost);

        return $this;
    }

    /**
     * Sets request PORT
     *
     * @param  $port
     * @return $this
     */
    public function setPort($port)
    {
        $this->setServer('SERVER_PORT', $port);

        return $this;
    }

    /**
     * Sets server names
     *
     * @param  $serverName
     * @return $this
     */
    public function setServerName($serverName)
    {
        $this->setServer('SERVER_NAME', $serverName);

        return $this;
    }

    /**
     * Sets request URI
     *
     * @param  $uri
     * @return $this
     */
    public function setRequestUri($uri)
    {
        $this->setServer('REQUEST_URI', $uri);

        return $this;
    }

    /**
     * Sets remote address
     *
     * @param  $remoteAddress
     * @return $this
     */
    public function setRemoteAddress($remoteAddress)
    {
        $this->setServer('REMOTE_ADDR', $remoteAddress);

        return $this;
    }

    /**
     * Sets request method
     *
     * @param  $method
     * @return $this
     */
    public function setRequestMethod($method)
    {
        $this->setServer('REQUEST_METHOD', $method);

        return $this;
    }

    /**
     * Sets HTTP User-Agent
     *
     * @param  $userAgent
     * @return $this
     */
    public function setHttpUserAgent($userAgent)
    {
        $this->setServer('HTTP_USER_AGENT', $userAgent);

        return $this;
    }

    /**
     * Sets http referer
     *
     * @param  $httpReferer
     * @return $this
     */
    public function setHttpReferer($httpReferer)
    {
        $this->setServer('HTTP_REFERER', $httpReferer);

        return $this;
    }

    /**
     * Sets HTTP_ACCEPT
     *
     * @param  $httpAccept
     * @return $this
     */
    public function setHttpAccept($httpAccept)
    {
        $this->setServer('HTTP_ACCEPT', $httpAccept);

        return $this;
    }

    /**
     * Sets basic Auth
     *
     * @param  $user
     * @param  $password
     * @return $this
     */
    public function setBasicAuth($user, $password)
    {
        $this->setServer('PHP_AUTH_USER', $user);
        $this->setServer('PHP_AUTH_PW', $password);

        return $this;
    }

    /**
     * Sets digest Auth
     *
     * @param  $digestAuth
     * @return $this
     */
    public function setDigestAuth($digestAuth)
    {
        $this->setServer('PHP_AUTH_DIGEST', $digestAuth);

        return $this;
    }

    /**
     * Sets accept language
     *
     * @param  $acceptLanguage
     * @return $this
     */
    public function setAcceptLanguage($acceptLanguage)
    {
        $this->setServer('HTTP_ACCEPT_LANGUAGE', $acceptLanguage);

        return $this;
    }

    /**
     * Sets accept charset
     *
     * @param  $acceptCharset
     * @return $this
     */
    public function setAcceptCharset($acceptCharset)
    {
        $this->setServer('HTTP_ACCEPT_CHARSET', $acceptCharset);

        return $this;
    }

    /**
     * Sets request Content-Type
     *
     * @param  $contentType
     * @return $this
     */
    public function setContentType($contentType)
    {
        $this->setHeader('Content-Type', $contentType);

        return $this;
    }

    /**
     * Sets Ajax request
     *
     * @return $this
     */
    public function setAjaxRequest()
    {
        $this->setServer('HTTP_X_REQUESTED_WITH', 'XMLHttpRequest');

        return $this;
    }

    /**
     * Gets param
     *
     * @param  $key
     * @return $value
     */
    public function getParam($key)
    {
        $params = $this->getDI()->get('router')->getParams();

        return true === isset($params[$key]) ? $params[$key] : '';
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->getDI()->get('router')->getParams();
    }

    /**
     * @return string
     */
    public function getRewriteUri()
    {
        return $this->getDI()->get('router')->getRewriteUri();
    }

    /**
     * @return array
     * @param null|mixed $index
     */
    public function getSegment($index = null)
    {
        if (!$this->segmentParameters) {
            $uri      = trim($this->getDI()->get('router')->getRewriteUri(), '/');
            $uri      = trim($_SERVER['REQUEST_URI'], '/');
            $segments = [];

            if (false === empty($uri)) {
                $segments = explode('/', $uri);
            }
            $this->segmentParameters = $segments;
        }
        if (null === $index) {
            return $this->segmentParameters;
        }

        return true === isset($this->segmentParameters[$index]) ? $this->segmentParameters[$index] : null;
    }

    public function getPath($pathname = null)
    {
        if (count($this->pathParameters) == 0) {
            $router = $this->getDI()->getShared('router');
            foreach ($router->getMatchedRoute()->getPaths() as $name => $key) {
                $this->pathParameters[$name] = $router->getMatches()[$key];
            }
        }
        if (null === $pathname) {
            return $this->pathParameters;
        }

        return true === isset($this->pathParameters[$pathname]) ? $this->pathParameters[$pathname] : null;
    }

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

    public function getBodyAll()
    {
        $body        = parent::getRawBody();
        $contentType = parent::getHeader('CONTENT_TYPE');

        switch ($contentType) {
            case 'application/x-www-form-urlencoded':
                //parse_str($body, $return);
                //return $return;
                return parent::getPost();
                break;
            case 'application/xml':
            case 'application/xml;charset=UTF-8':
                break;
            case 'application/json':
            case 'application/json;charset=UTF-8':
            default:
                $json = json_decode($body, true);
                if (0 < strlen($body)) {
                    if (json_last_error()) {
                        throw new \Exception('Invalid JSON syntax');
                    }
                } else {
                    $json = [];
                }

                return $json;
                break;
        }
    }

    public function extractDomain($domain)
    {
        if (preg_match("/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i", $domain, $matches)) {
            return $matches['domain'];
        }

        return $domain;
    }
    public function getSubDomain($host = null)
    {
        if (null === $host) {
            $host = $_SERVER['HTTP_HOST'];
        }
        $domain    = $this->extractDomain($host);
        $subDomain = str_replace('.'.$domain, '', $host);

        return $subDomain ?: 'www';
    }
}
