<?php declare(strict_types=1);

namespace teleclient\madelbase;

require_once 'TimeStore.php';

use \danog\MadelineProto\Logger;
use \danog\MadelineProto\API;
use danog\MadelineProto\Loop\Impl\ResumableSignalLoop;

class TimeLoop extends ResumableSignalLoop {

    public  $API;
    private $MadelineProto;
    private $self;

    function __construct(API $API, array $self) {
        $this->API = $API;
        $this->MadelineProto = $API;
        $this->self = $self;

        //$dump = json_encode($self, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        //$dump = ($dump !== '')? $dump : var_export($self, true);
        //$API->echo($dump.PHP_EOL);

        $profPhotoId = $self['photo']['photo_id']; // 678120700215666732;
        $API->echo('Profile Photo Id: '.$profPhotoId.PHP_EOL);
    }

    public function loop(): \Generator
    {
        while (true) {
            $timeout = yield $this->timeLoop($this->MadelineProto, $this->self);

            $this->API->logger->logger("Pausing {$this} for {$timeout}", Logger::VERBOSE);

            if (yield $this->waitSignal($this->pause($timeout))) {
                return;
            }
        }
    }

    function timeLoop ($MadelineProto, $self)  {
        $status = yield TimeStore::timeStatus();
        if($status !== 'on') {
            $delay = $this->calculateDelay($MadelineProto);
            return $delay;
        }

        //yield $MadelineProto->echo(PHP_EOL.'BEGIN'.PHP_EOL);

        $userId = $self['id'];
        //$MadelineProto->echo($self['username'].PHP_EOL);

        $MadelineProto->sleep(1);
        $photos = yield $MadelineProto->photos->getUserPhotos([
            'user_id' => $userId,
            'offset'  => 0,
            'max_id'  => 0,
            'limit'   => 1
        ]);
        if(sizeof($photos['photos'])) {
            $photo = $photos['photos'][0];

            //$dump = json_encode($photo, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            //$dump = ($dump !== '')? $dump : var_export($photo, true);
            //$MadelineProto->echo($dump.PHP_EOL);

            // ToDo: read the id of last upladed photo from the STORE, and if equal to this id, delete
            //       otherwise, don't delete.
            $lastId = yield TimeStore::timeId();
            $lastId = $lastId === null? 0 : intval($lastId);
            //$MadelineProto->echo('LastId:   '.$lastId.PHP_EOL);

            //$MadelineProto->echo('PhotoId:  '.$photo['id'].PHP_EOL);
            //$photoAge  = time() - $photo['date'];
            //$MadelineProto->echo('PhotoAge: '.$photoAge.PHP_EOL);

            if($lastId === $photo['id']) {
                $longs = yield $MadelineProto->photos->deletePhotos([
                    'id' => [[
                        '_'              => 'inputPhoto',
                        'id'             => $photo['id'],
                        'access_hash'    => $photo['access_hash'],
                        'file_reference' => $photo['file_reference']
                    ]]
                ]);
            }
        }

        $time      = date('H:i');
        $fontSize  = yield TimeStore::timeSize();
        $imagePath = './imgs/writingOverImage.jpg';
        $photoPath = './imgs/photo.jpg';
        $place     = yield TimeStore::timePlace();
        $color     = yield TimeStore::timeColor();
        $this->printTime (
            $time,
            $fontSize,
            $place,
            $color,
            $photoPath,
            $imagePath
        );
        //$MadelineProto->echo('Time: '.$time.PHP_EOL);

        $pht = yield $MadelineProto->photos->uploadProfilePhoto([
            'file' => $photoPath
        ]);
        //$dump = json_encode($pht, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        //$dump = ($dump !== '')? $dump : var_export($pht, true);
        //$MadelineProto->echo($dump.PHP_EOL);

        // ToDo: Save the id to as the last-written id in the STORE.
        $newPhotoId   = $pht['photo']['id'];
        //$newPhotoRef  = $pht['photo']['file_reference']['bytes'];
        //$newPhotoDate = $pht['photo']['date'];
        yield TimeStore::timeId(strval($newPhotoId));

        yield \Amp\File\unlink($photoPath);

        $delay = $this->calculateDelay($MadelineProto);
        return $delay;
    }

