<?php declare(strict_types=1);

namespace teleclient\madelbase\plugins;

require_once(dirname(__DIR__, 1).'/Upd.php');
require_once(dirname(__DIR__, 1).'/Store.php');

use \teleclient\madelbase\Upd;
use \teleclient\madelbase\Store;


class PingPlugin {

    private $CombinedMadelineProto;

    public function __construct($CombinedMadelineProto)
    {
        $this->CombinedMadelineProto = $CombinedMadelineProto;
    }

    public function process($MadelineProto, $update)
    {
        //$MadelineProto = $this->CombinedMadelineProto->instances[$session];
        $session = '';

        $processed     = false;
        $msg = Upd::getMsgText($update);
        if (strncasecmp($session, 'bot', 3) !== 0 && $msg && strncasecmp($msg, 'ping', 4) === 0) {
            $processed = true;
            $msgId     = Upd::getMsgId($update);
            $msgIsOut  = Upd::isMsgOutward($update);
            $peerId    = Upd::getToId($update);
            $ttl       = yield $this->pingTtl();
            $msgEnd    = strtolower(trim(substr($msg, 4)));
            if ($msgEnd === '') {
                $status = yield $this->pingStatus();
                if ($status === 'on') {
                    yield $this->replyMsg($MadelineProto, $peerId, $msgId, 'pong');
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
                        yield $this->pingStatus('on');
                        break;
                    case 'off':
                        yield $this->pingStatus('off');
                        break;
                    case 'status':
                        break;
                    default:
                        $space = strpos($msgEnd, ' ');
                        $token1 = ($space === false)? ''      : trim(substr($msgEnd, 0, $space));
                        $rest   = ($space === false)? $msgEnd : trim(substr($msgEnd,    $space));
                        if ($token1 === 'ttl' && ($rest === 'off' || ctype_digit($rest))) {
                            yield $this->pingTtl($rest);
                        } else {
                            $processed = false;
                        }
                        break;
                    }
                    if ($processed) {
                        $text = yield $this->getStatusText();
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
        return
        "<b>Ping Instructions:</b><br>" .
        "<br>" .
        "<b>ping help</b>      Shows this help text.<br>" .
        "<b>ping status</b>  Shows the status of the<br>" .
        " &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp; feature.<br>" .
        "<b>ping on</b>        Turns on the ping feature.<br>" .
        "<b>ping off</b>       Turns off the ping feature.<br>" .
        "<b>ping ttl off</b>   Ping messages will not be<br>" .
        " &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; deleted.<br>" .
        "<b>ping ttl 47</b>   Ping messages will be<br>" .
        " &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; deleted after 47 seconds.<br>";
    }

    private function getStatusText() {
        $status = yield $this->pingStatus();
        $ttl    = yield $this->pingTtl();
        return 'Ping status:' . ($status === 'on'? 'ON' : 'OFF') .
                  '  ttl:' . ($ttl === 'off'? 'OFF' : ($ttl . ' seconds'));
    }

    private function pingStatus(?string $value = null) {
        $store = yield Store::getInstance();
        if ($value === null) {
            $fetched = yield $store->get('ping.status');
            return $fetched === 'on'? 'on' : 'off';
        } else {
            $value = strtolower(trim($value));
            if ('on' === $value || 'off' === $value) {
                yield $store->set('ping.status', $value);
                return $value;
            }
        }
        throw new Exception();
    }

    private function pingTtl(?string $value = null) {
        $store = yield Store::getInstance();
        if ($value === null) {
            $fetched = yield $store->get('ping.ttl');
            return $fetched === null? 'off' : $fetched;
        } else {
            $value = strtolower(trim($value));
            if ('off' === $value || ctype_digit($value)) {
                yield $store->set('ping.ttl', $value);
                return $value;
            }
        }
        throw new Exception();
    }
}
