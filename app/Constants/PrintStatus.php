<?php

namespace App\Constants;

class PrintStatus
{
    const Pending = 0;
    const Printed = 1;
    const PrintError = 2;
    const Assigned = 3;

    const names = [
        self::Pending => 'Pending',
        self::Printed => 'Printed',
        self::PrintError => 'PrintError',
        self::Assigned => 'Assigned',
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
