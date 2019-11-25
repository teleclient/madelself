<?php declare(strict_types=1);

require_once '../../vendor/autoload.php';

class VerifyPlugin {

    private $MadelineProto;
    private $store;
    private $selfId;

    public function __construct($MadelineProto, $store, int $selfId)
    {
        $this->MadelineProto = $MadelineProto;
        $this->store         = $store;
        $this->selfId        = $selfId;
    }

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

    public function process($update)
    {
        $MadelineProto = $this->MadelineProto;

        $update_type  = $update['_'];
        $update_id    = $update['pts'];
        $userID       = $this->getInt($update,    'from_id');
        $msgID        = $this->getInt($update,    'id');
        $msg          = $this->getString($update, 'message');
        $replyToMsgID = $this->getInt($update,    'reply_to_msg_id'); // This msg is a reply to some other msg
        $chatInfo     = yield $MadelineProto->get_info($update);
        $chatID       = intval($chatInfo['bot_api_id']);
        $chatType     = $chatInfo['type'];  // can be either “private”, “group”, “supergroup” or “channel”
        $chatTitle    = !isset($chatInfo['Chat']['title'])? ' ' : $chatInfo['Chat']['title'];

        $res = json_encode($update, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|
                                                      JSON_UNESCAPED_SLASHES);

        //$chatJson = json_encode($chatInfo,  JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|
        //                                    JSON_UNESCAPED_SLASHES );
        //yield $MadelineProto->echo($chatJson . PHP_EOL);

        $msgFront = substr(str_replace(array("\r", "\n"), '<br>', $msg), 0, 60);
        yield $MadelineProto->echo(PHP_EOL . 'chatID: '.$chatID.'/'.$update_id.' '.$update_type.' ['.$chatTitle .'] ['.$msgFront.']'.PHP_EOL);
        $MadelineProto->logger('chatID: '.$chatID.'/'.$update_id.' '.$update_type.' '.$msgFront);
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
