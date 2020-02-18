<?php declare(strict_types=1);

namespace teleclient\madelbase\plugins;

class VerifyPlugin {

    private $CombinedMadelineProto;

    public function __construct($CombinedMadelineProto)
    {
        $this->CombinedMadelineProto = $CombinedMadelineProto;
    }

    protected function getInt($update, string $index) : int {
        return !isset($update['message'][$index]) ?
                        0 : intval($update['message'][$index]);
    }

    protected function getString($update, string $index) : ?string {
        return !isset($update['message'][$index]) ?
                        null : trim($update['message'][$index]);
    }
    protected function jsonStr($update): string {
        $res = json_encode($update, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        return $res;
    }

    public function process($MadelineProto, $update)
    {
        $session = '';

        $update_type  = $update['_'];
        $update_id    = $update['pts'];
        $userID       = $this->getInt($update,    'from_id');
        $msgID        = $this->getInt($update,    'id');
        $msg          = $this->getString($update, 'message');
        $replyToMsgID = $this->getInt($update,    'reply_to_msg_id');//This is a reply to some other msg

        $chatInfo     = yield $MadelineProto->get_info($update);
        $chatID       = intval($chatInfo['bot_api_id']);
        $chatType     = $chatInfo['type']; //Can be either “private”, “group”, “supergroup” or “channel”
        $chatTitle    = !isset($chatInfo['Chat']['title'])? ' ' : $chatInfo['Chat']['title'];
        if(isset($chatInfo['User']['username'])) {
            $chatTitle =$chatInfo['User']['username'];
        }
        //yield $MadelineProto->echo($this->jsonStr($chatInfo).PHP_EOL);

        $msgFront = substr(str_replace(array("\r", "\n"), '<br>', $msg), 0, 60);
        $msgDetail = $session . '  ' .
                                  'chatID:' . $chatID . '/' . $msgID . '  ' .
                                  $update_type . '/' . $update_id .
                                  '  ' . $chatType . ':[' . $chatTitle . ']' .
                                  '  msg:[' . $msgFront . ']';
        yield $MadelineProto->echo(PHP_EOL . $msgDetail . PHP_EOL);

        //$MadelineProto->echo($this->jsonStr($update).PHP_EOL);

        if ($userID === 0 &&
            isset($update['message']['to_id']['_']) &&
                  $update['message']['to_id']['_'] !== 'peerChannel')
        {
            $MadelineProto->logger('Missing from_id. '.$update_type.' update_id: '.$update_id);
            $MadelineProto->logger($this->jsonStr($update).PHP_EOL);
        }

        if ($msg === null &&
            $update['message']['_'] !== 'messageService')
        {
            $MadelineProto->logger('Missing message text. update_id: ' . $update_id);
            $MadelineProto->logger($this->jsonStr($update).PHP_EOL);
        }

        if ($msgID === 0 &&
            $update['message']['_'] !== 'messageService')
        {
            $MadelineProto->logger('Missing message_id. update_id: ' . $update_id);
            $MadelineProto->logger($this->jsonStr($update).PHP_EOL);
        }
    }
}
