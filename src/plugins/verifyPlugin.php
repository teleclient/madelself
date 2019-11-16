<?php

class VerifyPlugin {

    protected function getInt($update, $index) : int {
        $value = !isset($update['message'][$index]) ?
                        0 : intval($update['message'][$index]);
        return $value;
    }

    protected function getString($update, $index) : ?string {
        $value = !isset($update['message'][$index]) ?
                        null : trim($update['message'][$index]);
        return $value;
    }

    public function process($MadelineProto, $selfId, $update)
    {
        $update_type  = $update['_'];
        $update_id    = $update['pts'];
        $userID       = $this->getInt($update,    'from_id');
        $msgID        = $this->getInt($update,    'id');
        $msg          = $this->getString($update, 'message');
        $replyToMsgID = $this->getInt($update,    'reply_to_msg_id'); // This msg is a reply to some other msg
        $chatInfo     = yield $MadelineProto->get_info($update);
        $chatID       = intval($chatInfo['bot_api_id']);
        $chatType     = $chatInfo['type'];  // can be either “private”, “group”, “supergroup” or “channel”
        //$chatInfo     = null;

        $res = json_encode($update, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|
                            JSON_UNESCAPED_SLASHES);
        $chatJson = json_encode($chatInfo,  JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|
                                            JSON_UNESCAPED_SLASHES );

        $msgFront = substr(nl2br($msg, FALSE), 0, 40);
        yield $MadelineProto->echo  ('chatID: '.$chatID.'/'.$update_id.' '.$update_type.' '.$msgFront.PHP_EOL);
        $MadelineProto->logger('chatID: '.$chatID.'/'.$update_id.' '.$update_type.' '.$msgFront);
        yield $MadelineProto->echo($chatJson . PHP_EOL);
        if ($userID === 0 &&
            isset($update['message']['to_id']['_']) &&
                  $update['message']['to_id']['_'] !== 'peerChannel') {
            $MadelineProto->logger('Missing from_id. '.$update_type.' update_id: '.$update_id);
            $MadelineProto->logger($res.PHP_EOL);
        }

        if ($msg === null &&
            $update['message']['_'] !== 'messageService') {
            $MadelineProto->logger('Missing message text. update_id: ' . $update_id);
            $MadelineProto->logger($res.PHP_EOL);
        }

        if ($msgID === 0 &&
            $update['message']['_'] !== 'messageService') {
            $MadelineProto->logger('Missing message_id. update_id: ' . $update_id);
            $MadelineProto->logger($res.PHP_EOL);
        }
    }
}
