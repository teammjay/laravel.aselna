<?php

namespace App\Constants;

class StorePageType
{
    const AboutUs = 0;
    const ContactUs = 1;
    const ShippingHandling = 2;
    const ReturnPolicy = 3;
    const PaymentMethod = 4;
    const SalesTax = 5;
    const Warranty = 6;
    const Faq = 7;
    const Feedback = 8;
    const ItemsForSale = 9;

    public static function getPageTypes(): array
    {
        return [
            self::AboutUs,
            self::ContactUs,
            self::ShippingHandling,
            self::ReturnPolicy,
            self::PaymentMethod,
            self::SalesTax,
            self::Warranty,
            self::Faq,
            self::Feedback,
            self::ItemsForSale,
        ];
    }

    public static function getPageTypeName(int $type): string
    {
        $types = [
            self::AboutUs => 'About Us',
            self::ContactUs => 'Contact Us',
            self::ShippingHandling => 'Shipping & Handling',
            self::ReturnPolicy => 'Return Policy',
            self::PaymentMethod => 'Payment Method',
            self::SalesTax => 'Sales Tax',
            self::Warranty => 'Warranty',
            self::Faq => 'FAQ',
            self::Feedback => 'Feedback',
            self::ItemsForSale => 'Items For Sale',
        ];

        return $types[$type] ?? 'Unknown';
    }
}
