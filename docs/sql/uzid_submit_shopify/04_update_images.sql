-- =====================================================================================
-- SubmitShopifyProductsJob dry run, 04: IMAGE changes. Images are only touched inside
-- updateProduct(), so everything here is limited to the products 02_update.sql selects.
-- Read-only.
--
-- Which file a store pushes (ShopifyImageUtility::resolveImage):
--   MAIN image (main_image = 1) and store.product_alt_image_number = N > 0:
--     the N-th alternate of that image, ordered by version_number, sort_order, id;
--     the original when there are fewer than N alternates or that alternate has no sys_file.
--   every other image: the original.
-- Listing item: updateProduct() uses the store product's first listing item (no ORDER BY in the
--   job; MIN(id) below, which is what MySQL returns for it in practice).
--
-- Covered, in the order updateProduct() runs them:
--   (a) handleOldImages():       listing image rows with product_image_id NULL are deleted (DB only).
--   (b) new media:               product images with no listing image row -> sent to Shopify as NEW media.
--   (c) removed images:          listing image rows whose product image no longer exists on the product
--                                -> Shopify media deleted (when reference_id is set) and the row deleted.
--   (d) updateChangedImages():   listing image rows (reference_id set) whose product image, or the selected
--                                alternate, was updated after the row -> Shopify media REPLACED with the
--                                selected file.
-- Not predictable from the DB (needs the live Shopify media): handleOldImages() re-mapping Shopify media
--   by alt text, deleteOldProductImages() (removes Shopify media no listing image row knows by url or
--   reference_id), and the featured image change.
-- =====================================================================================

SET @store_name := 'UZID';
SET @store_id := (SELECT s.id FROM store s JOIN store_type t ON t.id = s.store_type_id
                  WHERE t.code = 'shopify' AND s.name = @store_name LIMIT 1);
SET @alt_n := (SELECT GREATEST(0, COALESCE(product_alt_image_number, 0)) FROM store WHERE id = @store_id);

-- ---------------------------------------------------------------------------------
-- Detail: one row per image action. Filter on `action` or add LIMIT as needed.
-- ---------------------------------------------------------------------------------
WITH l0 AS (SELECT id FROM product_type WHERE code = 'cea'),
     l1 AS (SELECT id FROM product_type WHERE parent_id IN (SELECT id FROM l0)),
     l2 AS (SELECT id FROM product_type WHERE parent_id IN (SELECT id FROM l1)),
     cea AS (SELECT id FROM l0 UNION SELECT id FROM l1 UNION SELECT id FROM l2),
     upd_rows AS (
         SELECT p.product_id, p.product_sku, sp.id AS store_product_id,
                ROW_NUMBER() OVER (ORDER BY m.updated_at ASC) AS run_position
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
     ),
     upd AS (
         SELECT product_id, product_sku, store_product_id, MIN(run_position) AS run_position,
                (SELECT MIN(li.id) FROM listing_item li WHERE li.store_product_id = upd_rows.store_product_id) AS listing_item_id
         FROM upd_rows
         GROUP BY product_id, product_sku, store_product_id
     ),
     alt_ranked AS (
         SELECT a.id, a.main_image_id, a.image, a.updated_at,
                ROW_NUMBER() OVER (PARTITION BY a.main_image_id ORDER BY a.version_number, a.sort_order, a.id) AS rn
         FROM product_image_alternatives a
     ),
     -- The alternate the store pushes for each main image (only when it has a file).
     selected_alt AS (
         SELECT ar.main_image_id, ar.id AS alt_id, ar.updated_at AS alt_updated_at
         FROM alt_ranked ar
         JOIN sys_file sf ON sf.id = ar.image
         WHERE ar.rn = @alt_n
     ),
     -- Every product image of the updated products with the file this store pushes for it.
     img AS (
         SELECT u.product_id, u.product_sku, u.run_position, u.listing_item_id,
                pi.id AS product_image_id, pi.main_image, pi.updated_at AS image_updated_at,
                sa.alt_id, sa.alt_updated_at,
                (sa.alt_id IS NOT NULL OR EXISTS (SELECT 1 FROM sys_file sf WHERE sf.id = pi.image)) AS has_file
         FROM upd u
         JOIN product_images pi ON pi.product_id = u.product_id
         LEFT JOIN selected_alt sa ON sa.main_image_id = pi.id AND pi.main_image = 1
     )
-- (a) rows without a product image
SELECT u.run_position, u.product_sku, '(a) listing image row deleted (no product_image_id)' AS action,
       l.id AS listing_image_id, l.reference_id, NULL AS product_image_id, NULL AS main_image,
       NULL AS file_pushed, l.updated_at AS pushed_at, NULL AS changed_at
FROM upd u
JOIN listing_item_product_images l ON l.listing_item_id = u.listing_item_id
WHERE l.product_image_id IS NULL

UNION ALL
-- (b) new media: product image with no listing image row on the listing item
SELECT i.run_position, i.product_sku, '(b) NEW media added on Shopify', NULL, NULL,
       i.product_image_id, i.main_image,
       IF(i.alt_id IS NOT NULL, CONCAT('alternate ', i.alt_id), 'original'), NULL, NULL
FROM img i
WHERE i.has_file
  AND NOT EXISTS (SELECT 1 FROM listing_item_product_images l
                  WHERE l.listing_item_id = i.listing_item_id AND l.product_image_id = i.product_image_id)

