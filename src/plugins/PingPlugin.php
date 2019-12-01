<?php declare(strict_types=1);

require_once(dirname(__DIR__, 1).'/Upd.php');
require_once(dirname(__DIR__, 1).'/Store.php');

class PingPlugin {

    private $MadelineProto;

    public function __construct($MadelineProto)
    {
        $this->MadelineProto = $MadelineProto;
    }

    public function process($update)
    {
        $processed = false;
        $mp        = $this->MadelineProto;
        $peerId    = Upd::getToId($update);
        //if($peerId !== -1001289749330) {
        //    return false;
        //}
        $msg = Upd::getMsgText($update);
        if ($msg && strncasecmp($msg, 'ping', 4) === 0) {
            $processed = true;
            $msgId     = Upd::getMsgId($update);
            $msgIsOut  = Upd::isMsgOut($update);
            $ttl       = yield $this->pingTtl();
            $msgEnd    = strtolower(trim(substr($msg, 4)));
            if ($msgEnd === '') {
                $status   = yield $this->pingStatus();
                if ($status === 'on') {
                    yield $this->replyMsg($mp, $peerId, $msgId, 'pong');
                    $msgs = $msgIsOut? [$msgId, $msgId + 1] : [$msgId];
                    yield $this->deleteMsgs($mp, $peerId, $msgs, $ttl);
                }
            }
            elseif ($msgIsOut)
            {
                if ($msgEnd === 'help') {
                    yield $this->editMsg   ($mp, $peerId,  $msgId, $this->getHelpText());
                    yield $this->deleteMsgs($mp, $peerId, [$msgId], $ttl);
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
                        yield $this->editMsg   ($mp, $peerId,  $msgId,  $text);
                        yield $this->deleteMsgs($mp, $peerId, [$msgId], $ttl);
                    }
                }
            }
        }
        return $processed;
    }

    private function replyMsg($mp, $peerId, $msgId, $text)
    {
        //try {
            $updates = yield $mp->messages->sendMessage([
                'peer'            => $peerId,
                'reply_to_msg_id' => $msgId,
                'message'         => $text,
                'parse_mode'      => 'HTML'
            ]);
            return $updates;
        //}
        //catch (\danog\MadelineProto\RPCErrorException $e) {
        //    \danog\MadelineProto\Logger::log((string)$e, \danog\MadelineProto\Logger::FATAL_ERROR);
        //}
        //catch (\danog\MadelineProto\Exception $e) {
        //    \danog\MadelineProto\Logger::log((string)$e, \danog\MadelineProto\Logger::FATAL_ERROR);
        //}
    }

    private function editMsg($mp, $peerId, $msgId, $text)
    {
        //try {
            yield $mp->messages->editMessage([
                'peer'       => $peerId,
                'id'         => $msgId,
                'message'    => $text,
                'parse_mode' => 'HTML'
            ]);
        //}
        //catch (\danog\MadelineProto\RPCErrorException $e) {
        //    \danog\MadelineProto\Logger::log((string)$e, \danog\MadelineProto\Logger::FATAL_ERROR);
        //}
        //catch (\danog\MadelineProto\Exception $e) {
        //    \danog\MadelineProto\Logger::log((string)$e, \danog\MadelineProto\Logger::FATAL_ERROR);
        //}
    }

    private function deleteMsgs($mp, $peerId, $msgIds, $ttl) {
        if ($ttl !== 'off') {
            $mp->callFork((function () use ($mp, $peerId, $msgIds, $ttl) {
                yield $mp->sleep($ttl);
                yield $mp->messages->deleteMessages([
                    'revoke' => true,
                    'peer'   => $peerId,
                    'id'     => $msgIds
                ]);
                yield $mp->channels->deleteMessages([
                    'channel' => $peerId,
                    'id'      => $msgIds
                ]);
            })());
        }
    }

    private function getHelpText() {
        return
        "Ping Instructions:<br>" .
        "<br>" .
        "ping help      Show this help text.<br>." .
        "ping status    Shows the status of the feature<br>." .
        "ping on        Turns on the ping feature.<br>" .
        "Ping off       Turns off the ping feature.<br>" .
        "ping ttl off   Ping messages will not be.<br>" .
        "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;      deleted." .
        "ping ttl 47    Ping messages will be<br>" .
        "               deleted after 47 seconds.<br>";
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