    function calculateDelay($MadelineProto) {
        $now   = time();
        $next  = intdiv($now + 60, 60) * 60;
        $delay = $next - $now;
        $delay = $delay > 60? 60 : $delay;

        //$MadelineProto->echo('Now:   '.$now.PHP_EOL);
        //$MadelineProto->echo('Next:  '.$next.PHP_EOL);
        //$MadelineProto->echo('Delay: '.$delay.PHP_EOL);

        //$MadelineProto->echo('END'.PHP_EOL.PHP_EOL);

        return $delay;
    }

    function printTime (
        $text,
        $fontSize  = 80,
        //$position  = ['x' => 'right', 'y' => 'center'],
        $place     = 'bottom',
        $color     = ['red' => 255, 'green' => 255, 'blue' => 255],
        $photoPath = './imgs/photo.jpg',
        $imagePath = './imgs/writingOverImage.jpg'
    ) {
        $fontFile    = __DIR__.'/franklin.ttf';
        $jpegQuality = 85;

        switch ($place) {
            case 'center':
                $x = 'center';
                $y = 'center';
                break;
            case 'top':
                $x = 'center';
                $y = 'top';
                break;
            case 'bottom':
                $x = 'center';
                $y = 'bottom';
                break;
            case 'left':
                $x = 'left';
                $y = 'center';
                break;
            case 'right':
                $x = 'right';
                $y = 'center';
                break;
        }
        $position = ['x' => $x, 'y' => $y];

        $img       = imagecreatefromjpeg($imagePath);
        $textColor = imagecolorallocate ($img, $color['red'], $color['green'], $color['blue']);

        $dimes = imagettfbbox($fontSize, 0, $fontFile, $text);
        $minX  = min(array($dimes[0], $dimes[2] ,$dimes[4], $dimes[6]));
        $maxX  = max(array($dimes[0], $dimes[2], $dimes[4], $dimes[6]));
        $minY  = min(array($dimes[1],$dimes[3],$dimes[5],$dimes[7]));
        $maxY  = max(array($dimes[1],$dimes[3],$dimes[5],$dimes[7]));
        $dimensions['width'] = $maxX - $minX;
        $dimensions['heigh'] = $maxY - $minY;

        $x = $position['x'];
        $y = $position['y'];

        if($x=="left") {
            $startPosition["x"] = 0 + 10;
        }
        else if($x=="center") {
            $startPosition["x"] = imagesx($img)/2 - $dimensions["width"] / 2;
        }
        else if($x=="right") {
            $startPosition["x"] = imagesx($img) - $dimensions["width"] - 30;
        }
        //custom
        else {
            $startPosition["x"] = $x;
        }

        if($y=="top") {
            $startPosition["y"] = 0 + $dimensions["heigh"] + 50;
        }
        else if($y=="center") {
            $startPosition["y"]  = imagesy($img)/2 + $dimensions["heigh"] / 2;
        }
        else if($y=="bottom") {
            $startPosition["y"]  = imagesy($img) - 50;
        }
        //custom
        else {
            $startPosition["y"] = $y;
        }
        $startPosition["x"] = intval($startPosition["x"]);
        $startPosition["y"] = intval($startPosition["y"]);

        imagettftext(
            $img,
            $fontSize,
            0,
            $startPosition["x"],
            $startPosition["y"],
            $textColor,
            $fontFile,
            $text
        );
        imagejpeg($img, $photoPath, $jpegQuality);
    }


    public function __toString(): string
    {
        return 'TimeLoop';
    }
}