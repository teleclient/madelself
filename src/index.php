#!/usr/bin/env php
<?php declare(strict_types=1);
/* MadelineProto UserBot Starter By:Esfandyar Shayan @WebWarp */

\set_include_path(\get_include_path().PATH_SEPARATOR.dirname(__DIR__, 1));

//date_default_timezone_set('Asia/Tehran');
ini_set('memory_limit', '2048M');
error_reporting(E_ALL);                              // always TRUE
ini_set('ignore_repeated_errors', '1');              // always TRUE
ini_set('display_errors',         '1');              // FALSE only in production or real server
ini_set('log_errors',             '1');              // Error logging engine
ini_set('error_log',              'php_errors.log'); // Logging file path
if (file_exists('php_errors.log')) {unlink('php_errors.log');}

if (!\file_exists(dirname(__DIR__, 1).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php')) {
    if (!\file_exists('madeline.php')) {
        \copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
    }
    define('MADELINE_BRANCH', '');
    require 'madeline.php';
} else {
    require_once 'vendor/autoload.php';
}
require_once 'plugins.php';

if (!file_exists('config.php')) {
    $config = '<?php' . PHP_EOL .
    '$GLOBALS["SELF_ID"]   = 157887279;' . PHP_EOL .
    '$GLOBALS["TEST_MODE"] = TRUE;' . PHP_EOL .
    '$GLOBALS["API_ID"]    = 6;' . PHP_EOL .
    '$GLOBALS["API_HASH"]  = "eb06d4abfb49dc3eeb1aeb98ae0f581e";' . PHP_EOL .
    PHP_EOL;
    var_export($config);
    file_put_contents('config.php', $config);
}
require_once 'config.php';

if (!file_exists('bot.lock')) {
    touch('bot.lock');
}
$lock = fopen('bot.lock', 'r+');


class EventHandler extends \danog\MadelineProto\EventHandler
{
    private $selfId;
    private $plugins;
    private $MadelineProto;

    public function __construct(object $MadelineProto)
    {
        parent::__construct($MadelineProto);
        $this->MadelineProto = $MadelineProto;
        $this->plugins = new Plugins();
        $this->selfId  = intval($GLOBALS['SELF_ID']);
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
        //if (isset($update['message']['_']) === 'message') {
            yield $this->plugins->process($this, $this->selfId, $update);
        //}
    }
}


$try = 1;
$locked = false;
while (!$locked) {
    $locked = flock($lock, LOCK_EX | LOCK_NB);
    if (!$locked) {
        closeConnection();
        if ($try++ >= 30) {
            \danog\MadelineProto\Logger::log('Another copy of the script is executing. Exited');
            exit;
        }
        sleep(1);
    }
}

//\danog\MadelineProto\Shutdown::addCallback(static function () use ($lock) {
//    flock($lock, LOCK_UN);
//    fclose($lock);
//});

if (file_exists('MadelineProto.log')) {unlink('MadelineProto.log');}
$settings['logger']['logger_level'] = \danog\MadelineProto\Logger::FATAL_ERROR;
$settings['logger']['logger']       = \danog\MadelineProto\Logger::FILE_LOGGER;
$settings['app_info']['api_id']     = $GLOBALS['API_ID'];
$settings['app_info']['api_hash']   = $GLOBALS['API_HASH'];
$settings['serialization']['cleanup_before_serialization'] = true;

$MadelineProto = new \danog\MadelineProto\API('session.madeline', $settings);
$MadelineProto->async(true);

$MadelineProto->loop(function () use ($MadelineProto) {
    yield $MadelineProto->start();
    yield $MadelineProto->setEventHandler('\EventHandler');
});

$MadelineProto->loop();
