<?php

require_once 'LocalKeyedMutex.php';
require_once 'FileCache.php';

use Amp\Promise;
use Amp\Cache\FileCache;
use Amp\Sync\LocalKeyedMutex;

class Store
{
    private static $instance;

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            $directory = 'cache';
            $mutex     = yield new LocalKeyedMutex();
            $cache     = yield new FileCache($directory, $mutex);
            self::$instance = yield new self($cache);
        }
        return self::$instance;
    }

    private $cache;

    private function __construct($cache)
    {
        $this->cache = $cache;
    }
    public function __destruct()
    {
            yield $this->cache->__destruct();
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
