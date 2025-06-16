<?php

namespace App\Utils\mail\Extractors;


class AmazonOrderIdExtractor extends MessageEntityExtractor
{
    public function __construct($type,$regularExpression)
    {
        parent::__construct(
            $type,
            $regularExpression
        );
    }
}
