-- =====================================================================================
-- SubmitShopifyProductsJob dry run, 00: store settings and the values the job derives.
-- Read-only (SET + SELECT only). Run each file on its own; change @store_name for another store.
--
-- The job does NOT use device ratings or device types. What it does use:
--   * store.store_status <> 0 and store.auto_generate_listings = 1  (otherwise the store is skipped)
--   * store.product_alt_image_number  (N-th alternate of the MAIN image is pushed; 0/NULL = original)
--   * store.file_system_id            (no file system = no images generated/pushed)
--   * CEA product types, 3 levels deep, minus the 'ceabundle' type   (SubmitShopifyProductsJob::handle)
-- Note: when APP_ENV=local the job only processes the store named 'odemobile-c'.
-- =====================================================================================

SET @store_name := 'UZID';
SET @store_id := (SELECT s.id FROM store s JOIN store_type t ON t.id = s.store_type_id
                  WHERE t.code = 'shopify' AND s.name = @store_name LIMIT 1);

-- 1. Store row and whether the job processes it at all
SELECT s.id,
       s.name,
       s.store_status,
       s.auto_generate_listings,
       s.product_alt_image_number,
       GREATEST(0, COALESCE(s.product_alt_image_number, 0))        AS alt_image_number_used,
       s.file_system_id,
       fs.url_base_path,
       (s.store_status <> 0 AND COALESCE(s.auto_generate_listings, 0) = 1) AS job_processes_store
FROM store s
LEFT JOIN file_systems fs ON fs.id = s.file_system_id
WHERE s.id = @store_id;

-- 2. CEA product types the job uses (levels 0-2 only; deeper levels are NOT included by the job)
WITH l0 AS (SELECT id FROM product_type WHERE code = 'cea'),
     l1 AS (SELECT id FROM product_type WHERE parent_id IN (SELECT id FROM l0)),
     l2 AS (SELECT id FROM product_type WHERE parent_id IN (SELECT id FROM l1)),
     l3 AS (SELECT id FROM product_type WHERE parent_id IN (SELECT id FROM l2))
SELECT (SELECT COUNT(*) FROM l0) AS level0,
       (SELECT COUNT(*) FROM l1) AS level1,
       (SELECT COUNT(*) FROM l2) AS level2,
       (SELECT COUNT(*) FROM l3) AS level3_not_used_by_job,
       (SELECT GROUP_CONCAT(id) FROM product_type WHERE code = 'ceabundle') AS excluded_bundle_type_ids;

-- 3. Marketplace product rows for the store
SELECT upload_status, COUNT(*) AS rows_, COUNT(DISTINCT product_id) AS products
FROM manage_marketplace_products
WHERE store_id = @store_id
GROUP BY upload_status;
