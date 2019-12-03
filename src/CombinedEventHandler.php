<?php declare(strict_types=1);

namespace teleclient\madelbase;

require_once 'Store.php';

require_once 'plugins/verifyplugin.php';
require_once 'plugins/pingplugin.php';

use teleclient\madelbase\plugin;

class CombinedEventHandler extends \danog\MadelineProto\CombinedEventHandler
{
    public $verifyPlugin;
    public   $pingPlugin;

    public function __construct($CombinedMadelineProto)
    {
        parent::__construct($CombinedMadelineProto);
        $this->verifyPlugin = new \teleclient\madelbase\plugin\VerifyPlugin($CombinedMadelineProto);
        $this->pingPlugin   = new \teleclient\madelbase\plugin\PingPlugin  ($CombinedMadelineProto);
    }

    public function __magic_sleep() {
        return [];
    }
    public function __wakeup()
    {
    }

    public function onUpdateEditChannelMessage($update, $session)
    {
        yield $this->onUpdateNewMessage($update, $session);
    }
    public function onUpdateNewChannelMessage($update, $session)
    {
        yield $this->onUpdateNewMessage($update, $session);
    }
    public function onUpdateNewMessage($update, $session)
    {
        if (isset($update['message']['_']) && $update['message']['_'] === 'message') {
            yield $this->verifyPlugin->process($update, $session);
            yield   $this->pingPlugin->process($update, $session);
        }
    }
}
