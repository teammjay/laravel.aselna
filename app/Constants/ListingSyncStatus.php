<?php

namespace App\Constants;

class ListingSyncStatus
{

    const Synchronizing = 0;
    const Unsynchronize = 1;
    const SynchronizingInventory = 2;
    const SynchronizingPrice = 3;
    const PriceError = 4;
    const InventoryError = 5;
    const Synchronized = 6;
    const CreationError = 7;
    const UpdateError = 8;
    const SynchronizingImage = 9;
    const SynchronizingRelationship = 10;
    const ImageError = 11;
    const RelationshipError = 12;
    const DeleteError = 13;
    const OrderOperationError = 14;

    const names = [
        self::Synchronizing => 'Synchronizing',
        self::Unsynchronize => 'Unsynchronize',
        self::SynchronizingInventory => 'Synchronizing Inventory',
        self::SynchronizingPrice => 'Synchronizing Price',
        self::PriceError => 'Price Error',
        self::InventoryError => 'Inventory Error',
        self::Synchronized => 'Synchronized',
        self::CreationError => 'Processing Error',
        self::UpdateError => 'Update Error',
        self::SynchronizingImage => 'Synchronizing Image',
        self::SynchronizingRelationship => 'Synchronizing Relationship',
        self::ImageError => 'Synchronizing Relationship',
        self::RelationshipError => 'Relationship Error',
        self::DeleteError => 'Delete Error',
        self::OrderOperationError => 'Order Operation Error'
    ];

    public static function getListingSyncStatusName($listingSyncStatusId)
    {
        return self::names[$listingSyncStatusId];
    }

    // create a function to get id from name
    public static function getListingSyncStatusId($listingSyncStatusName)
    {
        return array_search($listingSyncStatusName, self::names);
    }
}
