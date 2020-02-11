<?php  declare(strict_types=1);

/*
require_once 'madeline.php';
$MadelineProto = new \danog\MadelineProto\CombinedAPI('combined.madeline');
$CombinedMadelineProto->async(true);
$CombinedMadelineProto->setEventHandler("\\teleclient\\madelbase\\CombinedEventHandler");
$MadelineProto.logout();
*/

if(\file_exists('user.madeline')) {
    unlink('user.madeline');
}
if(\file_exists('bot.madeline')) {
    unlink('bot.madeline');
}
if(\file_exists('combined.madeline')) {
    unlink('combined.madeline');
}

if(\file_exists('user.madeline.lock')) {
    unlink('user.madeline.lock');
}
if(\file_exists('bot.madeline.lock')) {
    unlink('bot.madeline.lock');
}
if(\file_exists('combined.madeline.lock')) {
    unlink('combined.madeline.lock');
}

if(\file_exists('madeline.php')) {
    unlink('madeline.php');
}
if(\file_exists('madeline.phar')) {
    unlink('madeline.phar');
}
if(\file_exists('madeline.phar.version')) {
    unlink('madeline.phar.version');
}
if(\file_exists('MadelineProto.log')) {
    unlink('MadelineProto.log');
}

die;
