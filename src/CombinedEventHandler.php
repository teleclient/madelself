<?php declare(strict_types=1);

namespace teleclient\madelbase;

require_once 'Store.php';

require_once __DIR__ . "/plugins/VerifyPlugin.php";
require_once __DIR__ . "/plugins/PingPlugin.php";
require_once __DIR__ . "/plugins/DownPlugin.php";
require_once __DIR__ . "/plugins/TimePlugin.php";

use teleclient\madelbase\plugins\VerifyPlugin;
use teleclient\madelbase\plugins\PingPlugin;
use teleclient\madelbase\plugins\DownPlugin;
use teleclient\madelbase\plugins\TimePlugin;

class CombinedEventHandler extends \danog\MadelineProto\CombinedEventHandler
{
    public static $self;

    public $verifyPlugin;
    public   $pingPlugin;
    public   $downPlugin;
    public   $timePlugin;

    public function __construct($CombinedMadelineProto)
    {
        parent::__construct($CombinedMadelineProto);
        $this->verifyPlugin = new VerifyPlugin($CombinedMadelineProto);
        $this->pingPlugin   = new   PingPlugin($CombinedMadelineProto);
        $this->downPlugin   = new   DownPlugin($CombinedMadelineProto);
        $this->timePlugin   = new   TimePlugin($CombinedMadelineProto, self::$self);
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
        if ($session === 'bot.madeline') {
            $res = json_encode($update, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            $res = ($res !== '')? $res : var_export($update, true);
            yield $this->{$session}->echo($res.PHP_EOL);
        }
        if (isset($update['message']['_']) && $update['message']['_'] === 'message') {
            $MadelineProto = $this->{$session};
            $processed = yield $this->processScriptCommands($MadelineProto, $update);

            yield $this->verifyPlugin->process($MadelineProto, $update);
            if($processed) {
                return;
            }
            yield $this->pingPlugin->process($MadelineProto, $update);
            if($processed) {
                return;
            }
            yield $this->downPlugin->process($MadelineProto, $update);
            if($processed) {
                return;
            }
            yield $this->timePlugin->process($MadelineProto, $update);
        }
    }

    /*
    Usage: "script exit"   To stop the script.
           "script logout" To log out of the session
           The commands must be issued by the owner of the userbot.
    */
    private function processScriptCommands($MadelineProto, $update) {
        if(isset($update['message']['out'])) {
            $msg = $update['message']['message']? trim($update['message']['message']) : null;
            if($msg && strlen($msg) >= 7 && strtolower(substr($msg, 0, 7)) === 'script ') {
                $param = strtolower(trim(substr($msg, 6)));
                switch($param) {
                    case 'logout':
                        $MadelineProto->logout();
                        echo('Successfully logged out.'.PHP_EOL);
                    case 'exit':
                        echo('Robot is stopped.'.PHP_EOL);
                        \danog\MadelineProto\Shutdown::addCallback(function () {}, 1);
                        exit();
                }
            }
        }
        return false;
    }
}
