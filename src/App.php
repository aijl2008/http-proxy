<?php


namespace Ajl;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;

class App
{
    protected $cache = null;
    protected $targetUrl = '';
    /**
     * @var Swoole\Server\Request
     */
    protected $request = null;
    /**
     * @var Swoole\Server\Response
     */
    protected $response = null;

    function __construct($request, $response)
    {
        $this->request = $request;
        $this->response = $response;
        $this->cache = new Cache();
        $this->targetUrl = $this->getTargetUrl();
    }

    protected function getTargetUrlByCache()
    {
        $uri = Uri::parse($this->request->header['referer']);
        $referer = $this->cache->get(urlencode($uri->getPathWithQuery()));
        if (!$referer) {
            Log::error('取[' . $uri->getPathWithQuery() . ']映射失败');
            throw new ServerException("解析目标地址失败" . __METHOD__ . "[" . __LINE__ . "]", 400);
        }
        $referUri = Uri::parse($referer);
        if (explode('/', trim($this->request->server['request_uri'], '/'))[0] == explode('/', trim($referUri->getPath(), '/'))[0]) {
            return $referUri->getBaseUrl() . $this->request->server['request_uri'];
        } else {
            return $referUri->getBaseUrl() . $this->request->server['request_uri'];
        }
    }

    protected function getTargetUrlByReferer()
    {
        if (!isset($this->request->header['referer'])) {
            throw new ServerException("解析目标地址失败" . __METHOD__ . "[" . __LINE__ . "]", 400);
        }
        $query = parse_url($this->request->header['referer'], PHP_URL_QUERY);
        if (!$query) {
            return $this->getTargetUrlByCache();
        }

        if (stripos($query, 'http://') !== 0 && stripos($query, 'https://') !== 0) {
            return $this->getTargetUrlByCache();
        }

        $referUri = Uri::parse($query);
        $targetUrl = $referUri->getBaseUrl() . $this->getRequestWithQuery();
        $sourceUrl = $this->getRequestWithQuery();
        Log::info(__METHOD__ . "[" . __LINE__ . "]创建HOST映射[" . $sourceUrl . ']' . $targetUrl);
        $this->cache->set(urlencode($sourceUrl), $targetUrl);
        return $targetUrl;
    }

    protected function getTargetUrl()
    {
        if ($this->request->server['request_uri'] !== '/') {
            return $this->getTargetUrlByReferer();
        }

        if (is_null($this->request->get)) {
            throw new ServerException("解析目标地址失败" . __METHOD__ . "[" . __LINE__ . "]", 400);
        }

        return str_replace(
            ['/?https://', '/?http://'],
            ['https://', 'http://'],
            $this->request->server["query_string"]
        );
    }

    protected function getShouldSendHeaders($targetUri)
    {
        $headers = [];
        foreach ($this->request->header as $name => $value) {
            if (in_array($name, ["Content-Length", "Host", "Origin"])) {
                continue;
            }
            $headers[$name] = $value;
        }
        $headers['user-agent'] = $this->request->header['user-agent'] ?? phpversion();
        $headers['referer'] = $targetUri->getBaseUrl();
        return $headers;
    }

    protected function getRequestWithQuery()
    {
        return $this->request->server['request_uri'] . (isset($this->request->server['query_string']) ? ('?' . $this->request->server['query_string']) : '');
    }

    function proxy()
    {
        $targetUri = Uri::parse($this->targetUrl);
        /**
         * 转发的Header
         */
        $requestHeaders = $this->getShouldSendHeaders($targetUri);
        $desc = $this->getRequestWithQuery() . ' => ' . $targetUri->getBaseUrl() . $targetUri->getPathWithQuery();
        try {
            $startAt = microtime(true);
            $client = new Client([
                'base_uri' => $targetUri->getBaseUrl(),
                'allow_redirects' => true,
                'connect_timeout' => 5,
                //  'debug' => true,
                'force_ip_resolve' => 'v4',
                'headers' => $requestHeaders
            ]);

            //$jar = new \GuzzleHttp\Cookie\CookieJar;
            if ($this->request->cookie){
                $jar = \GuzzleHttp\Cookie\CookieJar::fromArray(
                    $this->request->cookie,
                    explode(':', $this->request->header['host'])[0]
                );
            }
            else{
                $jar = null;
            }


            switch ($this->request->server["request_method"]) {
                case "GET":
                    $response = $client->request("GET",
                        $targetUri->getPathWithQuery(),
                        //'http://localhost:8080/server.php',
                        ['cookies' => $jar]
                    );

                    break;
                case "POST":
                    $response = $client->request("POST",
                        $targetUri->getPathWithQuery(),
                        [
                            'cookies' => $jar,
                            'form_params' => $this->request->post
                        ]
                    );

                    break;
                default:
                    throw new ServerException("不支持" . $this->request->server["request_method"], 405);
                    break;
            }


            $endAt = microtime(true);
            $code = $response->getStatusCode();

            if ($code != 200) {
                $this->response->status($code);
                Log::info(__METHOD__ . "[" . __LINE__ . '] ' . $code . ' ' . $code . ' ' . (round(($endAt - $startAt) / 100, 3)) . ' ' . strlen($response->getBody()->__toString()) . ' ' . $desc);
                return;
            }

            /**
             * 将服务器Header原样响应给浏览器
             */
            $responseHeader = [];
            foreach ($response->getHeaders() as $name => $val) {
                $responseHeader[$name] = $response->getHeaderLine($name);
                if ($name == 'Transfer-Encoding') {
                    continue;
                }
                $this->response->header($name, $response->getHeaderLine($name));
            }
            if (!isset($headers['Content-Length'])) {
                $this->response->header('Content-Length', strlen($response->getBody()->__toString()));
            }
            Log::debug(__METHOD__ . "[" . __LINE__ . "]>>>>>收到并转发HEADER:" . print_r($responseHeader, true));


            $conversion = new Conversion();
            $html = $conversion->convert($response->getBody()->__toString(), $response->getHeaderLine('Content-Type'));

            Log::info(__METHOD__ . "[" . __LINE__ . '] 200 ' . $code . ' ' . (round(($endAt - $startAt) / 100, 3)) . ' ' . strlen($html) . ' ' . $desc);
            $this->response->end($html);

        } catch (TransferException $exception) {
            if (method_exists($exception, 'hasResponse') && $exception->hasResponse()) {
                $this->response->status($exception->getResponse()->getStatusCode());
                $this->response->end(
                    "<h1>" . $exception->getMessage() . "</h1>\n" .
                    "<pre>" . $exception->getTraceAsString() . "</pre>"
                );
            } else {
                $this->response->status(500);
                $this->response->end(
                    "<h1>" . $exception->getMessage() . "</h1>\n" .
                    "<pre>" . $exception->getTraceAsString() . "</pre>"
                );
            }
            Log::warning(__METHOD__ . "[" . __LINE__ . '>>>>>000 ' . $desc . ' ||| ' . $exception->getMessage());
        }
    }
}