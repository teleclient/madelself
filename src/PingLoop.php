<?php

//namespace danog\MadelineProto\Loop\Connection;
namespace teleclient\madelbase;

//use danog\MadelineProto\Connection;
use danog\MadelineProto\Loop\Impl\ResumableSignalLoop;
use danog\MadelineProto\API;

/**
 * Ping loop.
 */
class PingLoop extends ResumableSignalLoop
{
    protected $lastTime;
    protected $MadelineProto;

    public function __construct($API, String $url, int $timeout)
    {
        $this->API      = $API;
        $this->url      = $url;
        $this->timeout  = $timeout;
        $this->lastTime = 0;
    }

    public function loop()
    {
        $API     = $this->API;
        $url     = $this->url;
        $timeout = $this->timeout;
        while (true) {
            $promise = yield $this->waitSignal($this->pause($timeout));
            if ($promise) {
                return;
            }
            if (\time() - $this->lastTime >= $timeout) {
                try {
                  //yield $connection->methodCallAsyncRead('ping', ['ping_id' => \random_bytes(8)]);
                    $API->logger->logger("Pinging $url");
                    //yield $API->echo("Pinging $url!".PHP_EOL);
                    $this->lastTime = \time();
                } catch (\Throwable $e) {
                    $API->logger->logger("Error while pinging $url");
                    yield $API->echo("Error while pinging $url".PHP_EOL);
                    $API->logger->logger((string) $e);
                    $err = (string) $e;
                    yield $API->echo($err.PHP_EOL);
                }
            }
        }
    }

    public function __toString(): string
    {
        //return "Ping loop in DC {$this->datacenter}";
        return "Ping loop";
    }
}