UNION ALL
-- (c) listing image rows whose product image is gone from the product
SELECT u.run_position, u.product_sku,
       IF(l.reference_id IS NOT NULL AND l.reference_id <> '', '(c) Shopify media DELETED + row deleted',
          '(c) row deleted (no Shopify media)'),
       l.id, l.reference_id, l.product_image_id, l.main_image, NULL, l.updated_at, NULL
FROM upd u
JOIN listing_item_product_images l ON l.listing_item_id = u.listing_item_id
WHERE l.product_image_id IS NOT NULL
  AND l.product_image_id NOT IN (SELECT pi.id FROM product_images pi WHERE pi.product_id = u.product_id)

UNION ALL
-- (d) updateChangedImages: image or selected alternate newer than the listing image row
SELECT i.run_position, i.product_sku,
       CONCAT('(d) Shopify media REPLACED: ',
              CASE WHEN l.updated_at < i.image_updated_at AND i.alt_id IS NOT NULL AND l.updated_at < i.alt_updated_at
                        THEN 'image and alternate changed'
                   WHEN l.updated_at < i.image_updated_at THEN 'product image changed'
                   ELSE 'alternate changed' END),
       l.id, l.reference_id, i.product_image_id, i.main_image,
       IF(i.alt_id IS NOT NULL, CONCAT('alternate ', i.alt_id), 'original'),
       l.updated_at, GREATEST(i.image_updated_at, COALESCE(i.alt_updated_at, i.image_updated_at))
FROM img i
JOIN listing_item_product_images l ON l.listing_item_id = i.listing_item_id AND l.product_image_id = i.product_image_id
WHERE i.has_file
  AND l.reference_id IS NOT NULL AND l.reference_id <> ''
  AND (l.updated_at < i.image_updated_at OR (i.alt_id IS NOT NULL AND l.updated_at < i.alt_updated_at))
ORDER BY 1, 3;

-- ---------------------------------------------------------------------------------
-- Summary per action (same logic as above)
-- ---------------------------------------------------------------------------------
WITH l0 AS (SELECT id FROM product_type WHERE code = 'cea'),
     l1 AS (SELECT id FROM product_type WHERE parent_id IN (SELECT id FROM l0)),
     l2 AS (SELECT id FROM product_type WHERE parent_id IN (SELECT id FROM l1)),
     cea AS (SELECT id FROM l0 UNION SELECT id FROM l1 UNION SELECT id FROM l2),
     upd AS (
         SELECT DISTINCT p.product_id, sp.id AS store_product_id,
                (SELECT MIN(li.id) FROM listing_item li WHERE li.store_product_id = sp.id) AS listing_item_id
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
     ),
     alt_ranked AS (
         SELECT a.id, a.main_image_id, a.image, a.updated_at,
                ROW_NUMBER() OVER (PARTITION BY a.main_image_id ORDER BY a.version_number, a.sort_order, a.id) AS rn
         FROM product_image_alternatives a
     ),
     selected_alt AS (
         SELECT ar.main_image_id, ar.id AS alt_id, ar.updated_at AS alt_updated_at
         FROM alt_ranked ar JOIN sys_file sf ON sf.id = ar.image
         WHERE ar.rn = @alt_n
     ),
     img AS (
         SELECT u.product_id, u.listing_item_id, pi.id AS product_image_id, pi.updated_at AS image_updated_at,
                sa.alt_id, sa.alt_updated_at,
                (sa.alt_id IS NOT NULL OR EXISTS (SELECT 1 FROM sys_file sf WHERE sf.id = pi.image)) AS has_file
         FROM upd u
         JOIN product_images pi ON pi.product_id = u.product_id
         LEFT JOIN selected_alt sa ON sa.main_image_id = pi.id AND pi.main_image = 1
     )
SELECT '(a) rows deleted: no product_image_id' AS action, COUNT(*) AS images, COUNT(DISTINCT u.product_id) AS products
FROM upd u JOIN listing_item_product_images l ON l.listing_item_id = u.listing_item_id
WHERE l.product_image_id IS NULL
UNION ALL
SELECT '(b) NEW media added', COUNT(*), COUNT(DISTINCT i.product_id)
FROM img i
WHERE i.has_file AND NOT EXISTS (SELECT 1 FROM listing_item_product_images l
                                 WHERE l.listing_item_id = i.listing_item_id AND l.product_image_id = i.product_image_id)
UNION ALL
SELECT '(c) removed (image gone from product)', COUNT(*), COUNT(DISTINCT u.product_id)
FROM upd u JOIN listing_item_product_images l ON l.listing_item_id = u.listing_item_id
WHERE l.product_image_id IS NOT NULL
  AND l.product_image_id NOT IN (SELECT pi.id FROM product_images pi WHERE pi.product_id = u.product_id)
UNION ALL
SELECT '(d) REPLACED on Shopify', COUNT(*), COUNT(DISTINCT i.product_id)
FROM img i
JOIN listing_item_product_images l ON l.listing_item_id = i.listing_item_id AND l.product_image_id = i.product_image_id
WHERE i.has_file AND l.reference_id IS NOT NULL AND l.reference_id <> ''
  AND (l.updated_at < i.image_updated_at OR (i.alt_id IS NOT NULL AND l.updated_at < i.alt_updated_at))
UNION ALL
SELECT '(d) of which: main image replaced WITH the alternate', COUNT(*), COUNT(DISTINCT i.product_id)
FROM img i
JOIN listing_item_product_images l ON l.listing_item_id = i.listing_item_id AND l.product_image_id = i.product_image_id
WHERE i.has_file AND i.alt_id IS NOT NULL AND l.reference_id IS NOT NULL AND l.reference_id <> ''
  AND (l.updated_at < i.image_updated_at OR l.updated_at < i.alt_updated_at);
