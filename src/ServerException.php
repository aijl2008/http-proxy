<?php


namespace Ajl;


use Throwable;

class ServerException extends \RuntimeException
{
    function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}