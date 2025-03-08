<?php

namespace App\Constants;

class ShopifyQueries
{
    public static string $saveProduct = <<<GQL
        mutation productCreate(\$input: ProductInput!) {
            productCreate(input: \$input) {
                product {
                    id
                    handle
                    status
                }
                userErrors {
                    field
                    message
                }
            }
        }
    GQL;

    public static string $createProductVariant = <<<GQL
        mutation productVariantCreate(\$input: ProductVariantInput!) {
            productVariantCreate(input: \$input) {
                productVariant {
                    id
                    sku
                    title
                    inventoryItem {
                        id
                    }
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;


}
