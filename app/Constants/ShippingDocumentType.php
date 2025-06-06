<?php

namespace App\Constants;

class ShippingDocumentType
{
    const Label = 1;
    const PackingSlip = 2;
    const LabelWithPackingSlip = 3;
    const ReturnLabel = 4;
    const ReturnLabelWithPackingSlip = 5;
    const PickList = 6;

    const names = [
        self::Label => 'Label',
        self::PackingSlip => 'PackingSlip',
        self::LabelWithPackingSlip => 'LabelWithPackingSlip',
        self::ReturnLabel => 'ReturnLabel',
        self::ReturnLabelWithPackingSlip => 'ReturnLabelWithPackingSlip',
        self::PickList => 'PickList',
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
