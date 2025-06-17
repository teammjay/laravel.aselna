<?php

namespace App\Utils\mail;


use App\Constants\HdTicketTypeCode;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ShopifyEmailParser extends ReceivedMessageParser
{
    public function __construct($message)
    {
        parent::__construct($message);
    }

    public function getMessage($text)
    {
        $res = parent::getMessage($text);

        if ($this->senderIsAccountOwner && str_contains($res, 'placed a new order with your store')) {
            $this->ticketTypeCode = HdTicketTypeCode::MARKETPLACE_NOTIFICATION;
        }

        return $res;
    }
}
