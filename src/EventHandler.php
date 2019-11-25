<?php declare(strict_types=1);

//require_once '../vendor/autoload.php';
require_once 'Store.php';

require_once 'plugins/verifyplugin.php';
require_once 'plugins/pingplugin.php';


class EventHandler extends \danog\MadelineProto\EventHandler
{
    protected $verifyPlugin;
    protected   $pingPlugin;

    public function __construct($MadelineProto)
    {
        parent::__construct($MadelineProto);
        //$selfId = $MadelineProto->__get('self_id')[0];
        $this->verifyPlugin = (new VerifyPlugin($MadelineProto));
        $this->pingPlugin   = (new   PingPlugin($MadelineProto));
    }

    public function onUpdateEditChannelMessage($update)
    {
        yield $this->onUpdateNewMessage($update);
    }
    public function onUpdateNewChannelMessage($update)
    {
        yield $this->onUpdateNewMessage($update);
    }
    public function onUpdateNewMessage($update)
    {
        if (isset($update['message']['_']) && $update['message']['_'] === 'message') {
            yield $this->verifyPlugin->process($update);
            yield   $this->pingPlugin->process($update);
        }
    }
}
