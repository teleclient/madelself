#!/usr/bin/env php
<?php declare(strict_types=1);
/* MadelineProto UserBot Starter By:Esfandyar Shayan @WebWarp */

\set_include_path(\get_include_path().PATH_SEPARATOR.dirname(__DIR__, 1));

date_default_timezone_set('Asia/Tehran');
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
  //define('MADELINE_BRANCH', '4.2.25');
    require 'madeline.php';
} else {
    require_once '../vendor/autoload.php';
}
require_once 'Store.php';
require_once 'EventHandler.php';

if (!file_exists('config.php')) {
    $config = '<?php' . PHP_EOL .
    '$GLOBALS["TEST_MODE"] = TRUE;' . PHP_EOL .
    '$GLOBALS["API_ID"]    = 6;' . PHP_EOL .
    '$GLOBALS["API_HASH"]  = "eb06d4abfb49dc3eeb1aeb98ae0f581e";' . PHP_EOL .
    PHP_EOL;
    var_export($config);
    file_put_contents('config.php', $config);
}
require_once 'config.php';

$msg = "Done";
\danog\MadelineProto\Shutdown::addCallback(static function () use ($msg) {
    echo($msg.PHP_EOL);
});

if (file_exists('MadelineProto.log')) {unlink('MadelineProto.log');}
$settings['logger']['logger_level'] = \danog\MadelineProto\Logger::ULTRA_VERBOSE;
$settings['logger']['logger']       = \danog\MadelineProto\Logger::FILE_LOGGER;
$settings['app_info']['api_id']     = $GLOBALS['API_ID'];
$settings['app_info']['api_hash']   = $GLOBALS['API_HASH'];
$settings['serialization']['cleanup_before_serialization'] = true;

$MadelineProto = new \danog\MadelineProto\API('session.madeline', $settings);
$MadelineProto->async(true);
$MadelineProto->loop(function() use($MadelineProto) {
    yield $MadelineProto->start();
    yield Store::getInstance();
    $self   = yield $MadelineProto->get_self();
    yield $MadelineProto->__set('self_id', [$self['id']]);
    yield $MadelineProto->setEventHandler(EventHandler::class);
});
$MadelineProto->loop();
