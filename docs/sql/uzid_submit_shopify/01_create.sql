-- =====================================================================================
-- SubmitShopifyProductsJob dry run, 01: products the job would CREATE on Shopify.
-- Read-only. Mirrors SubmitShopifyProductsJob::handle() "Handle Created Products":
--   active CEA product (levels 0-2, not 'ceabundle'), parent_id NULL,
--   no manage_marketplace_products row with upload_status = 'success' for this store,
--   ORDER BY products.created_at DESC LIMIT 100 per run.
-- Not limited to products already in the store: createProduct() -> addListingItem() creates the
-- store_product and listing_item when missing, then productSet creates a NEW Shopify product.
-- =====================================================================================

SET @store_name := 'UZID';
SET @store_id := (SELECT s.id FROM store s JOIN store_type t ON t.id = s.store_type_id
                  WHERE t.code = 'shopify' AND s.name = @store_name LIMIT 1);

-- 1. Every product that qualifies, with the ones the next run takes (first 100) marked.
WITH l0 AS (SELECT id FROM product_type WHERE code = 'cea'),
     l1 AS (SELECT id FROM product_type WHERE parent_id IN (SELECT id FROM l0)),
     l2 AS (SELECT id FROM product_type WHERE parent_id IN (SELECT id FROM l1)),
     cea AS (SELECT id FROM l0 UNION SELECT id FROM l1 UNION SELECT id FROM l2),
     candidates AS (
         SELECT p.product_id, p.product_sku, p.product_title, p.product_status, p.created_at,
                ROW_NUMBER() OVER (ORDER BY p.created_at DESC) AS run_position
         FROM products p
         WHERE p.product_type NOT IN (SELECT id FROM product_type WHERE code = 'ceabundle')
           AND p.product_type IN (SELECT id FROM cea)
           AND p.parent_id IS NULL
           AND p.product_status = 'active'
           AND p.product_id NOT IN (SELECT m.product_id FROM manage_marketplace_products m
                                    WHERE m.store_id = @store_id AND m.upload_status = 'success')
     )
SELECT c.run_position,
       c.run_position <= 100                                        AS in_next_run,
       c.product_sku,
       c.product_title,
       c.created_at,
       sp.id IS NOT NULL                                            AS store_product_exists,
       (SELECT COUNT(*) FROM listing_item li WHERE li.store_product_id = sp.id) AS listing_items,
       -- A listing item that already has a Shopify id: creating it again makes a DUPLICATE on Shopify.
       EXISTS (SELECT 1 FROM listing_item li WHERE li.store_product_id = sp.id
               AND li.store_sku IS NOT NULL AND li.store_sku <> '')   AS already_on_shopify_risk_duplicate,
       (SELECT GROUP_CONCAT(DISTINCT m.upload_status) FROM manage_marketplace_products m
        WHERE m.store_id = @store_id AND m.product_id = c.product_id)  AS other_mmp_status_for_store,
       (SELECT COUNT(*) FROM product_images pi WHERE pi.product_id = c.product_id) AS product_images
FROM candidates c
LEFT JOIN store_product sp ON sp.product_id = c.product_id AND sp.store_id = @store_id
ORDER BY c.run_position;

-- 2. Summary
WITH l0 AS (SELECT id FROM product_type WHERE code = 'cea'),
     l1 AS (SELECT id FROM product_type WHERE parent_id IN (SELECT id FROM l0)),
     l2 AS (SELECT id FROM product_type WHERE parent_id IN (SELECT id FROM l1)),
     cea AS (SELECT id FROM l0 UNION SELECT id FROM l1 UNION SELECT id FROM l2),
     candidates AS (
         SELECT p.product_id
         FROM products p
         WHERE p.product_type NOT IN (SELECT id FROM product_type WHERE code = 'ceabundle')
           AND p.product_type IN (SELECT id FROM cea)
           AND p.parent_id IS NULL
           AND p.product_status = 'active'
           AND p.product_id NOT IN (SELECT m.product_id FROM manage_marketplace_products m
                                    WHERE m.store_id = @store_id AND m.upload_status = 'success')
     )
SELECT COUNT(*)                                        AS create_candidates,
       LEAST(COUNT(*), 100)                            AS created_in_next_run,
       CEIL(COUNT(*) / 100)                            AS runs_needed,
       SUM(sp.id IS NOT NULL)                          AS already_a_store_product,
       SUM(sp.id IS NULL)                              AS store_product_will_be_created,
       SUM(EXISTS (SELECT 1 FROM listing_item li WHERE li.store_product_id = sp.id
                   AND li.store_sku IS NOT NULL AND li.store_sku <> '')) AS risk_duplicate_on_shopify
FROM candidates c
LEFT JOIN store_product sp ON sp.product_id = c.product_id AND sp.store_id = @store_id;
