<?php declare(strict_types=1);
/* MadelineProto UserBot Starter By:Esfandyar Shayan @WebWarp */

namespace teleclient\madelbase;

\set_include_path(\get_include_path().PATH_SEPARATOR.dirname(__DIR__, 1));

function registerSession($CombinedMadelineProto, string $session) {
    $self = yield $CombinedMadelineProto->instances[$session]->get_self();

    $isBot = isset($self['bot'])? $self['bot'] : null;
    yield $CombinedMadelineProto->instances[$session]->echo(($isBot? 'BOT' : 'USER')  .PHP_EOL);

    $dump = json_encode($self, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $dump = ($dump !== '')? $dump : var_export($self, true);
    yield $CombinedMadelineProto->instances[$session]->echo($dump.PHP_EOL);

    return $self;
}

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
\danog\MadelineProto\Shutdown::addCallback(static function () use ($msg) {
    echo($msg.PHP_EOL);
});

if (file_exists('MadelineProto.log')) {unlink('MadelineProto.log');}

function getSettings() {
    $settings['logger']['logger_level'] = \danog\MadelineProto\Logger::ULTRA_VERBOSE;
    $settings['logger']['logger']       = \danog\MadelineProto\Logger::FILE_LOGGER;
    $settings['serialization']['serialization_interval'] = 60;
    $settings['app_info']['api_id']   = $GLOBALS['API_ID'];
    $settings['app_info']['api_hash'] = $GLOBALS['API_HASH'];
    //$settings['authorization']['rsa_keys'] = [
    //    "-----BEGIN RSA PUBLIC KEY-----\nMIIBCgKCAQEAwVACPi9w23mF3tBkdZz+zwrzKOaaQdr01vAbU4E1pvkfj4sqDsm6\nlyDONS789sVoD/xCS9Y0hkkC3gtL1tSfTlgCMOOul9lcixlEKzwKENj1Yz/s7daS\nan9tqw3bfUV/nqgbhGX81v/+7RFAEd+RwFnK7a+XYl9sluzHRyVVaTTveB2GazTw\nEfzk2DWgkBluml8OREmvfraX3bkHZJTKX4EQSjBbbdJ2ZXIsRrYOXfaA+xayEGB+\n8hdlLmAjbCVfaigxX0CDqWeR1yFL9kwd9P0NsZRPsmoqVwMbMu7mStFai6aIhc3n\nSlv8kg9qv1m6XHVQY3PnEw+QQtqSIXklHwIDAQAB\n-----END RSA PUBLIC KEY-----",
    //    "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAruw2yP/BCcsJliRoW5eB\nVBVle9dtjJw+OYED160Wybum9SXtBBLXriwt4rROd9csv0t0OHCaTmRqBcQ0J8fx\nhN6/cpR1GWgOZRUAiQxoMnlt0R93LCX/j1dnVa/gVbCjdSxpbrfY2g2L4frzjJvd\nl84Kd9ORYjDEAyFnEA7dD556OptgLQQ2e2iVNq8NZLYTzLp5YpOdO1doK+ttrltg\ngTCy5SrKeLoCPPbOgGsdxJxyz5KKcZnSLj16yE5HvJQn0CNpRdENvRUXe6tBP78O\n39oJ8BTHp9oIjd6XWXAsp2CvK45Ol8wFXGF710w9lwCGNbmNxNYhtIkdqfsEcwR5\nJwIDAQAB\n-----END PUBLIC KEY-----",
    //    "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvfLHfYH2r9R70w8prHbl\nWt/nDkh+XkgpflqQVcnAfSuTtO05lNPspQmL8Y2XjVT4t8cT6xAkdgfmmvnvRPOO\nKPi0OfJXoRVylFzAQG/j83u5K3kRLbae7fLccVhKZhY46lvsueI1hQdLgNV9n1cQ\n3TDS2pQOCtovG4eDl9wacrXOJTG2990VjgnIKNA0UMoP+KF03qzryqIt3oTvZq03\nDyWdGK+AZjgBLaDKSnC6qD2cFY81UryRWOab8zKkWAnhw2kFpcqhI0jdV5QaSCEx\nvnsjVaX0Y1N0870931/5Jb9ICe4nweZ9kSDF/gip3kWLG0o8XQpChDfyvsqB9OLV\n/wIDAQAB\n-----END PUBLIC KEY-----",
    //    "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAs/ditzm+mPND6xkhzwFI\nz6J/968CtkcSE/7Z2qAJiXbmZ3UDJPGrzqTDHkO30R8VeRM/Kz2f4nR05GIFiITl\n4bEjvpy7xqRDspJcCFIOcyXm8abVDhF+th6knSU0yLtNKuQVP6voMrnt9MV1X92L\nGZQLgdHZbPQz0Z5qIpaKhdyA8DEvWWvSUwwc+yi1/gGaybwlzZwqXYoPOhwMebzK\nUk0xW14htcJrRrq+PXXQbRzTMynseCoPIoke0dtCodbA3qQxQovE16q9zz4Otv2k\n4j63cz53J+mhkVWAeWxVGI0lltJmWtEYK6er8VqqWot3nqmWMXogrgRLggv/Nbbo\noQIDAQAB\n-----END PUBLIC KEY-----",
    //    "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvmpxVY7ld/8DAjz6F6q0\n5shjg8/4p6047bn6/m8yPy1RBsvIyvuDuGnP/RzPEhzXQ9UJ5Ynmh2XJZgHoE9xb\nnfxL5BXHplJhMtADXKM9bWB11PU1Eioc3+AXBB8QiNFBn2XI5UkO5hPhbb9mJpjA\n9Uhw8EdfqJP8QetVsI/xrCEbwEXe0xvifRLJbY08/Gp66KpQvy7g8w7VB8wlgePe\nxW3pT13Ap6vuC+mQuJPyiHvSxjEKHgqePji9NP3tJUFQjcECqcm0yV7/2d0t/pbC\nm+ZH1sadZspQCEPPrtbkQBlvHb4OLiIWPGHKSMeRFvp3IWcmdJqXahxLCUS1Eh6M\nAQIDAQAB\n-----END PUBLIC KEY-----",
    //];
    $settings['connection']['main']['ipv4'][2]['ip_address'] = '149.154.167.50';
    $settings['connection']['test']['ipv4'][2]['ip_address'] = '149.154.167.40';

    $settings['app_info']['device_model']   = 'auto-detected';
    $settings['app_info']['system_version'] = 'auto-detected';
    $settings['app_info']['app_version']    = 'Unicorn';
    $settings['app_info']['lang_code']      = 'auto-detected';

    return $settings;
}

$botSettings = getSettings();
$token = $GLOBALS['BOT_TOKEN'];
$botSettings['app_info']['bot_auth_token'] = $GLOBALS['BOT_TOKEN'];
$userSettings = getSettings();

$CombinedMadelineProto = new \danog\MadelineProto\CombinedAPI('combined_session.madeline', [
     'bot.madeline' =>  $botSettings,
    'user.madeline' => $userSettings,
]);

$CombinedMadelineProto->settings['logger']['logger_level'] = \danog\MadelineProto\Logger::FATAL_ERROR;
$CombinedMadelineProto->settings['logger']['logger']       = \danog\MadelineProto\Logger::FILE_LOGGER;
$CombinedMadelineProto->settings['serialization']['serialization_interval'] = 600;
//$CombinedMadelineProto->{'user.madeline'}->settings['connection']['main']['ipv4'][2]['ip_address'] = '149.154.167.50';

$CombinedMadelineProto->async(true);

$CombinedMadelineProto->loop(function() use($CombinedMadelineProto, $token) {

    $botSession = 'bot.madeline';
    //$token = $CombinedMadelineProto->{$botSession}->settings['app_info']['bot_auth_token']?? null;
    if ($token) {
        yield $CombinedMadelineProto->instances[$botSession]->botLogin($token);
    }
    $promises[] = $CombinedMadelineProto->instances[$botSession]->start();

    $userSession = 'user.madeline';
    $promises[] = $CombinedMadelineProto->instances[$userSession]->start();

    yield $CombinedMadelineProto->all($promises);
    yield $CombinedMadelineProto->setEventHandler("\\teleclient\\madelbase\\CombinedEventHandler");
    yield registerSession($CombinedMadelineProto,  $botSession);
    yield registerSession($CombinedMadelineProto, $userSession);
});
$CombinedMadelineProto->loop();
