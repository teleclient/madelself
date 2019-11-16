<?php

include 'plugins/verifyplugin.php';
include 'plugins/pingplugin.php';

class Plugins {

    private $verifyPlugin;
    private   $pingPlugin;

    public function __construct()
    {
        $this->verifyPlugin = new VerifyPlugin();
        $this->pingPlugin = new   PingPlugin();
    }

    public function process($MadelineProto, $selfId, $update)
    {
        yield $this->verifyPlugin->process($MadelineProto, $selfId, $update);
        yield $this->pingPlugin->process($MadelineProto, $selfId, $update);
    }

}