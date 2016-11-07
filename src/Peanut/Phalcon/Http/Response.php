<?php
namespace Peanut\Phalcon\Http;

use Peanut\Phalcon\Mvc\Micro;

class Response extends \Phalcon\Http\Response
{
    /**
     * @param  array   $content
     * @return $this
     */
    public function setJsonContent($content)
    {
        if (!parent::getHeaders()->get('Content-Type')) {
            parent::setContentType('application/json', 'UTF-8');
        }

        parent::setJsonContent($content);

        return $this;
    }

    /**
     * @return array
     */
    public function getJsonContent()
    {
        return json_decode(parent::getContent(), true);
    }

    public function forward($url)
    {
        return $this->getDI()->get('application')->handle($url);
    }

    public function content($response)
    {
        if (true === isset($response['status'])) {
            parent::setStatusCode($response['status']);
        }

        $contentTypeAll = $this->getDI()->get('request')->getAcceptableContent();

        foreach ($contentTypeAll as $contentType) {
            switch ($contentType['accept']) {
                case 'text/html':
                case 'application/xhtml':
                    parent::setContentType('text/html', 'UTF-8');
                    parent::setContent(html_encode($response));
                    break(2);

                case 'application/xml':
                case 'application/xml;charset=UTF-8':
                    break(2);

                case 'application/json':
                case 'application/json;charset=UTF-8':
                default:
                    parent::setContentType('application/json', 'UTF-8');
                    parent::setJsonContent($response);
                    break(2);
            }
        }

        return $this;
    }
}

function html_encode($in)
{
    $t = '<table border=1 cellspacing="0" cellpadding="0">';
    foreach ($in as $key => $value) {
        if (is_array($value)) {
            $t .= '<tr><td>'.$key.'</td><td>'.html_encode($value).'</td></tr>';
        } else {
            $t .= '<tr><td>'.$key.'</td><td>'.$value.'</td></tr>';
        }
    }

    return $t.'</table>';
}
