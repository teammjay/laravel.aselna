# Shopify Store Sync Verification Guide

This document outlines the database tables and sample queries required to verify data consistency before enabling Shopify synchronization for a new store. Use these checks to prevent duplicate creation of products, devices, and images that may have already been synced by another system.

---

## 1. Core Synchronization Tables

### A. Product Mappings (`manage_marketplace_products`)
This table maps your local `product_id` to Shopify's unique Product and Variant IDs. If a record exists here with `upload_status = 'success'`, the system will **update** instead of **create**.

*   **Action:** Ensure all products already on Shopify have entries here for the new `store_id`.
*   **Sample Query:**
    ```sql
    -- Check for products synced to a specific store
    SELECT product_id, sku, marketplace_product_id, marketplace_variant_id, upload_status 
    FROM manage_marketplace_products 
    WHERE store_id = {YOUR_NEW_STORE_ID};

    -- Verify if a specific SKU exists in the mapping
    SELECT * FROM manage_marketplace_products 
    WHERE sku = 'ABC-123' AND store_id = {YOUR_NEW_STORE_ID};
    ```

### B. Store Product Relationship (`store_product`)
Links the global catalog to a specific store and tracks the last synchronization timestamp.

*   **Action:** Verify that records exist for each product you intend to sync. The `last_synch_date_time` should be set if you want the system to treat them as "already processed".
*   **Sample Query:**
    ```sql
    -- Count products linked to the store
    SELECT count(*) FROM store_product WHERE store_id = {YOUR_NEW_STORE_ID};

    -- Find products that have never been synced
    SELECT * FROM store_product 
    WHERE store_id = {YOUR_NEW_STORE_ID} AND last_synch_date_time IS NULL;
    ```

### C. Listing Items (`listing_items`)
Stores the actual listing data, including the Shopify handle (`store_url`) and combined IDs (`store_sku`).

*   **Action:** Check that `store_sku` (format: `product_id-variant_id`) and `store_url` match the actual Shopify product handle.
*   **Sample Query:**
    ```sql
    -- Check listing details for a store
    SELECT li.id, li.store_sku, li.store_url, sp.product_id 
    FROM listing_items li
    JOIN store_product sp ON li.store_product_id = sp.id
    WHERE sp.store_id = {YOUR_NEW_STORE_ID};
    ```

---

## 2. Media & Image Tables

### A. Listing Item Images (`listing_item_product_images`)
Maps local `product_image_id` to Shopify's `MediaImage` IDs (`reference_id`).

*   **Action:** If images are already on Shopify, populate `reference_id` to prevent the system from re-uploading them.
*   **Sample Query:**
    ```sql
    -- Check image references for a specific listing
    SELECT lipi.id, lipi.reference_id, lipi.path, pi.product_id
    FROM listing_item_product_images lipi
    JOIN product_images pi ON lipi.product_image_id = pi.id
    WHERE lipi.listing_item_id = {YOUR_LISTING_ITEM_ID};
    ```

### B. Generated Images (`generate_product_images`)
Tracks local image processing for a store.

*   **Action:** Check if images have already been generated/uploaded to the store's FTP path.
*   **Sample Query:**
    ```sql
    SELECT * FROM generate_product_images 
    WHERE product_id = '{PRODUCT_UUID}' AND path LIKE '%/store-images/{STORE_ID}/%';
    ```

---

## 3. Collections & Pages (`store_navigation_page`)

The system creates Shopify Collections for Brands, Devices, and Product Types.

*   **Action:** Verify that `page_id` (Shopify Collection ID) and `url_path` (Handle) are correctly populated.
*   **Sample Query:**
    ```sql
    -- Check synced collections/pages for a store
    SELECT page_type, title, url_path, page_id, device_id, product_type_id 
    FROM store_navigation_page 
    WHERE store_id = {YOUR_NEW_STORE_ID};
    ```

---

## 4. Device & Product Links

Synchronization relies on `device_interface` and `product_interface` to build tag-based collection rules in Shopify.

*   **Action:** Ensure your device and product interfaces are consistent. If a product is missing an interface, it won't appear in the automated "Device Accessories" collections.
*   **Sample Query:**
    ```sql
    -- Check interfaces for a product
    SELECT * FROM product_interface WHERE product_id = '{PRODUCT_UUID}';

    -- Check interfaces for a device
    SELECT * FROM device_interface WHERE device_id = {DEVICE_ID};
    ```

---

## 5. Summary Verification Query

Run this query to get a bird's-eye view of a product's sync status for your new store:

```sql
SELECT 
    p.product_sku,
    mmp.marketplace_product_id,
    mmp.upload_status,
    li.store_sku,
    li.store_url,
    sp.last_synch_date_time
FROM products p
LEFT JOIN store_product sp ON p.product_id = sp.product_id AND sp.store_id = {STORE_ID}
LEFT JOIN manage_marketplace_products mmp ON p.product_id = mmp.product_id AND mmp.store_id = {STORE_ID}
LEFT JOIN listing_items li ON li.store_product_id = sp.id
WHERE p.product_sku = '{YOUR_TEST_SKU}';
```

If `mmp.marketplace_product_id` is present and `mmp.upload_status` is `success`, the sync engine will treat it as an existing item.
