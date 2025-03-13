<?php

namespace App\Constants;

class ShopifyQueries
{
    public static string $saveProduct = <<<GQL
        mutation (\$productSet : ProductSetInput!) {
            productSet(input : \$productSet) {
                product {
                    id
                    handle
                    variants(first : 1) {
                        nodes {
                            id
                        }
                    }
                }
                userErrors {
                    code
                    field
                    message
                }
            }
        }
    GQL;

    // Query to get orders with a dynamic limit
    public static $getOrders = <<<GQL
        query getOrders(\$first: Int!) {
            orders(first: \$first) {
                edges {
                    node {
                        id
                        name
                        email
                        phone
                        poNumber
                        totalPrice
                        createdAt
                        updatedAt
                        totalWeight
                        displayFinancialStatus
                        displayFulfillmentStatus
                        totalPriceSet{
                            shopMoney{
                                amount
                            }
                        }
                        additionalFees {
                          name
                          price{
                            shopMoney{
                                amount
                            }
                          }
                          taxLines{
                            rate
                            title
                          }
                        }
                        customer{
                            id
                            displayName
                            email
                            firstName
                            lastName
                        }
                        lineItems(first: 10){
                            nodes{
                                id
                                title
                                name
                                quantity
                                sku
                                currentQuantity
                                variant{
                                    price
                                }
                                taxLines(first: 10){
                                    title
                                    price
                                    source
                                    rate
                                }
                            }
                        }
                    }
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

    public static $orderFields = <<<GQL
    {
      __type(name: "Order") {
        fields {
          name
          type {
            name
            kind
            fields {
              name
            }
          }
        }
      }
    }
    GQL;
    public static $createCollection = <<<GQL
        mutation (\$collection : CollectionInput!) {
            collectionCreate(input : \$collection) {
                collection {
                    id
                }
            }
        }
    GQL;

    public static $getPublications = <<<GQL
        query {
            publications (first: 10) {
                edges {
                    node {
                        id
                    }
                }
            }
        }
    GQL;

}
