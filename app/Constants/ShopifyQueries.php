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

    
}