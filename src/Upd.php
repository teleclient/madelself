<?php

require_once 'Peer.php';

class Upd implements ArrayAccess
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

    public static function isMsgOut($update) : bool {
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

/*
{
    "_": "updateNewChannelMessage",
    "message": {
        "_": "message",
        "out": true,
        "mentioned": false,
        "media_unread": false,
        "silent": false,
        "post": false,
        "from_scheduled": false,
        "legacy": false,
        "edit_hide": false,
        "id": 907,
        "from_id": 157887279,
        "to_id": {
            "_": "peerChannel",
            "channel_id": 1289749330
        },
        "date": 1573130962,
        "message": "ping"
    },
    "pts": 1391,
    "pts_count": 1
}


{
    "_": "updateNewChannelMessage",
    "message": {
        "_": "message",
        "out": true,
        "mentioned": false,
        "media_unread": false,
        "silent": false,
        "post": false,
        "from_scheduled": false,
        "legacy": false,
        "edit_hide": false,
        "id": 908,
        "from_id": 157887279,
        "to_id": {
            "_": "peerChannel",
            "channel_id": 1289749330
        },
        "reply_to_msg_id": 907,
        "date": 1573130962,
        "message": "pong"
    },
    "pts": 1392,
    "pts_count": 1
}

{
    "_": "updateNewChannelMessage",
    "message": {
        "_": "messageService",
        "out": false,
        "mentioned": false,
        "media_unread": false,
        "silent": true,
        "post": true,
        "legacy": false,
        "id": 390,
        "to_id": {
            "_": "peerChannel",
            "channel_id": 1492468097
        },
        "reply_to_msg_id": 389,
        "date": 1573150454,
        "action": {
            "_": "messageActionPinMessage"
        }
    },
    "pts": -1,
    "pts_count": -1
}


Chat": {
    "_":"channel",
    "creator":true,
    "left":false,
    "broadcast":false,
    "verified":false,
    "megagroup":true,
    "restricted":false,
    "signatures":false,
    "min":false,
    "scam":false,
    "has_link":false,
    "has_geo":false,
    "slowmode_enabled":false,
    "id":1289749330,
    "access_hash":2033098084005994628,
    "title":"\u062e\u0627\u0646\u0648\u0627\u062f\u0647 \u0647\u0627\u06cc \u0622\u0631\u0627\u0646 \u0642\u062f\u06cc\u0645 \u0648 \u0631\u0648\u0627\u0628\u0637\u0634\u0627\u0646",
    "photo":{
        "_":"chatPhoto",
        "photo_small":{
            "_":"fileLocationToBeDeprecated",
            "volume_id":806627833,
            "local_id":48829
        },
        "photo_big":{
            "_":"fileLocationToBeDeprecated",
            "volume_id":806627833,
            "local_id":48831
        },
        "dc_id":1
    },
    "date":1543725462,
    "version":0,
    "default_banned_rights":{
        "_":"chatBannedRights","view_messages":false,"send_messages":false,"send_media":false,"send_stickers":false,
        "send_gifs":false,"send_games":false,"send_inline":false,"embed_links":false,"send_polls":false,"change_info":true,
        "invite_users":false,"pin_messages":true,"until_date":2147483647
    }
},
"InputPeer":{
    "_":"inputPeerChannel",
    "channel_id":1289749330,
    "access_hash":2033098084005994628,
    "min":false
},
"Peer":{
    "_":"peerChannel",
    "channel_id":1289749330},
    "DialogPeer":{
        "_":"dialogPeer",
        "peer":{
            "_":"peerChannel",
            "channel_id":1289749330
        }
    },
    "NotifyPeer":{
        "_":"notifyPeer",
        "peer":{
            "_":"peerChannel",
            "channel_id":1289749330
        }
    },
    "InputDialogPeer":{
        "_":"inputDialogPeer",
        "peer":{
            "_":"inputPeerChannel",
            "channel_id":1289749330,
            "access_hash":2033098084005994628,
            "min":false
        }
    },
    "InputNotifyPeer":{
        "_":"inputNotifyPeer",
        "peer":{
            "_":"inputPeerChannel",
            "channel_id":1289749330,
            "access_hash":2033098084005994628,
            "min":false
        }
    },
    "InputChannel":{
        "_":"inputChannel",
        "channel_id":1289749330,
        "access_hash":2033098084005994628,
        "min":false
    },
    "channel_id":1289749330,
    "bot_api_id":-1001289749330,
    "type":"supergroup"
}

*/
