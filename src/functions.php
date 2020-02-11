<?php declare(strict_types=1);

function printTime (
    $text,
    $fontSize  = 80,
    $position  = ['x' => 'right', 'y' => 'center'],
    $color     = ['red' => 255, 'green' => 255, 'blue' => 255],
    $photoPath = './imgs/photo.jpg',
    $imagePath = './imgs/writingOverImage.jpg'
) {
    $fontFile    = __DIR__.'/franklin.ttf';
    $jpegQuality = 85;

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
