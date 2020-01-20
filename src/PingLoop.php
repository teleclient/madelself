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
    /**
     * Connection instance.
     *
     * @var \danog\MadelineProto\Connection
     */
    //protected $connection;

    /**
     * DC ID.
     *
     * @var string
     */
    //protected $datacenter;

    /**
     * DataCenterConnection instance.
     *
     * @var \danog\MadelineProto\DataCenterConnection
     */
    //protected $datacenterConnection;

    protected $lastTime;
    protected $MadelineProto;

    public function __construct($API, String $url, int $timeout/*Connection $connection*/)
    {
        //$this->connection = $connection;
        //$this->API        = $connection->getExtra();
        //$this->datacenter = $connection->getDatacenterID();
        //$this->datacenterConnection = $connection->getShared();
        $this->API      = $API;
        $this->url      = $url;
        $this->timeout  = $timeout;
        $this->lastTime = 0;
    }

    public function loop()
    {
        $API        = $this->API;
        //$datacenter = $this->datacenter;
        //$connection = $this->connection;
        //$shared     = $this->datacenterConnection;

        /*yield $API->*/echo("Ping hello!".PHP_EOL);

        $url     = $this->url;
        $timeout = $this->timeout; // $shared->getSettings()['timeout'];
        while (true) {
            //while (!$shared->hasTempAuthKey()) {
                // /*yield $API->*/echo("Ping while started".PHP_EOL);;
                //$pause = $this->pause();
                //$promise = yield $this->waitSignal($pause);  // hangs here
                //if ($promise) {
                //    /*yield $API->*/echo("Ping returned 1".PHP_EOL);
                //    return;
                //}
            //}
            /*yield $API->*/echo("Ping wait started".PHP_EOL);
            $promise = yield $this->waitSignal($this->pause($timeout));
            if ($promise) {
                /*yield $API->*/echo("Ping returned 2".PHP_EOL);
                return;
            }
            /*yield $API->*/echo("Ping wait ended".PHP_EOL);
            if (\time() - $this->lastTime >= $timeout) {
                $API->logger->logger("Ping $url");
                /*yield $API->*/echo("Ping $url".PHP_EOL);
                try {
                    //yield $connection->methodCallAsyncRead('ping', ['ping_id' => \random_bytes(8)]);
                    /*yield $API->*/echo("Pinged!".PHP_EOL);
                    $this->lastTime = \time();
                } catch (\Throwable $e) {
                    $API->logger->logger("Error while pinging $url");
                    /*yield $API->*/echo("Error while pinging $url".PHP_EOL);
                    $API->logger->logger((string) $e);
                    $err = (string) $e;
                    /*yield $API->*/echo($err.PHP_EOL);
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
