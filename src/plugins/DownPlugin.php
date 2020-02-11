<?php declare(strict_types=1);

namespace teleclient\madelbase\plugins;

use danog\MadelineProto\Logger;
use danog\MadelineProto\RPCErrorException;
use League\Uri\Contracts\UriException;

require_once(dirname(__DIR__, 1).'/Upd.php');
require_once(dirname(__DIR__, 1).'/Store.php');

use \teleclient\madelbase\Upd;
use \teleclient\madelbase\Store;


class DownPlugin {

    private $CombinedMadelineProto;
    const ADMIN = 'webwarp';

    public function __construct($CombinedMadelineProto)
    {
        $this->CombinedMadelineProto = $CombinedMadelineProto;
    }

    public function report($message)
    {
        try {
            $this->messages->sendMessage([
                'peer'    => self::ADMIN,
                'message' => $message
            ]);
        } catch (\Throwable $e) {
            $this->logger("While reporting: $e", Logger::FATAL_ERROR);
        }
    }

    public function process($MadelineProto, $update)
    {
        //$MadelineProto = $this->CombinedMadelineProto->instances[$session];
        $session = '';

        $processed = false;
        $msg = Upd::getMsgText($update);
        $peer = yield $MadelineProto->getInfo($update);
        $peerId = $peer['bot_api_id'];
        $messageId = $update['message']['id'];

        //if (/*strncasecmp($session, 'bot', 3) !== 0 &&*/ $msg && strncasecmp($msg, 'ping', 4) === 0) {

        if(\stripos(trim($msg), '/dl ') === 0) {

            try {

                $tokens = \explode(' ', trim($msg));
                if(sizeof($tokens) > 3) {
                    echo 'Invalid input';
                    //throw ;
                }
                $name = \trim($tokens[2] ?? \basename($tokens[1]));
                $url  = \trim($tokens[1]);
                if (!$url) {
                    echo 'Invalid input';
                    // continue;
                }
                if (\stripos($url, 'http') !== 0) {
                    $url = "http://$url";
                }
                echo($url .PHP_EOL);
                echo($name.PHP_EOL);

                $id = yield $MadelineProto->messages->sendMessage([
                    'peer'            => $peerId,
                    'message'         => 'Preparing...',
                    'reply_to_msg_id' => $messageId
                ]);
                if (!isset($id['id'])) {
                    $$MadelineProto->report(\json_encode($id));
                    foreach ($id['updates'] as $updat) {
                        if (isset($updat['id'])) {
                            $id = $updat['id'];
                            break;
                        }
                    }
                } else {
                    $id = $id['id'];
                }

                $url = new \danog\MadelineProto\FileCallback(
                    $url,
                    function ($progress, $speed, $time) use ($peerId, $id) {
                        $this->logger("Upload progress: $progress%");

                        static $prev = 0;
                        $now = \time();
                        if ($now - $prev < 10 && $progress < 100) {
                            return;
                        }
                        $prev = $now;
                        try {
                            yield $this->messages->editMessage(
                                [
                                    'peer'    => $peerId,
                                    'id'      => $id,
                                    'message' => "Upload progress: $progress%\n".
                                                "Speed: $speed mbps\n".
                                                "Time elapsed since start: $time"
                                ],
                                [
                                    'FloodWaitLimit' => 0
                                ]
                            );
                        } catch (\danog\MadelineProto\RPCErrorException $e) {
                        }
                    }
                );
                yield $this->messages->sendMedia(
                    [
                        'peer' => $peerId,
                        'reply_to_msg_id' => $messageId,
                        'media' => [
                            '_' => 'inputMediaUploadedDocument',
                            'file' => $url,
                            'attributes' => [
                                ['_' => 'documentAttributeFilename', 'file_name' => $name]
                            ]
                        ],
                        'message'    => 'Powered by @MadelineProto!',
                        'parse_mode' => 'Markdown'
                    ]
                );

                if (\in_array($peer['type'], ['channel', 'supergroup'])) {
                    yield $this->channels->deleteMessages([
                        'channel' => $peerId,
                        'id'      => [$id]
                    ]);
                } else {
                    yield $this->messages->deleteMessages([
                        'revoke' => true,
                        'id'     => [$id]
                    ]);
                }
            } catch (\Throwable $e) {
                if (\strpos($e->getMessage(), 'Could not connect to URI') === false &&
                    !($e instanceof UriException) &&
                    \strpos($e->getMessage(), 'URI') === false)
                {
                    $this->report((string) $e);
                    $this->logger((string) $e, \danog\MadelineProto\Logger::FATAL_ERROR);
                }
                if ($e instanceof RPCErrorException && $e->rpc === 'FILE_PARTS_INVALID') {
                    $this->report(\json_encode($url));
                }
                try {
                    yield $this->messages->editMessage([
                        'peer'    => $peerId,
                        'id'      => $id,
                        'message' => 'Error: '.$e->getMessage()
                    ]);
                } catch (\Throwable $e) {
                    $this->logger((string) $e, \danog\MadelineProto\Logger::FATAL_ERROR);
                }
            }
        }
        return $processed;
    }









