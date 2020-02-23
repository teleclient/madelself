<?php declare(strict_types=1);
/* MadelineProto UserBot Starter By:Esfandyar Shayan @WebWarp */

namespace teleclient\madelbase;

//date_default_timezone_set('Asia/Tehran');
date_default_timezone_set('America/Los_Angeles');

use \danog\MadelineProto\Logger;

\set_include_path(\get_include_path().PATH_SEPARATOR.dirname(__DIR__, 1));

ini_set('memory_limit', '2048M');
ignore_user_abort(true);
set_time_limit(0);
error_reporting(E_ALL);                              // always TRUE
ini_set('ignore_repeated_errors', '1');              // always TRUE
ini_set('display_startup_errors', '1');
ini_set('display_errors',         '1');              // FALSE only in production or real server
ini_set('log_errors',             '1');              // Error logging engine
ini_set('error_log',              'php_errors.log'); // Logging file path
if (file_exists('php_errors.log')) {unlink('php_errors.log');}

$composer = true;
if (!$composer || !\file_exists(dirname(__DIR__, 1).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php')) {
    if (!\file_exists('madeline.php')) {
        \copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
    }
    //define('MADELINE_BRANCH', '4.2.25');
    require 'madeline.php';
} else {
    require_once '../vendor/autoload.php';
}
require_once 'functions.php';
require_once 'Store.php';
require_once 'CombinedEventHandler.php';
require_once 'config.php';
require_once 'PingLoop.php';
require_once 'TimeLoop.php';

Logger::constructor(Logger::FILE_LOGGER, __DIR__ .'/MadelineProto.log');

if (!file_exists('cache') || !is_dir('cache')) {
    mkdir('cache');
}

$pid = getmypid();
//echo ($pid.PHP_EOL);

if (file_exists('MadelineProto.log')) {unlink('MadelineProto.log');}

function registerSession($MadelineProto, string $session, $self) {
    //$isBot = isset($self['bot'])? $self['bot'] : null;
    //$MadelineProto->instances[$session]->echo('Registered ' . ($isBot? 'BOT' : 'USER') . ' id:'. $self['id'] .
    //                       (isset($self['username'])? ('  name: '. $self['username']) : (' ')) .PHP_EOL);

    //$dump = json_encode($self, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    //$dump = ($dump !== '')? $dump : var_export($self, true);
    //$MadelineProto->instances[$session]->echo($dump.PHP_EOL);
}

function isBot($self) {
    return isset($self['bot'])? true : false;
}

function getSettings() {
    $settings = [];
    $settings['logger']['logger_level'] = Logger::ULTRA_VERBOSE;
    $settings['logger']['logger']       = Logger::FILE_LOGGER;
    $settings['app_info']['api_id']     = $GLOBALS["API_ID"];
    $settings['app_info']['api_hash']   = $GLOBALS["API_HASH"];
    $settings['connection']['main']['ipv4'][2]['ip_address'] = '149.154.167.50';
    $settings['connection']['test']['ipv4'][2]['ip_address'] = '149.154.167.40';

    $settings['app_info']['device_model']   = 'auto-detected';
    $settings['app_info']['system_version'] = 'auto-detected';
    $settings['app_info']['app_version']    = 'powerlap';
    $settings['app_info']['lang_code']      = 'auto-detected';

    return $settings;
}
$botSettings = getSettings();
$botToken = $GLOBALS['BOT_TOKEN'];
$botSettings['app_info']['bot_auth_token'] = $botToken;
$userSettings = getSettings();

$CombinedMadelineProto = new \danog\MadelineProto\CombinedAPI('combined.madeline', [
     'bot.madeline' =>  $botSettings,
    'user.madeline' => $userSettings,
]);
Logger::constructor(Logger::FILE_LOGGER, 'C://devphp/madelbase/src/MadelineProto.log');
$CombinedMadelineProto->settings['logger']['logger_level'] = \danog\MadelineProto\Logger::WARNING;
$CombinedMadelineProto->settings['logger']['logger']       = \danog\MadelineProto\Logger::FILE_LOGGER;
$CombinedMadelineProto->settings['logger']['logger_param'] = 'MadelineProto.log';
$CombinedMadelineProto->settings['logger']['param']        = 'MadelineProto.log';

$CombinedMadelineProto->async(true);

//while(true) {
    //try {
        $CombinedMadelineProto->loop(function() use($CombinedMadelineProto, $botToken) {
            $botSession = 'bot.madeline';
            if ($botToken) {yield $CombinedMadelineProto->instances[$botSession]->botLogin($botToken);}
            Logger::log('Bot login', \danog\MadelineProto\Logger::WARNING);
            CombinedEventHandler::$botSelf = yield $CombinedMadelineProto->instances[$botSession]->start();

            $userSession = 'user.madeline';
            Logger::log('Userbot login');
            CombinedEventHandler::$userSelf = yield $CombinedMadelineProto->instances[$userSession]->start();

            $CombinedMadelineProto->all([CombinedEventHandler::$botSelf ,CombinedEventHandler::$userSelf]);
        });
        $CombinedMadelineProto->setEventHandler("\\teleclient\\madelbase\\CombinedEventHandler");
        $combinedEventHandler = $CombinedMadelineProto->getEventHandler();
        if(!is_array(CombinedEventHandler::$botSelf) || !is_array(CombinedEventHandler::$userSelf)) {
            echo('Please re-start the script.'.PHP_EOL);
            \danog\MadelineProto\Magic::shutdown();
        }

        $pingLoop = new PingLoop($CombinedMadelineProto->instances['user.madeline'], 'url', 20);
        $CombinedMadelineProto->echo('Ping Loop started'.PHP_EOL);
        $pingLoop->start();

        $timeLoop = new TimeLoop($CombinedMadelineProto->instances['user.madeline'], CombinedEventHandler::$userSelf);
        $CombinedMadelineProto->echo('Time Loop started'.PHP_EOL);
        $timeLoop->start();

        $CombinedMadelineProto->echo('Update Loop started'.PHP_EOL);
        $CombinedMadelineProto->loop();
    //} catch (\Throwable $e) {
    //    try {
    //        $CombinedMadelineProto->logger("Surfaced: $e");
    //        $CombinedMadelineProto->getEventHandler(['async' => false])->report("Surfaced: $e");
    //    } catch (\Throwable $e) {
    //    }
    //}
//}

echo('End of Script'.PHP_EOL);
