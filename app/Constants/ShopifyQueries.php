<?php

namespace App\Constants;

class ShopifyQueries
{
    public static $saveProduct = <<<GQL
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



}
