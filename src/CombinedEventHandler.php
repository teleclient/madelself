<?php declare(strict_types=1);

namespace teleclient\madelbase;

require_once 'Store.php';

require_once __DIR__ . "/plugins/VerifyPlugin.php";
require_once __DIR__ . "/plugins/PingPlugin.php";
require_once __DIR__ . "/plugins/DownPlugin.php";
require_once __DIR__ . "/plugins/TimePlugin.php";

use \teleclient\madelbase\Store;
use teleclient\madelbase\plugins\VerifyPlugin;
use teleclient\madelbase\plugins\PingPlugin;
use teleclient\madelbase\plugins\DownPlugin;
use teleclient\madelbase\plugins\TimePlugin;

class CombinedEventHandler extends \danog\MadelineProto\CombinedEventHandler
{
    public static $userSelf;
    public static  $botSelf;

    public $CombinedMadelineProto;

    public $verifyPlugin;
    public   $pingPlugin;
    public   $downPlugin;
    public   $timePlugin;

    public function __construct($CombinedMadelineProto)
    {
        parent::__construct($CombinedMadelineProto);
        $this->CombinedMadelineProto = $CombinedMadelineProto;
        $this->verifyPlugin = new VerifyPlugin($CombinedMadelineProto);
        $this->pingPlugin   = new   PingPlugin($CombinedMadelineProto);
        $this->downPlugin   = new   DownPlugin($CombinedMadelineProto);
        $this->timePlugin   = new   TimePlugin($CombinedMadelineProto, self::$userSelf);
    }

    public function __magic_sleep() {
        return [];
    }
    public function __wakeup()
    {
    }

    public function report(string $message)
    {
        try {
            $this->messages->sendMessage(['peer' => $this->userSelf['id'], 'message' => $message]);
        } catch (\Throwable $e) {
            $this->logger("While reporting: $e", Logger::FATAL_ERROR);
        }
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
        if ($update['message']['_'] === 'messageEmpty') {
            return;
        }
        if ($session === 'bot.madeline') {
            $res = json_encode($update, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            $res = ($res !== '')? $res : var_export($update, true);
            yield $this->{$session}->echo($res.PHP_EOL);
        }
        if (isset($update['message']['_']) && $update['message']['_'] === 'message') {
            $MadelineProto = $this->{$session};
            $processed = yield $this->processScriptCommands($update);

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
    Usage: "script exit"     To stop the script.
           "script logout"   To log out of the session.
           "script echo on"  To start printing out debugging info.
           "script echo off" To start printing out debugging info.
           "script status"   To show the status of the echo feature.

           The commands must be issued by the owner of the userbot.
    */
    private function processScriptCommands($update) {
        if(isset($update['message']['out'])) {
            $msg = $update['message']['message']? trim($update['message']['message']) : null;
            if($msg && strlen($msg) >= 7 && strtolower(substr($msg, 0, 7)) === 'script ') {
                $param = strtolower(trim(substr($msg, 6)));
                switch($param) {
                    case 'help':
                        //;
                        break;
                    case 'status':
                        $store   = yield Store::getInstance();
                        $fetched = yield $store->get('script.echo');
                        break;
                    case 'logout':
                        foreach($this->CombinedMadelineProto->instances as $session => $MadelineProto) {
                            $MadelineProto->logout();
                        }
                        echo('Successfully logged out by owner.'.PHP_EOL);
                    case 'exit':
                        echo('Robot is stopped by owner.'.PHP_EOL);
                        \danog\MadelineProto\Magic::shutdown();
                    default:
                        if(substr($param, 0, 5) === 'echo ') {
                            $subparam = trim(substr($param, 0, 6));
                            if($subparam === 'on' || $subparam === 'off') {
                                $store = yield Store::getInstance();
                                yield $store->set('script.echo', $subparam);
                            }
                        }
                        break;
                }
            }
        }
        return false;
    }

    private function getHelpText() {
        return
        "<b>Download Module Instructions:</b><br>" .
        "<br>" .
        "<b>down help</b>      Shows this help text.<br>" .
        "<b>screen status</b>  Shows the status of the<br>" .
        " &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp; echo feature.<br>" .
        "<b>screen echo on</b>   To turn on the echo feature.<br>" .
        "<b>screen echo off</b>  To turn off the echo feature.<br>" .
        "<b>scree ttl off</b>   Screen messages will not be<br>" .
        " &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; deleted.<br>" .
        "<b>screen ttl 47</b>   Screen messages will be<br>" .
        " &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; deleted after 47 seconds.<br>" .
        "<b>screen logout</b>   To log out of the session.<br>" .
        "<b>screen exit</b>     To stop the script execusion.<br>" .
        "<b>script status</b>   To show the status of the echo feature.";
    }
}
