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
                media(first: 249) {
                    nodes {
                        alt
                        mediaContentType
                        preview {
                            status
                            image {
                                url
                            }
                        }
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

    public static $updateProduct = <<<GQL
    mutation UpdateProductWithNewMedia(\$product: ProductUpdateInput!, \$media: [CreateMediaInput!]) {
      productUpdate(product: \$product, media: \$media) {
        product {
          id
            handle
            variants(first : 1) {
                nodes {
                    id
                }
            }
          media(first: 249) {
            nodes {
              alt
              mediaContentType
              preview {
                status
                image {
                    url
                }
              }
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
                    risk{
                        assessments{
                            facts{
                                description
                                sentiment
                            }
                            riskLevel
                        }
                        recommendation
                    }
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
                    shippingLine{
                        title
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

    // Query to get latest orders with a dynamic limit
    public static $getLatestOrders = <<<GQL
    query GetLatestOrders(\$first: Int!) {
        orders(first: \$first, sortKey: CREATED_AT, reverse: true) {
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
                    risk{
                        assessments{
                            facts{
                                description
                                sentiment
                            }
                            riskLevel
                        }
                        recommendation
                    }
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
                    shippingLine{
                        title
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
                image {
                    url
                }
            }
        }
    }
GQL;

    public static $updateCollection = <<<GQL
    mutation (\$collection : CollectionInput!) {
        collectionUpdate(input : \$collection) {
            collection {
                id
                image {
                    url
                }
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

    public static $cancelOrder = <<<GQL
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
    public static $getOrder = <<<GQL
    query getOrderId(\$query: String!) {
        orders(first: 1, query: \$query) {
            edges {
                node {
                    id
                    name
                    lineItems(first: 5) {
                        edges {
                            node {
                                id
                                title
                                quantity
                            }
                        }
                    }
                    transactions{
                      id
                      amount
                      kind
                      gateway
                    }
                }
            }
        }
    }
GQL;
    public static $getOrderTransaction = <<<GQL
    query getOrderTransactions(\$orderId: ID!) {
        order(id: \$orderId) {
            transactions {
                id
                kind
                gateway
                amount
                status
            }
        }
    }
GQL;
    public static $getOrderDetail = <<<GQL
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
              id
              title
              quantity
            }
          }
        }
        risk {
          recommendation
          assessments {
            provider {
              title
            }
            riskLevel
            facts {
              description
              sentiment
            }
          }
        }
      }
    }
GQL;


    public static $createRefund = <<<GQL
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

    public static $refundOrder = <<<GQL
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


    public static $getProducts = <<<GQL
    query GetProducts {
        products(first: 100) {
            nodes {
                id
                title
                variants (first: 10){
                    edges {
                        node {
                        id
                        title
                        sku
                        inventoryQuantity
                        }
                    }
                }
            }
            pageInfo {
                hasPreviousPage
                hasNextPage
                startCursor
                endCursor
            }
        }
    }
GQL;

    public static $getProductImages = <<<GQL
    query GetProductImages(\$id: ID!) {
        product(id: \$id) {
            id
            title
            media(first: 250) {
                nodes {
                    id
                    alt
                    mediaContentType
                    preview {
                        status
                        image {
                            url
                        }
                    }
                }
            }
        }
    }
GQL;


    public static $getProductsWithCursor = <<<GQL
    query GetProducts(\$cursor: String) {
        products(first: 100,after: \$cursor) {
            nodes {
                id
                title
                variants (first: 10){
                    edges {
                        node {
                        id
                        title
                        sku
                        inventoryQuantity
                        }
                    }
                }
            }
            pageInfo {
                hasPreviousPage
                hasNextPage
                startCursor
                endCursor
            }
        }
    }
GQL;

    public static $getProductById = <<<GQL
    query GetProductById (\$id : ID!) {
        product( id : \$id) {
            tags
        }
    }
GQL;
    public static $getCollectionByHandle = <<<GQL
    query GetCollectionByHandle(\$handle: String!) {
        collectionByHandle(handle: \$handle) {
            id
            title
            handle
            updatedAt
            ruleSet {
                appliedDisjunctively
                rules {
                    column
                    relation
                    condition
                }
            }
        }
    }
GQL;
    public static $pageCreate = <<<GQL
    mutation pageCreate(\$page: PageCreateInput!) {
        pageCreate(page: \$page) {
            page {
                id
                title
                handle
            }
            userErrors {
                field
                message
            }
        }
    }
GQL;

    public static $pageFetch = <<<GQL
query getPageByHandle (\$handle: String!) {
  pages (first: 1, query : \$handle) {
    edges {
      node {
        id
      }
    }
    pageInfo {
      endCursor
      hasNextPage
      hasPreviousPage
      startCursor
    }
  }
}
GQL;

    public static $pageUpdate = <<<GQL
    mutation pageUpdate(\$page: PageUpdateInput!, \$id: ID!) {
        pageUpdate(page: \$page, id: \$id) {
            page {
                id
                title
                handle
            }
        }
    }
GQL;
    public static $AddTags = <<<GQL
        mutation addTags(\$id: ID!, \$tags: [String!]!) {
          tagsAdd(id: \$id, tags: \$tags) {
            node {
              id
            }
            userErrors {
              message
            }
          }
        }
GQL;
    public static $removeTags = <<<GQL
        mutation removeTags(\$id: ID!, \$tags: [String!]!) {
          tagsRemove(id: \$id, tags: \$tags) {
            node {
              id
            }
            userErrors {
              message
            }
          }
        }
GQL;

    public static $productPublish = <<<GQL
        mutation productPublish(\$input: ProductPublishInput!) {
          productPublish(input: \$input) {
            product {
                id
            }
            shop {
                id
            }
            userErrors {
              field
              message
            }
          }
        }
GQL;

    public static $deleteProductImage = <<<GQL
        mutation productDeleteMedia(\$mediaIds: [ID!]!, \$productId: ID!) {
          productDeleteMedia(mediaIds: \$mediaIds, productId: \$productId) {
            deletedMediaIds
            deletedProductImageIds
            mediaUserErrors {
              field
              message
            }
            product {
              id
              title
              media(first: 5) {
                nodes {
                  alt
                  mediaContentType
                  status
                }
              }
            }
          }
        }
GQL;
}
