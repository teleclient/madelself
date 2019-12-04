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
    '$GLOBALS["BOT_TOKEN"] = "1234567890:ABCDEFGH-WhweDNtcNckxt7RyFo-dXrFLw";' . PHP_EOL;
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
$BotSettings['logger']['logger_level'] = \danog\MadelineProto\Logger::WARNING;
$botSettings['logger']['logger']       = \danog\MadelineProto\Logger::FILE_LOGGER;
$botSettings['serialization']['cleanup_before_serialization'] = true;
$botSettings['serialization']['serialization_interval']       = 60;
$botSettings['app_info']['api_id']   = $GLOBALS['API_ID'];
$botSettings['app_info']['api_hash'] = $GLOBALS['API_HASH'];
$token = $GLOBALS['BOT_TOKEN'];
$userSettings = $botSettings;

$CombinedMadelineProto = new \danog\MadelineProto\CombinedAPI('combined_session.madeline', [
    'bot.madeline' =>  $botSettings,
    'user.madeline' => $userSettings
]);

$CombinedMadelineProto->async(true);
$CombinedMadelineProto->serialization_interval = 60;
$CombinedMadelineProto->loop(function() use($CombinedMadelineProto, $token) {

    $botSession = 'bot.madeline';
    $botAuthorization = yield $CombinedMadelineProto->instances[$botSession]->bot_login($token);
    \danog\MadelineProto\Logger::log('Bot login:', \danog\MadelineProto\Logger::WARNING);
    $promises[] = $CombinedMadelineProto->instances[$botSession]->start();

    $userSession = 'user.madeline';
    \danog\MadelineProto\Logger::log('User login:', \danog\MadelineProto\Logger::WARNING);
    $promises[] = $CombinedMadelineProto->instances[$userSession]->start();

    yield $CombinedMadelineProto->all($promises);
    yield $CombinedMadelineProto->setEventHandler("\\teleclient\\madelbase\\CombinedEventHandler");

    //$json = json_encode($botAuthorization, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    //$json = ($json !== '')? $json : var_export($botAuthorization, true);
    //yield $CombinedMadelineProto->instances[$botSession]->echo($json.PHP_EOL);

    $bot = yield $CombinedMadelineProto->instances[$botSession]->get_self();
    $json = json_encode($bot, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $json = ($json !== '')? $json : var_export($bot, true);
    yield $CombinedMadelineProto->instances[$botSession]->echo($json.PHP_EOL);

    $user = yield $CombinedMadelineProto->instances[$userSession]->get_self();
    $json = json_encode($user, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $json = ($json !== '')? $json : var_export($user, true);
    yield $CombinedMadelineProto->instances[$userSession]->echo($json.PHP_EOL);
});
$CombinedMadelineProto->loop();
