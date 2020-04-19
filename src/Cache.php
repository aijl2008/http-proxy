<?php


namespace Ajl;


class Cache
{
    protected $dir = null;

    function __construct()
    {
        $this->dir = DIR . '/cache/';
        if (!file_exists($this->dir)) {
            mkdir($this->dir);
        }
    }

    function get($key)
    {
        if (!file_exists($this->dir . $key)) {
            Log::error($key . '不存在');
            return null;
        }
        return file_get_contents($this->dir . $key);
    }

    function set($key, $value)
    {
        if (!$value) {
            Log::error('写入' . $key . '失败,value不能为空');
            return;
        }
        if (false === file_put_contents($this->dir . $key, $value)) {
            Log::error('写入' . $key . '失败');
        }
    }
}