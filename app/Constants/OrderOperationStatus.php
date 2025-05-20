<?php

namespace App\Constants;

class OrderOperationStatus
{
    const Created = 0;
    const LabelCreated = 1;
    const Error = 2;
    const LabelPrinted = 3;
    const Estimated = 4;
    const Accepted = 6;
    const InTransit = 7;
    const Delivered = 8;
    const DeliveryException = 9;
    const ReturningToSender = 10;
    const ReturnedToSender = 11;
    const Canceled = 12;
    const MarkAsShipped = 13;
    const Requested = 50;
    const Completed = 51;
    const Rejected = 55;


    const ESCALATED = 100;
    const PARTIAL_REFUND_OFFERED = 101;
    const PARTIAL_REFUND_ACCEPTED = 102;
    const PARTIAL_REFUND_DECLINED = 103;
    const REFUNDED = 104;
    const REPLACEMENT_OFFERED = 105;
    const REPLACEMENT_OFFER_ACCEPTED = 106;
    const REPLACEMENT_OFFER_DECLINED = 107;
    const REPLACEMENT_SHIPPED = 108;
    const REPLACEMENT_DELIVERED = 109;
    const REFUND_ERROR = 110;
    const PENDING = 111;


    const names = [
        self::Created => 'Created',
        self::LabelCreated => 'LabelCreated',
        self::Error => 'Error',
        self::LabelPrinted => 'LabelPrinted',
        self::Estimated => 'Estimated',
        self::Accepted => 'Accepted',
        self::InTransit => 'InTransit',
        self::Delivered => 'Delivered',
        self::DeliveryException => 'DeliveryException',
        self::ReturningToSender => 'ReturningToSender',
        self::ReturnedToSender => 'ReturnedToSender',
        self::Canceled => 'Canceled',
        self::MarkAsShipped => 'MarkAsShipped',
        self::Requested => 'Requested',
        self::Completed => 'Completed',
        self::Rejected => 'Rejected',

        self::ESCALATED => 'Escalated',
        self::PARTIAL_REFUND_OFFERED => 'PartialRefundOffered',
        self::PARTIAL_REFUND_ACCEPTED => 'PartialRefundAccepted',
        self::PARTIAL_REFUND_DECLINED => 'PartialRefundDeclined',
        self::REFUNDED => 'Refunded',
        self::REPLACEMENT_OFFERED => 'ReplacementOffered',
        self::REPLACEMENT_OFFER_ACCEPTED => 'ReplacementOfferAccepted',
        self::REPLACEMENT_OFFER_DECLINED => 'ReplacementOfferDeclined',
        self::REPLACEMENT_SHIPPED => 'ReplacementShipped',
        self::REPLACEMENT_DELIVERED => 'ReplacementDelivered',
        self::REFUND_ERROR => 'RefundError',
        self::PENDING => 'Pending'

    ];
}
