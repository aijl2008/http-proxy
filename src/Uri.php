<?php


namespace Ajl;


class Uri
{

    public $scheme;
    public $host;
    public $port;
    public $path;
    public $query;

    protected function __construct()
    {
    }

    static function parse(string $uri): Uri
    {
        $obj = new self();
        $uri = parse_url($uri);
        if (!isset($uri['host']) || !$uri['host']) {
            $uri['host'] = '127.0.0.1';
        }
        $obj->scheme = $uri['scheme'] ?? 'http';
        $obj->host = $uri['host'];
        $obj->port = '';
        $obj->path = $uri['path'] ?? '/';
        $obj->query = $uri['query'] ?? '';
        $obj->query = urldecode($obj->query);
        switch ($obj->scheme) {
            case 'https':
                if (isset($uri['port']) && $uri['port'] != 443) {
                    $obj->port = $uri['port'];
                }
                break;
            default:
                if (isset($uri['port']) && $uri['port'] != 80) {
                    $obj->port = $uri['port'];
                }
                break;
        }
        return $obj;
    }

    function getBaseUrl()
    {
        return $this->scheme . '://' . $this->host . ($this->port ? (':' . $this->port) : '');
    }

    function getPathWithQuery()
    {
        return $this->path . $this->getQuery();
    }

    function getPath()
    {
        return $this->path;
    }

    function getHost(){
        return $this->host;
    }
    function getQuery()
    {
        if (!$this->query) {
            return '';
        }
        return '?' . $this->query;
    }
}