<?php declare(strict_types=1);

namespace teleclient\madelbase\plugins;

require_once(dirname(__DIR__, 1).'/Upd.php');
require_once(dirname(__DIR__, 1).'/TimeStore.php');

use \teleclient\madelbase\Upd;
use \teleclient\madelbase\TimeStore;

class TimePlugin {

    private $CombinedMadelineProto;
    private $self;

    public function __construct($CombinedMadelineProto, $self)
    {
        $this->CombinedMadelineProto = $CombinedMadelineProto;
        $this->self = $self;
    }

    public function process($MadelineProto, $update)
    {
        //$MadelineProto = $this->CombinedMadelineProto->instances[$session];
        $session = '';

        $processed     = false;
        $msg = Upd::getMsgText($update);
        if (strncasecmp($session, 'bot', 3) !== 0 && $msg && strncasecmp($msg, 'time', 4) === 0) {
            $MadelineProto->echo("Message: '" . $msg . "'" . PHP_EOL);

            $processed = true;
            $msgId     = Upd::getMsgId($update);
            $msgIsOut  = Upd::isMsgOutward($update);
            $peerId    = Upd::getToId($update);
            $ttl       = yield TimeStore::timeTtl();
            $msgEnd    = strtolower(trim(substr($msg, 4)));
            if ($msgEnd === '') {
                $status = yield TimeStore::timeStatus();
                if ($status === 'on') {
                    $time = date('H:i');
                    yield $this->replyMsg($MadelineProto, $peerId, $msgId, 'Time is '.$time);
                    $msgs = $msgIsOut? [$msgId, $msgId + 1] : [$msgId];
                    yield $this->deleteMsgs($MadelineProto, $peerId, $msgs, $ttl);
                }
            }
            elseif ($msgIsOut)
            {
                if ($msgEnd === 'help') {
                    yield $this->editMsg   ($MadelineProto, $peerId,  $msgId, $this->getHelpText());
                    yield $this->deleteMsgs($MadelineProto, $peerId, [$msgId], $ttl);
                } else {
                    switch ($msgEnd) {
                    case 'on':
                        yield TimeStore::timeStatus('on');
                        break;
                    case 'off':
                        yield TimeStore::timeStatus('off');
                        break;
                    case 'status':
                        break;
                    default:
                        $space = strpos($msgEnd, ' ');
                        $token1 = ($space === false)? ''      : trim(substr($msgEnd, 0, $space));
                        $rest   = ($space === false)? $msgEnd : trim(substr($msgEnd,    $space));
                        echo("token:'$token1'".PHP_EOL);
                        echo(" rest:'$rest'".PHP_EOL);
                        if ($token1 === 'ttl' && ($rest === 'off' || ctype_digit($rest))) {
                            $res = yield TimeStore::timeTtl($rest);
                            if($res === null) {
                                $processed = false;
                            }
                        }
                        elseif ('photo' === $token1 && ctype_digit($rest)) {
                            if(is_writable('imgs')) {
                                $MadelineProto->echo('WRITABLE'.PHP_EOL);
                            } else {
                                $MadelineProto->echo('NOT WRITABLE'.PHP_EOL);
                            }
                            $index = intval($rest);
                            $photos = yield $MadelineProto->photos->getUserPhotos([
                                'user_id' => $this->self['id'],
                                'offset'  => $index,
                                'max_id'  => 0,
                                'limit'   => 1
                            ]);
                            $path = '';
                            if(sizeof($photos['photos'])) {
                                $path = yield $MadelineProto->downloadToFile(
                                    [
                                        '_'     => 'messageMediaPhoto',
                                        'photo' => $photos['photos'][0]
                                    ],
                                    'imgs/writingOverImage.jpg'
                                );
                            }
                            $MadelineProto->echo("Photo path:$path".PHP_EOL);
                        }
                        elseif ('size' === $token1 && ctype_digit($rest)) {
                            $res = yield TimeStore::timeSize($rest);
                            if($res === null) {
                                $processed = false;
                            }
                        }
                        elseif ('place' === $token1) {
                            $res = yield TimeStore::timePlace($rest);
                            if($res === null) {
                                $processed = false;
                            }
                        }
                        elseif ('color' === $token1) {
                            $res = yield TimeStore::timeColor($rest);
                            if($res === null) {
                                $processed = false;
                            }
                        }
                        else {
                            $processed = false;
                        }
                        break;
                    }
                    if ($processed) {
                        $text = yield TimeStore::getStatusText();
                        yield $this->editMsg   ($MadelineProto, $peerId,  $msgId,  $text);
                        yield $this->deleteMsgs($MadelineProto, $peerId, [$msgId], $ttl);
                    }
                }
            }
        }
        return $processed;
    }

