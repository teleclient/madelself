<?php

namespace teleclient\madelbase;

require_once 'Peer.php';

class Upd implements \ArrayAccess
{
    public $update;
    public $message;

    public function __construct($update)
    {
        $this->$update = $update;
        $this->message = $update['message'];
    }

    public static function getInt($update, $index) : int {
        $value = !isset($update['message'][$index]) ?
                        0 : intval($update['message'][$index]);
        return $value;
    }

    public static function getString($update, $index) : ?string {
        $value = !isset($update['message'][$index]) ?
                        null : trim($update['message'][$index]);
        return $value;
    }

    public static function toJson($update) : ?string {
        $res = json_encode($update, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $res = ($res !== '')? $res : var_export($update, true);
        return  $res;
    }

    public static function getToId($update) : int {
        $id = 0;
        if ( isset($update['message']['to_id'])) {
            $type = $update['message']['to_id']['_'];
            switch ($type) {
                case 'peerChannel':
                    $id = intval('-100' . trim($update['message']['to_id']['channel_id']));
                    break;
                case 'peerChat':
                    $id = intval('-' . trim($update['message']['to_id']['chat_id']));
                    break;
                case 'peerUser':
                    $id = intval(trim($update['message']['to_id']['user_id']));
                    break;
            }
        }
        return $id;
    }

    public static function getMsgId($update) : int {
        return !isset($update['message']['id'])? 0 :
               intval($update['message']['id']);
    }

    public static function getReplyToMsgId($update) : int {
        return !isset($update['message']['reply_to_msg_id'])? 0 :
               intval($update['message']['reply_to_msg_id']);
    }

    public static function getMsgText($update) : ?string {
        return !isset($update['message']['message'])? null :
                      $update['message']['message'];
    }

    public static function isMsgOutward($update) : bool {
        return !isset($update['message']['out'])? false : $update['message']['out'];
    }

    public function offsetGet($offset): ?string
    {
        switch ($offset) {
            case 'to_id':
                return $this->getPeerId($this->update);
            case 'from_id':
                return !isset($this->message['from_id'])? 0 : intval($this->message['from_id']);
            case 'reply_to_msg_id':
                return !isset($this->message['reply_to_msg_id'])? 0 : intval($this->message['reply_to_msg_id']);
            case 'update_id':
                return intval($this->update['pts']);
            case 'message_id':
                return intval($this->message['id']);
            case 'out':
                return intval($this->message['out'] === 'false'? 0 : 1);
            case 'mentioned':
                return intval($this->message['mentioned'] === 'false'? 0 : 1);
            case 'media_unread':
                return intval($this->message['media_unread'] === 'false'? 0 : 1);
            case 'silent':
                return intval($this->message['silent'] === 'false'? 0 : 1);
            case 'post':
                return intval($this->message['post'] === 'false'? 0 : 1);
            case 'from_scheduled':
                return intval($this->message['from_scheduled'] === 'false'? 0 : 1);
            case 'legacy':
                return intval($this->message['legacy'] === 'false'? 0 : 1);
            case 'edit_hide':
                return intval($this->message['edit_hide'] === 'false'? 0 : 1);
            case 'message':
                return !isset($this->message['message'])? null : trim($this->message['message']);
        }
        return array_key_exist($offset, $this->message) ? $this->message[$offset] : null;
    }

    public function offsetExists($offset)
    {
        throw new Exception();
    }

    public function offsetSet($offset, $value)
    {
        throw new Exception();
    }

    public function offsetUnset($offset)
    {
        throw new Exception();
    }
}
