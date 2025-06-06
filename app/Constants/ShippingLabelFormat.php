<?php

namespace App\Constants;

class ShippingLabelFormat
{
    const EPL2 = 1;
    const ZPLII = 2;
    const PDF = 10;
    const DAZzle = 100;
    const JPEG = 101;

    const names = [
        self::EPL2 => 'EPL2',
        self::ZPLII => 'ZPLII',
        self::PDF => 'PDF',
        self::DAZzle => 'DAZzle',
        self::JPEG => 'JPEG',
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
