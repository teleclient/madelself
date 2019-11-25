<?php

class Peer implements ArrayAccess {

    public $peer;

    public function __construct($peer) 
    {
        $this->$peer = $peer;
    }

    public function offsetGet($offset)
    {
        switch ($offset) {
            case 'type':
                return $this->peer['_'];
            case 'user':
                $user  = ['bot' => $this->peer['id']];
                $user += ['cli' => 'user#' . $this->peer['id']];
                return $user;
            case 'chat':
                $chat  = ['bot' => $this->peer['id']];
                $chat += ['cli' => 'channel#' . $this->peer['id']];
                return $chat;
            case 'channel':
                $channel  = ['bot' => $this->peer['id']];
                $channel += ['cli' => 'channel#' . $this->peer['id']];
                return $channel;
            case 'bot':
                $bot  = ['user'    =>          trim($this->peer['id'])];
                $bot += ['chat'    => '-100' . trim($this->peer['id'])];
                $bot += ['channel' => '-'    . trim($this->peer['id'])];
                return $bot;
            case 'cli':
                $cli  = ['user'    => 'user#'    . $this->peer['id']];
                $cli += ['chat'    => 'chat#'    . $this->peer['id']];
                $cli += ['channel' => 'channel#' . $this->peer['id']];
                return $cli;
        }
    }

    public function offsetExists($offset) {
        throw new Exception('Not implemented');
    }

    public function offsetSet($offset, $value) {
        throw new Exception('Not implemented');
    }

    public function offsetUnset($offset) {
        throw new Exception('Not implemented');
    }
}

/*
$Peer = 44700;        // bot API id (users)
$Peer = -492772765;   // bot API id (chats)
$Peer = -10038575794; // bot API id (channels)

$Peer = 'user#44700';       // tg-cli style id (users)
$Peer = 'chat#492772765';   // tg-cli style id (chats)
$Peer = 'channel#38575794'; // tg-cli style id (channels)

$Peer = '@username'; // Username

$Peer = 'me'; // The currently logged-in user

$Peer = 'https://t.me/danogentili';           // t.me URLs
$Peer = 'https://t.me/joinchat/asfln1-21fa_'; // t.me invite links

*/