    private function replyMsg($mp, $peerId, $msgId, $text)
    {
        try {
            $updates = yield $mp->messages->sendMessage([
                'peer'            => $peerId,
                'reply_to_msg_id' => $msgId,
                'message'         => $text,
                'parse_mode'      => 'HTML'
            ]);
            return $updates;
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            \danog\MadelineProto\Logger::log((string)$e, \danog\MadelineProto\Logger::FATAL_ERROR);
        } catch (\danog\MadelineProto\Exception $e) {
            \danog\MadelineProto\Logger::log((string)$e, \danog\MadelineProto\Logger::FATAL_ERROR);
        }
    }

    private function editMsg($mp, $peerId, $msgId, $text)
    {
        try {
            yield $mp->messages->editMessage([
                'peer'       => $peerId,
                'id'         => $msgId,
                'message'    => $text,
                'parse_mode' => 'HTML'
            ]);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            \danog\MadelineProto\Logger::log((string)$e, \danog\MadelineProto\Logger::FATAL_ERROR);
        } catch (\danog\MadelineProto\Exception $e) {
            \danog\MadelineProto\Logger::log((string)$e, \danog\MadelineProto\Logger::FATAL_ERROR);
        }
    }

    private function deleteMsgs($mp, $peerId, $msgIds, $ttl) {
        if ($ttl !== 'off') {
            $mp->callFork((function () use ($mp, $peerId, $msgIds, $ttl) {
                yield $mp->sleep($ttl);
                try {
                        yield $mp->messages->deleteMessages([
                        'revoke' => true,
                        'peer'   => $peerId,
                        'id'     => $msgIds
                    ]);
                        yield $mp->channels->deleteMessages([
                        'channel' => $peerId,
                        'id'      => $msgIds
                    ]);
                } catch (\danog\MadelineProto\RPCErrorException $e) {
                    \danog\MadelineProto\Logger::log((string)$e, \danog\MadelineProto\Logger::FATAL_ERROR);
                } catch (\danog\MadelineProto\Exception $e) {
                    \danog\MadelineProto\Logger::log((string)$e, \danog\MadelineProto\Logger::FATAL_ERROR);
                }
            })());
        }
    }

    private function getHelpText() {
        return
        "<b>Ping Instructions:</b><br>" .
        "<br>" .
        "<b>ping help</b>      Shows this help text.<br>" .
        "<b>ping status</b>  Shows the status of the<br>" .
        " &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp; feature.<br>" .
        "<b>ping on</b>        Turns on the ping feature.<br>" .
        "<b>ping off</b>       Turns off the ping feature.<br>" .
        "<b>ping ttl off</b>   Ping messages will not be<br>" .
        " &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; deleted.<br>" .
        "<b>ping ttl 47</b>   Ping messages will be<br>" .
        " &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; deleted after 47 seconds.<br>";
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
