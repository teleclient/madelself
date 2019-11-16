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
        $msg = Upd::getMsgText($update);
        //yield $mp->echo('PING: ' . substr($msg, 0, 20) . PHP_EOL . PHP_EOL);
        $processed = true;
        if ($msg && strncasecmp($msg, 'ping', 4) === 0) {
            $msgEnd = strtolower(trim(substr($msg, 4)));
            //yield $mp->echo('PING: REST:' . $msgEnd . PHP_EOL);
            $reply = ' ';
            switch ($msgEnd) {
            case '':
                if ($this->statusOn) {
                    yield $this->sendMsg($mp, $update, 'pong');
                    //yield $mp->echo('Ping command:' . $msgEnd . PHP_EOL);
                }
                $reply = null;
                break;
            case 'on':
                $this->statusOn = true;
                //yield $mp->echo('Ping ON command: REST:' . $msgEnd . PHP_EOL);
                break;
            case 'off':
                $this->statusOn = false;
                //yield $mp->echo('Ping OFF command: REST:' . $msgEnd . PHP_EOL);
                break;
            case 'status':
                //yield $mp->echo('Ping STATUS command: REST:' . $msgEnd . PHP_EOL);
                break;
            default:
                //yield $mp->echo('Ping Not Processed:' . $msgEnd . PHP_EOL);
                $processed = false;
                $reply     = null;
                break;
            }
            if($reply) {
                $text = 'Ping status is ' . ($this->statusOn? 'on.' : 'off.');
                //yield $mp->echo($text.PHP_EOL);
                yield $this->editMsg($mp, $update, $text);
            }
        }
    }

    private function sendMsg($mp, $update, $text)
    {
        //yield $mp->echo('send ' . Upd::getToId($update) . '/' . Upd::getReplyToMsgId($update) . ': ' . $text . PHP_EOL);
        //try {
            yield $mp->messages->sendMessage([
                'peer'            => Upd::getToId($update),
                'reply_to_msg_id' => $update['message']['id'],  //Upd::getReplyToMsgId($update),
                'message'         => $text,
                'parse_mode'      => 'HTML'
            ]);
        //}
        //catch (\danog\MadelineProto\RPCErrorException $e) {
        //    yield \danog\MadelineProto\Logger::log((string)$e, \danog\MadelineProto\Logger::FATAL_ERROR);
        //}
        //catch (\danog\MadelineProto\Exception $e) {
        //    yield \danog\MadelineProto\Logger::log((string)$e, \danog\MadelineProto\Logger::FATAL_ERROR);
        //}
    }

    private function editMsg($mp, $update, $text)
    {
        //yield $mp->echo('edit ' . Upd::getToId($update). '/' . Upd::getReplyToMsgId($update) . ': ' . $text . PHP_EOL);
        //$json = Upd::toJson($update);
        //yield $mp->echo($json. PHP_EOL);
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
        //    yield \danog\MadelineProto\Logger::log((string)$e, \danog\MadelineProto\Logger::FATAL_ERROR);
        //}
        //catch (\danog\MadelineProto\Exception $e) {
        //    yield \danog\MadelineProto\Logger::log((string)$e, \danog\MadelineProto\Logger::FATAL_ERROR);
        //}
    }
}