<?php

use Ajl\App;
use Ajl\Log;
use Ajl\ServerException;

require "vendor/autoload.php";

Co::set([
    'socket_connect_timeout' => 1,
    'socket_timeout' => 5,
    'hook_flags' => SWOOLE_HOOK_ALL | SWOOLE_HOOK_CURL
]);

define('DIR', __DIR__);
error_reporting(-1);
//define('LOG_LEVEL_DEBUG', 1);
function exception_error_handler($severity, $message, $file, $line)
{
    throw new ErrorException($message, 0, $severity, $file, $line);
}

set_error_handler("exception_error_handler");

Co\run(function () {
    $server = new Co\Http\Server("0.0.0.0", 9501, false);
    $server->handle('/', function (Swoole\Http\Request $request, Swoole\Http\Response $response) {
        go(function () use ($request, $response) {
            Log::debug(
                __METHOD__ . "[" . __LINE__ . "]>>>>>收到请求:" . PHP_EOL .
                print_r($request, true) . PHP_EOL
            );
            try {
                $app = new App($request, $response);
                $app->proxy();
            } catch (ServerException $exception) {
                $response->status($exception->getCode());
                $response->header("Content-Type", "text/html; charset=utf-8");
                $response->end(
                    "<h1>" . $exception->getMessage() . "</h1>\n" .
                    "<pre>" . $exception->getTraceAsString() . "</pre>"
                );
            } catch (\Exception $exception) {
                $response->status(500);
                $response->header("Content-Type", "text/html; charset=utf-8");
                $response->end(
                    "<h1>" . $exception->getMessage() . "</h1>\n" .
                    "<pre>" . $exception->getTraceAsString() . "</pre>"
                );
            }
        });
        Log::info(__METHOD__ . '[' . __LINE__ . '] ' . $request->server['request_uri'] . ' ' . ($request->server['query_string'] ?? ''));
    });
    $server->start();
});
