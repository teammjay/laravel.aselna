-- =====================================================================================
-- SubmitShopifyProductsJob dry run, 02: products the job would UPDATE on Shopify.
-- Read-only. Mirrors SubmitShopifyProductsJob::handle() "Handle Updated Products":
--   products JOIN manage_marketplace_products (upload_status 'success', this store)
--            JOIN store_product (this store)
--   active CEA product (levels 0-2, not 'ceabundle'), parent_id NULL, and
--   store_product.last_synch_date_time IS NULL
--     OR last_synch_date_time < store_product.updated_at
--     OR last_synch_date_time < products.updated_at
--   ORDER BY manage_marketplace_products.updated_at ASC LIMIT 200 per run.
-- A product with two success rows appears twice (the job updates it twice in one run).
-- =====================================================================================

SET @store_name := 'UZID';
SET @store_id := (SELECT s.id FROM store s JOIN store_type t ON t.id = s.store_type_id
                  WHERE t.code = 'shopify' AND s.name = @store_name LIMIT 1);

-- 1. Every row that qualifies, with why, and the rows the next run takes (first 200).
WITH l0 AS (SELECT id FROM product_type WHERE code = 'cea'),
     l1 AS (SELECT id FROM product_type WHERE parent_id IN (SELECT id FROM l0)),
     l2 AS (SELECT id FROM product_type WHERE parent_id IN (SELECT id FROM l1)),
     cea AS (SELECT id FROM l0 UNION SELECT id FROM l1 UNION SELECT id FROM l2),
     candidates AS (
         SELECT p.product_id, p.product_sku, p.product_title, p.updated_at AS product_updated_at,
                sp.id AS store_product_id, sp.updated_at AS store_product_updated_at, sp.last_synch_date_time,
                m.sku AS mmp_sku, m.marketplace_product_id, m.updated_at AS mmp_updated_at,
                ROW_NUMBER() OVER (ORDER BY m.updated_at ASC) AS run_position
         FROM products p
         JOIN manage_marketplace_products m ON m.product_id = p.product_id
              AND m.upload_status = 'success' AND m.store_id = @store_id
         JOIN store_product sp ON sp.product_id = p.product_id AND sp.store_id = @store_id
         WHERE p.product_type NOT IN (SELECT id FROM product_type WHERE code = 'ceabundle')
           AND p.product_type IN (SELECT id FROM cea)
           AND p.parent_id IS NULL
           AND p.product_status = 'active'
           AND (sp.last_synch_date_time < sp.updated_at
                OR sp.last_synch_date_time < p.updated_at
                OR sp.last_synch_date_time IS NULL)
     )
SELECT c.run_position,
       c.run_position <= 200                                  AS in_next_run,
       c.product_sku,
       c.mmp_sku,
       c.marketplace_product_id,
       CASE WHEN c.last_synch_date_time IS NULL THEN 'never synced'
            WHEN c.last_synch_date_time < c.product_updated_at AND c.last_synch_date_time < c.store_product_updated_at
                 THEN 'product and store_product changed'
            WHEN c.last_synch_date_time < c.product_updated_at THEN 'product changed'
            ELSE 'store_product changed' END                  AS reason,
       c.last_synch_date_time,
       c.product_updated_at,
       c.store_product_updated_at,
       -- updateProduct() crashes ("Attempt to read property id on null") when the listing item has no
       -- main image row and the product has no main_image = 1 image: the run stops there.
       (NOT EXISTS (SELECT 1 FROM product_images pi WHERE pi.product_id = c.product_id AND pi.main_image = 1)
        AND NOT EXISTS (SELECT 1 FROM listing_item li JOIN listing_item_product_images l ON l.listing_item_id = li.id
                        WHERE li.store_product_id = c.store_product_id AND l.main_image = 1)) AS will_crash_no_main_image,
       (SELECT COUNT(*) FROM manage_marketplace_products m2
        WHERE m2.store_id = @store_id AND m2.upload_status = 'success' AND m2.product_id = c.product_id) AS success_rows_for_product
FROM candidates c
ORDER BY c.run_position;

-- 2. Summary
WITH l0 AS (SELECT id FROM product_type WHERE code = 'cea'),
     l1 AS (SELECT id FROM product_type WHERE parent_id IN (SELECT id FROM l0)),
     l2 AS (SELECT id FROM product_type WHERE parent_id IN (SELECT id FROM l1)),
     cea AS (SELECT id FROM l0 UNION SELECT id FROM l1 UNION SELECT id FROM l2),
     candidates AS (
         SELECT p.product_id, p.updated_at AS pu, sp.updated_at AS spu, sp.last_synch_date_time AS ls
         FROM products p
         JOIN manage_marketplace_products m ON m.product_id = p.product_id
              AND m.upload_status = 'success' AND m.store_id = @store_id
         JOIN store_product sp ON sp.product_id = p.product_id AND sp.store_id = @store_id
         WHERE p.product_type NOT IN (SELECT id FROM product_type WHERE code = 'ceabundle')
           AND p.product_type IN (SELECT id FROM cea)
           AND p.parent_id IS NULL
           AND p.product_status = 'active'
           AND (sp.last_synch_date_time < sp.updated_at OR sp.last_synch_date_time < p.updated_at
                OR sp.last_synch_date_time IS NULL)
     )
SELECT COUNT(*)                         AS update_rows,
       COUNT(DISTINCT product_id)       AS update_products,
       LEAST(COUNT(*), 200)             AS updated_in_next_run,
       CEIL(COUNT(*) / 200)             AS runs_needed,
       SUM(ls IS NULL)                  AS never_synced,
       SUM(ls < pu)                     AS product_changed,
       SUM(ls < spu)                    AS store_product_changed
FROM candidates;
