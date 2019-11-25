<?php declare(strict_types=1);

//require_once '../../vendor/autoload.php';
require_once(dirname(__DIR__, 1).'/Upd.php');
require_once(dirname(__DIR__, 1).'/Store.php');

class PingPlugin {
    const PING_STATUS = 'ping.status';
    const PING_TTL    = 'ping.ttl';

    private $MadelineProto;

    public function __construct($MadelineProto)
    {
        $this->MadelineProto = $MadelineProto;
    }

    public function process($update)
    {
        $processed = false;
        $peerId   = Upd::getToId($update);
        if($peerId !== -1001289749330) {
            return false;
        }
        $msg = Upd::getMsgText($update);
        if ($msg && strncasecmp($msg, 'ping', 4) === 0) {
            $processed = true;
            $mp       = $this->MadelineProto;
          //$selfId   = yield $mp->__get('self_id')[0];
            $store    = yield Store::getInstance();
            $ttl      = yield $store->get('ping.ttl');
            $msgId    = Upd::getMsgId($update);
            $msgIsOut = Upd::isMsgOut($update);
            $msgEnd = strtolower(trim(substr($msg, 4)));

            if ($msgEnd === '') {
                $status   = yield $store->get('ping.status');
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
                        yield $store->set('ping.status', 'on');
                        break;
                    case 'off':
                        yield $store->set('ping.status', 'off');
                        break;
                    case 'status':
                        $status = yield $store->get('ping.status');
                        break;
                    default:
                        $rest  = trim(substr($msgEnd, 4));
                        $front = trim(substr($msgEnd, 0, 4));
                        if ($front === 'ttl' && ctype_digit($rest)) {
                            yield $store->set('ping.ttl', $rest);
                        } elseif ($front === 'ttl' && $rest === 'off') {
                            yield $store->delete('ping.ttl');
                        } else {
                            $processed = false;
                        }
                        break;
                    }
                    if ($processed) {
                        $text = yield $this->getStatusText($store);
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
        if ($ttl !== null) {
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
        return 'Help Text';
    }

    private function getStatusText($store) {
        $status = yield $store->get('ping.status');
        $status = $status === 'on'? 'ON' : 'OFF';

        $ttl    = yield $store->get('ping.ttl');
        $ttl    = $ttl === null? 'OFF' : $ttl;

        return 'Ping status:' . $status . '  ttl:' . $ttl . ($ttl !== 'OFF'? ' seconds' : '');
    }
}