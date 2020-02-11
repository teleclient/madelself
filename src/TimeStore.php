<?php declare(strict_types=1);

namespace teleclient\madelbase;

require_once 'Store.php';

use \teleclient\madelbase\Store;


class TimeStore {

    public function __construct()
    {
    }

    public static function getStatusText() {
        $status = yield self::timeStatus();
        $ttl    = yield self::timeTtl();
        $size   = yield self::timeId();
        $size   = yield self::timeSize();
        $place  = yield self::timePlace();
        $color  = yield self::timeColor();
        $upPlace = strtoupper($place);

        $text = 'Time status:' . ($status === 'on'? 'ON' : 'OFF') .
                    '  ttl:' . ($ttl === 'off'? 'OFF' : ($ttl . ' seconds')) . '<br>' .
                "size:$size  place:$upPlace<br>" .
                "color:{$color['red']} {$color['green']} {$color['blue']} ";
        return $text;
    }

    public static function timeStatus(?string $value = null) {
        $store = yield Store::getInstance();
        if ($value === null) {
            $fetched = yield $store->get('time.status');
            return $fetched === 'on'? 'on' : 'off';
        } else {
            $value = strtolower(trim($value));
            if ('on' === $value || 'off' === $value) {
                yield $store->set('time.status', $value);
                return $value;
            }
        }
        return null;
    }

    public static function timeTtl(?string $value = null) {
        $store = yield Store::getInstance();
        if ($value === null) {
            $fetched = yield $store->get('time.ttl');
            return $fetched === null? 'off' : $fetched;
        } else {
            $value = strtolower(trim($value));
            if ('off' === $value || ctype_digit($value)) {
                yield $store->set('time.ttl', $value);
                return $value;
            }
        }
        return null;
    }

    public static function timePlace(?string $value = null) {
        $store = yield Store::getInstance();
        if ($value === null) {
            $fetched = yield $store->get('time.place');
            return $fetched === null? 'center' : $fetched;
        } else {
            $value = strtolower(trim($value));
            if (in_array($value, ['center', 'top', 'bottom', 'left', 'right'], true)) {
                yield $store->set('time.place', $value);
                return $value;
            }
        }
        return null;
    }

    public static function timeId(?string $value = null) {
        $store = yield Store::getInstance();
        if ($value === null) {
            $fetched = yield $store->get('time.id');
            return $fetched === null? 0 : intval($fetched);
        } else {
            $number = intval($value);
            if (0 <= $number) {
                yield $store->set('time.id', $value);
                return $number;
            }
        }
        return null;
    }

    public static function timeSize(?string $value = null) {
        $store = yield Store::getInstance();
        if ($value === null) {
            $fetched = yield $store->get('time.size');
            return $fetched === null? 80 : intval($fetched);
        } else {
            $number = intval($value);
            if (0 < $number) {
                yield $store->set('time.size', $value);
                return $number;
            }
        }
        return null;
    }

    public static function timeColor(?string $value = null) {
        $store = yield Store::getInstance();
        if ($value === null) {
            $fetched = yield $store->get('time.color');
            $color = [
                'red'   => 255,
                'green' => 255,
                'blue'  => 255
            ];
            if($fetched !== null) {
                $parts = explode(' ', $fetched);
                $color = [
                    'red'   => intval($parts[0]),
                    'green' => intval($parts[1]),
                    'blue'  => intval($parts[2])
                ];
            }
            return $color;
        } else {
            $parts = explode(' ', $value);
            if (sizeof($parts) === 3   &&
                ctype_digit($parts[0]) && intval($parts[0]) >= 0 && intval($parts[0]) <= 255 &&
                ctype_digit($parts[1]) && intval($parts[1]) >= 0 && intval($parts[1]) <= 255 &&
                ctype_digit($parts[2]) && intval($parts[2]) >= 0 && intval($parts[2]) <= 255
            ){
                $text = "$parts[0] $parts[1] $parts[1]";
                yield $store->set('time.color', $text);
                $color = [
                    'red'   => intval($parts[0]),
                    'green' => intval($parts[1]),
                    'blue'  => intval($parts[2])
                ];
                return $color;
            }
        }
        return null;
    }
}
