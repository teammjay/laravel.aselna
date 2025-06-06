<?php

namespace App\Constants;

class ImageRotate
{
    const None = 0;
    const Rotate90 = 1;
    const Rotate180 = 2;
    const Rotate270 = 3;

    const names = [
        self::None => 'None',
        self::Rotate90 => 'Rotate90',
        self::Rotate180 => 'Rotate180',
        self::Rotate270 => 'Rotate270',
    ];

    public static function getId($name)
    {
        return array_search($name, self::names, true);
    }

    public static function getName($id)
    {
        return self::names[$id] ?? null;
    }

    public static function fromId($id)
    {
        $name = self::getName($id);
        return $name !== null ? $id : null;
    }
}
