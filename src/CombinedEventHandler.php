<?php declare(strict_types=1);

namespace teleclient\madelbase;

require_once 'Store.php';

require_once __DIR__ . "/plugins/VerifyPlugin.php";
require_once __DIR__ . "/plugins/PingPlugin.php";

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

    public function onAny($update, $session) {
        //if ($session === 'bot.madeline') {
        //    $res = json_encode($update, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        //    $res = ($res !== '')? $res : var_export($update, true);
        //    yield $this->{$session}->echo($res);
        //}
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
        //if ($session === 'bot.madeline') {
        //    $res = json_encode($update, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        //    $res = ($res !== '')? $res : var_export($update, true);
        //    yield $this->{$session}->echo($res.PHP_EOL);
        //}
        if (isset($update['message']['_']) && $update['message']['_'] === 'message') {
            yield $this->verifyPlugin->process($update, $session);
            yield   $this->pingPlugin->process($update, $session);
        }
    }
}
