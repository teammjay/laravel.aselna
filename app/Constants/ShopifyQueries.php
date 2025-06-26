<?php

namespace App\Constants;

class ShopifyQueries
{
public static $saveProduct = <<<GQL
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
    query getOrders(\$first: Int!, \$query:String) {
        orders(first: \$first, query: \$query) {
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
                    shippingAddress{
                        firstName
                        lastName
                        address1
                        address2
                        city
                        country
                        countryCodeV2
                        zip
                        phone
                        province
                        provinceCode
                    }
                    lineItems(first: \$first){
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
                            taxLines(first: \$first){
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

public static $createProductVariant = <<<GQL
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

public static $getProductVariant = <<<GQL
    query(\$id: ID!) {
      productVariant(id: \$id) {
        inventoryItem {
          id
        }
      }
    }
GQL;

public static $getLocation = <<<GQL
    query {
      locations(first: 5) {
        edges {
          node {
            id
          }
        }
      }
    }
GQL;

public static $inventoryAdjustQuantities = <<<GQL
    mutation inventoryAdjustQuantities(\$input: InventoryAdjustQuantitiesInput!) {
      inventoryAdjustQuantities(input: \$input) {
        userErrors {
          field
          message
        }
        inventoryAdjustmentGroup {
          changes {
            delta
          }
        }
      }
    }
GQL;

public static $productVariantUpdate = <<<GQL
    mutation UpdateProductVariants(\$productId: ID!, \$variant: [ProductVariantsBulkInput!]!) {
      productVariantsBulkUpdate(productId: \$productId, variants: \$variant) {
        productVariants {
          id
          price
        }
        userErrors {
          field
          message
        }
      }
    }
GQL;

public static $getFullfillmentOrders = <<<GQL
    query getFulfillmentOrders(\$id: ID!) {
      order(id: \$id) {
        id
        fulfillmentOrders(first: 5) {
          nodes {
            id
            lineItems(first: 10) {
              edges {
                node {
                  id
                  lineItem {
                    id
                    title
                    quantity
                  }
                }
              }
            }
          }
        }
      }
    }
GQL;

public static $cancelFullfillment = <<<GQL
    mutation fulfillmentCancel(\$id: ID!) {
      fulfillmentCancel(id: \$id) {
        fulfillment {
          id
          status
        }
        userErrors {
          field
          message
        }
      }
    }
GQL;

public static $createFullfillment = <<<GQL
    mutation fulfillmentCreate(\$fulfillment: FulfillmentInput!) {
      fulfillmentCreate(fulfillment: \$fulfillment) {
        fulfillment {
          id
          status
          totalQuantity
          trackingInfo {
            number
            url
            company
          }
        }
        userErrors {
          field
          message
        }
      }
    }
GQL;

public static $cancelOrder =<<<GQL
    mutation orderCancel(\$notifyCustomer: Boolean, \$orderId: ID!, \$reason: OrderCancelReason!, \$refund: Boolean!, \$restock: Boolean!, \$staffNote: String) {
        orderCancel(notifyCustomer: \$notifyCustomer, orderId: \$orderId, reason: \$reason, refund: \$refund, restock: \$restock, staffNote: \$staffNote) {
            job {
              done
              id
            }
            orderCancelUserErrors {
              code
              field
              message
            }
        }
    }
GQL;

public static $getOrderDetail =<<<GQL
    query getOrder(\$id: ID!) {
      order(id: \$id) {
        id
        name
        createdAt
        displayFinancialStatus
        displayFulfillmentStatus
        fulfillments {
          id
          status
          trackingInfo {
            company
            number
            url
          }
        }
        lineItems(first: 10) {
          edges {
            node {
              title
              quantity
            }
          }
        }
      }
    }
GQL;


public static $createRefund =<<<GQL
    mutation RefundLineItem(\$input: RefundInput!) {
      refundCreate(input: \$input) {
        refund {
          id
          totalRefundedSet {
            presentmentMoney {
              amount
              currencyCode
            }
          }
        }
        userErrors {
          field
          message
        }
      }
    }
GQL;

public static $refundOrder =<<<GQL
    query refund(\$id: ID!) {
      refund(id: \$id) {
        duties {
          originalDuty {
            countryCodeOfOrigin
          }
          amountSet {
            shopMoney {
              amount
            }
          }
        }
      }
    }
GQL;

}