    private function replyMsg($mp, $peerId, $msgId, $text)
    {
        try {
            $updates = yield $mp->messages->sendMessage([
                'peer'            => $peerId,
                'reply_to_msg_id' => $msgId,
                'message'         => $text,
                'parse_mode'      => 'HTML'
            ]);
            return $updates;
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            \danog\MadelineProto\Logger::log((string)$e, \danog\MadelineProto\Logger::FATAL_ERROR);
        } catch (\danog\MadelineProto\Exception $e) {
            \danog\MadelineProto\Logger::log((string)$e, \danog\MadelineProto\Logger::FATAL_ERROR);
        }
    }

    private function editMsg($mp, $peerId, $msgId, $text)
    {
        try {
            //echo('STATUS TRACE 4 ' . $text. PHP_EOL);
            yield $mp->messages->editMessage([
                'peer'       => $peerId,
                'id'         => $msgId,
                'message'    => $text,
                'parse_mode' => 'HTML'
            ]);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            \danog\MadelineProto\Logger::log((string)$e, \danog\MadelineProto\Logger::FATAL_ERROR);
        } catch (\danog\MadelineProto\Exception $e) {
            \danog\MadelineProto\Logger::log((string)$e, \danog\MadelineProto\Logger::FATAL_ERROR);
        }
    }

    private function deleteMsgs($mp, $peerId, $msgIds, $ttl) {
        if ($ttl !== 'off') {
            $ttl = intval($ttl);
            $mp->callFork((function () use ($mp, $peerId, $msgIds, $ttl) {
                yield $mp->sleep($ttl);
                try {
                        yield $mp->messages->deleteMessages([
                        'revoke' => true,
                        'peer'   => $peerId,
                        'id'     => $msgIds
                    ]);
                        yield $mp->channels->deleteMessages([
                        'channel' => $peerId,
                        'id'      => $msgIds
                    ]);
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    \danog\MadelineProto\Logger::log((string)$e, \danog\MadelineProto\Logger::FATAL_ERROR);
                } catch (\danog\MadelineProto\Exception $e) {
                    \danog\MadelineProto\Logger::log((string)$e, \danog\MadelineProto\Logger::FATAL_ERROR);
                }
            })());
        }
    }

    private function getHelpText() {
        $text =
        "<b>Time Module Instructions:</b><br>" .
        "<br>" .
        "<b>time help</b>     Shows this help text.<br>" .
        "<b>time status</b> Shows the status of the<br>" .
        " &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp; time module.<br>" .
        "<b>time on</b>        Turns on the time feature.<br>" .
        "<b>time off</b>       Turns off the time feature.<br>" .
        "<b>time ttl off</b>   Ping messages will not be<br>" .
        " &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;deleted.<br>" .
        "<b>time ttl 47</b>   Time messages will be<br>" .
        " &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;deleted after 47 seconds.<br>" .
        "<b>time photo 0</b> The Photo to be used as the<br>" .
        " &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; profile's photo.<br>" .
        "<b>time size 80</b> Time text font size.<br>" .
        "<b>time color 255 255 255</b> Time text color<br>" .
        " &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; components in RGB scheme.<br>" .
        "<b>time position center</b><br>" .
        "<b>time position top</b><br>" .
        "<b>time position bottom</b><br>" .
        "<b>time position left</b><br>" .
        "<b>time position bottom</b><br>" .
        " &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; Possible positions of the text<br>" . 
        " &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  &nbsp; &nbsp; &nbsp; &nbsp; on the photo.";
        return $text;
    }
}
