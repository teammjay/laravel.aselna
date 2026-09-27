-- =====================================================================================
-- SubmitShopifyProductsJob dry run, 03: products the job would DISCONTINUE (set to DRAFT on Shopify).
-- Read-only. Two parts of SubmitShopifyProductsJob::handle():
--
-- A. "Handle Discontinued Products": products JOIN store_product (this store),
--    with a 'success' manage_marketplace_products row for this store (EXISTS, so a product with two
--    success rows is listed once), CEA product (levels 0-2, not 'ceabundle'), parent_id NULL,
--    product_status IN ('discontinued', 'temporaryDisabled', 'draft'),
--    last_synch_date_time IS NULL OR last_synch_date_time < GREATEST(store_product.updated_at, products.updated_at).
--    No limit. discontinueProduct() sets the product to DRAFT on Shopify and the listing item to Suspended.
--
-- B. "Handle Deleted Products": 'success' manage_marketplace_products rows of this store whose
--    product_id no longer exists in products. discontinueDeletedProduct() sets them to DRAFT on
--    Shopify (or just marks the row 'discontinued' when marketplace_product_id is empty).
-- =====================================================================================

SET @store_name := 'UZID';
SET @store_id := (SELECT s.id FROM store s JOIN store_type t ON t.id = s.store_type_id
                  WHERE t.code = 'shopify' AND s.name = @store_name LIMIT 1);

-- A1. Discontinued / temporarily disabled / draft products
WITH l0 AS (SELECT id FROM product_type WHERE code = 'cea'),
     l1 AS (SELECT id FROM product_type WHERE parent_id IN (SELECT id FROM l0)),
     l2 AS (SELECT id FROM product_type WHERE parent_id IN (SELECT id FROM l1)),
     cea AS (SELECT id FROM l0 UNION SELECT id FROM l1 UNION SELECT id FROM l2)
SELECT p.product_sku,
       p.product_title,
       p.product_status,
       p.updated_at                AS product_updated_at,
       sp.updated_at               AS store_product_updated_at,
       sp.last_synch_date_time,
       (SELECT m.marketplace_product_id FROM manage_marketplace_products m
        WHERE m.product_id = p.product_id AND m.store_id = @store_id AND m.upload_status = 'success'
        ORDER BY m.id LIMIT 1)                                   AS marketplace_product_id,
       'set to DRAFT on Shopify, listing item Suspended'         AS action,
       (SELECT COUNT(*) FROM listing_item li WHERE li.store_product_id = sp.id) AS listing_items
FROM products p
JOIN store_product sp ON sp.product_id = p.product_id AND sp.store_id = @store_id
WHERE EXISTS (SELECT 1 FROM manage_marketplace_products m
              WHERE m.product_id = p.product_id AND m.store_id = @store_id AND m.upload_status = 'success')
  AND p.product_type NOT IN (SELECT id FROM product_type WHERE code = 'ceabundle')
  AND p.product_type IN (SELECT id FROM cea)
  AND p.parent_id IS NULL
  AND p.product_status IN ('discontinued', 'temporaryDisabled', 'draft')
  AND (sp.last_synch_date_time < GREATEST(sp.updated_at, p.updated_at) OR sp.last_synch_date_time IS NULL)
ORDER BY p.created_at DESC;

-- A2. Summary of A
WITH l0 AS (SELECT id FROM product_type WHERE code = 'cea'),
     l1 AS (SELECT id FROM product_type WHERE parent_id IN (SELECT id FROM l0)),
     l2 AS (SELECT id FROM product_type WHERE parent_id IN (SELECT id FROM l1)),
     cea AS (SELECT id FROM l0 UNION SELECT id FROM l1 UNION SELECT id FROM l2)
SELECT p.product_status,
       COUNT(*) AS products_set_to_draft_on_shopify
FROM products p
JOIN store_product sp ON sp.product_id = p.product_id AND sp.store_id = @store_id
WHERE EXISTS (SELECT 1 FROM manage_marketplace_products m
              WHERE m.product_id = p.product_id AND m.store_id = @store_id AND m.upload_status = 'success')
  AND p.product_type NOT IN (SELECT id FROM product_type WHERE code = 'ceabundle')
  AND p.product_type IN (SELECT id FROM cea)
  AND p.parent_id IS NULL
  AND p.product_status IN ('discontinued', 'temporaryDisabled', 'draft')
  AND (sp.last_synch_date_time < GREATEST(sp.updated_at, p.updated_at) OR sp.last_synch_date_time IS NULL)
GROUP BY p.product_status;

-- B. Products deleted from the products table but still 'success' on this store
SELECT m.product_code,
       m.sku,
       m.product_id,
       m.marketplace_product_id,
       CASE WHEN m.marketplace_product_id IS NULL OR m.marketplace_product_id = ''
            THEN 'row marked discontinued, no Shopify call'
            ELSE 'set to DRAFT on Shopify, row marked discontinued' END AS action
FROM manage_marketplace_products m
WHERE m.store_id = @store_id
  AND m.upload_status = 'success'
  AND m.product_id NOT IN (SELECT product_id FROM products);
