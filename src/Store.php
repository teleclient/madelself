<?php

use Amp\Promise;

class Store
{
    private static $instance;

    public static function getInstance()
    {
        return !isset(self::$instance) ? self::$instance = new self : self::$instance;
    }

    private $cache;

    private function __construct()
    {
        $this->cache = new Amp\Cache\ArrayCache();
    }
    public function __destruct()
    {
            $this->cache->__destruct();
    }

    public function set(string $key, string $value): Promise
    {
        return $this->cache->set($key, $value);
    }

    public function get(string $key): Promise
    {
        return $this->cache->get($key);
    }

    public function delete(string $key): Promise
    {
        return $this->cache->delete($key);
    }
}
