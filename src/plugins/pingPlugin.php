<?php

require_once(dirname(__DIR__, 1).'/Upd.php');

class PingPlugin {

    private $statusOn;

    public function __construct()
    {
        $this->statusOn = false;
    }

    public function process($mp, $selfId, $update)
    {
        // chatId: -1001289749330
        $msg   = Upd::getMsgText($update);
        $msgId = intval($update['message']['id']);
        $processed = true;
        if ($msg && strncasecmp($msg, 'ping', 4) === 0) {
            //$json = json_encode($update, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|
            //                                               JSON_UNESCAPED_SLASHES);
            //$json = ($json !== '')? $json : var_export($update, true);
            //yield $mp->echo(PHP_EOL . PHP_EOL . $json . PHP_EOL . PHP_EOL);

            $msgEnd = strtolower(trim(substr($msg, 4)));
            $reply = ' ';
            $deletes = [];
            switch ($msgEnd) {
            case '':
                if ($this->statusOn) {
                    if([$update['message']['out']]) {
                        $deletes[] = $msgId;
                    }
                    $deletes[] = $msgId + 1;
                    $updates = yield $this->sendMsg($mp, $update, 'pong');
                }
                $reply = null;
                break;
            case 'on':
                $this->statusOn = true;
                break;
            case 'off':
                $this->statusOn = false;
                break;
            case 'status':
                break;
            default:
                $processed = false;
                $reply     = null;
                break;
            }
            if($reply) {
                $text = 'Ping status is ' . ($this->statusOn? 'on.' : 'off.');
                yield $this->editMsg($mp, $update, $text);
                $deletes[] = $msgId;
            }
            if(count($deletes)) {
                yield $this->deleteMsgs($mp, $update, $deletes);
            }
        }
    }

    private function sendMsg($mp, $update, $text)
    {
        //try {
            $updates = yield $mp->messages->sendMessage([
                'peer'            => Upd::getToId($update),
                'reply_to_msg_id' => $update['message']['id'],
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

    private function editMsg($mp, $update, $text)
    {
        //try {
            yield $mp->messages->editMessage([
                'peer'       => $update['message']['to_id'], //Upd::getToId ($update),
                'id'         => $update['message']['id'],    // Upd::getMsgId($update),
                'message'    => $text,
                'parse_mode' => 'HTML'
            ]);
            /*
            yield $mp->messages->deleteMessages([
                'revoke' => true,
                'peer'   => $update['message']['to_id'],
                'id'     => [$update['message']['id']]
            ]);
            yield $mp->channels->deleteMessages([
                'channel' => $update['message']['to_id'],
                'id'      => [$update['message']['id']]
            ]);
            */
        //}
        //catch (\danog\MadelineProto\RPCErrorException $e) {
        //    \danog\MadelineProto\Logger::log((string)$e, \danog\MadelineProto\Logger::FATAL_ERROR);
        //}
        //catch (\danog\MadelineProto\Exception $e) {
        //    \danog\MadelineProto\Logger::log((string)$e, \danog\MadelineProto\Logger::FATAL_ERROR);
        //}
    }

    private function deleteMsgs($mp, $peer, $msgIds) {
        $mp->callFork((function() use ($mp, $peer, $msgIds)  {
            yield $mp->sleep(10);
            yield $mp->messages->deleteMessages([
                'revoke' => true,
                'peer'   => $peer,
                'id'     => $msgIds
            ]);
            yield $mp->channels->deleteMessages([
                'channel' => $peer,
                'id'      => $msgIds
            ]);
        })());
    }
}