# Shopify New Store Setup Guide

This document outlines the mandatory steps and database configurations required to set up a new Shopify store for total synchronization in the Aselna system.

---

## 1. Shopify API Requirements
Before starting in Aselna, create a **Custom App** in your Shopify Admin and obtain:
*   **Admin API Access Token**: (Paste into `secret_key` in Aselna).
*   **Store URL**: e.g., `your-store-name.myshopify.com` (Paste into `service_url`).

---

## 2. Store Table Configuration (`store`)
Create a record in the `store` table with these critical settings:

| Field | Description | Recommendation |
| :--- | :--- | :--- |
| `id` | Unique UUID | Auto-generated |
| `name` | Internal display name | e.g. "Shopify US Store" |
| `store_type_id` | ID from `store_type` table | Link to type with code `shopify` |
| `secret_key` | Shopify Admin Access Token | `shpat_xxxxxxxxxxxx` |
| `service_url` | Shopify Store Domain | `https://your-store.myshopify.com` |
| `short_code` | Prefix for SKUs/Titles | e.g. `S1`, `US` |
| `auto_generate_listings`| Enables the sync engine | Set to `1` |
| `store_status` | Operational status | Set to `1` (Active) |
| `file_system_id` | Link to `file_systems` record | Required for image hosting |
| `default_listing_quantity`| Stock level sent to Shopify | e.g. `10` |
| `shopify_title_template` | Pattern for product titles | e.g. `#title_keyword1 #device_name #product_sku` |

---

## 3. Image Hosting Setup (`file_systems`)
Shopify requires publicly accessible URLs to download product images. You **must** configure an FTP/SFTP server in the `file_systems` table.

*   **host/port/login/password**: Your FTP credentials.
*   **root_path**: The directory on the server where images will be saved.
*   **url_base_path**: The public URL prefix that points to that root path (e.g., `https://cdn.yourdomain.com/store-images/`).

---

## 4. Category & Brand Mappings

### A. Store Category Mappings (`store_type_category`)
The system only syncs products belonging to categories mapped to the store's type.
*   Ensure the `category_id` (local Product Type) is linked to the `store_type_id`.

### B. Supported Device Types (`store_device_type`)
Define which device types this store will sell accessories for.
*   **Sample Entry**: `store_id: {ID}, device_type_id: {ID}, purpose: 'listing'`

---

## 5. Automated Collection Setup
The `ShopifyOnlySyncJob` automatically creates Collections on Shopify based on local data. Ensure these **Store Page Templates** exist:
*   `Brand`
*   `All Brands`
*   `Device`
*   `Product Type`
*   `Brand Devices`
*   `Brand Device Product Types`

If these are missing, collection generation will fail.

---

## 6. Verification Before Enabling
Before setting `auto_generate_listings = 1`, run these checks:
1.  **Check Credentials**: Ensure `secret_key` and `service_url` are valid.
2.  **Test FTP**: Manually verify the system can write to the FTP path and the resulting URL is reachable.
3.  **Map Products**: Use the `store_product` table to link existing catalog items to the new store.
4.  **Existing Data Check**: Refer to `SHOPIFY_SYNC_VERIFICATION.md` to ensure you aren't creating duplicates if the store was previously managed elsewhere.

---

## 7. Active Sync Jobs
Once setup is complete, the following jobs should be scheduled:
1.  **`app/Jobs/Sync/ShopifyOnlySyncJob.php`**: Runs periodically to sync Collections, Brands, and Navigation.
2.  **`app/Jobs/SubmitShopifyProductsJob.php`**: Runs periodically to push/update Products and Variants.
