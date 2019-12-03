<?php declare(strict_types=1);
/* MadelineProto UserBot Starter By:Esfandyar Shayan @WebWarp */

namespace teleclient\madelbase;

\set_include_path(\get_include_path().PATH_SEPARATOR.dirname(__DIR__, 1));

date_default_timezone_set('Asia/Tehran');
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
require_once 'CombinedEventHandler.php';

if (!file_exists('config.php')) {
    $config = '<?php'               . PHP_EOL .
    '$GLOBALS["TEST_MODE"] = TRUE;' . PHP_EOL .
    '$GLOBALS["API_ID"]    = 6;'    . PHP_EOL .
    '$GLOBALS["API_HASH"]  = "eb06d4abfb49dc3eeb1aeb98ae0f581e";' . PHP_EOL .
    PHP_EOL;
    var_export($config);
    file_put_contents('config.php', $config);
}
require_once 'config.php';

if (!file_exists('cache') || !is_dir('cache')) {
    mkdir('cache');
}

$pid = getmypid();
echo ($pid.PHP_EOL);

$msg = "Done";
\danog\MadelineProto\Shutdown::addCallback(function () use ($msg) {
    echo($msg.PHP_EOL);
});

if (file_exists('MadelineProto.log')) {unlink('MadelineProto.log');}
$BotSettings['logger']['logger_level'] = \danog\MadelineProto\Logger::FATAL_ERROR;
$BotSettings['logger']['logger']       = \danog\MadelineProto\Logger::FILE_LOGGER;
$BotSettings['serialization']['cleanup_before_serialization'] = true;
$BotSettings['serialization']['serialization_interval']       = 60;
$UserSettings = $BotSettings;
$UserSettings['app_info']['api_id']   = $GLOBALS['API_ID'];
$UserSettings['app_info']['api_hash'] = $GLOBALS['API_HASH'];

$CombinedMadelineProto = new \danog\MadelineProto\CombinedAPI('combined_session.madeline',  [
     'bot.madeline' =>  $BotSettings,
    'user.madeline' => $UserSettings
]);
$CombinedMadelineProto->async(true);
$CombinedMadelineProto->loop(function() use($CombinedMadelineProto) {
    \danog\MadelineProto\Logger::log('Bot  login', \danog\MadelineProto\Logger::WARNING);
    $res[] = $CombinedMadelineProto->instances['bot.madeline']->start();
    \danog\MadelineProto\Logger::log('User login', \danog\MadelineProto\Logger::WARNING);
    $res[] = $CombinedMadelineProto->instances['user.madeline']->start();
    yield $CombinedMadelineProto->all($res);
    yield $CombinedMadelineProto->setEventHandler("\\teleclient\\madelbase\\CombinedEventHandler");
});
$CombinedMadelineProto->loop();
