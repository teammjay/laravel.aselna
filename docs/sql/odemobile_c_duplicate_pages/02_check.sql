-- =====================================================================================
-- Duplicate-page test on OdeMobile-C: check the 8 example rows.
--   Before the test (after 01_setup.sql): no rows returned.
--   After Generate All with the fix: 8 rows, url_path without "-1", page_id equal to the
--   ORIGINAL Shopify page id in the comment (the existing page was updated, not recreated).
--   A "-1" url_path or a different page_id means a duplicate page was created.
-- =====================================================================================

SET @store_id := '8a6f1c6c-1d84-11f1-aaa9-ac1f6b9eb7e3';

SELECT page_type, title, url_path, page_id,
       CASE url_path
           WHEN 'apple-ipod-touch-7th-gen-accessories' THEN '132092166362'
           WHEN 'samsung-galaxy-z-fold6-accessories'   THEN '132092297434'
           WHEN 'nokia-g310-5g-accessories'            THEN '132092199130'
           WHEN 'fujitsu'                              THEN '132137451738'
           WHEN 'plantronics'                          THEN '132135190746'
           WHEN 'coby'                                 THEN '132136894682'
           WHEN 'health-household'                     THEN '132160454874'
           WHEN 'clothing-accessories'                 THEN '132160422106'
       END AS expected_page_id,
       updated_at
FROM store_navigation_page
WHERE store_id = @store_id
  AND (
      (page_type = 90 AND title IN ('Apple iPod Touch 7th Gen Accessories',
                                    'Samsung Galaxy Z Fold6 Accessories',
                                    'Nokia G310 5G Accessories'))
   OR (page_type = 50 AND title IN ('Fujitsu', 'Plantronics', 'Coby'))
   OR (page_type = 20 AND title IN ('Health & Household', 'Clothing & Accessories'))
  )
ORDER BY page_type, title;

-- Page handles ending in -1, -2 ... for this store. Baseline before the test: 39 rows (26 Sep 2026).
-- Any row beyond those 39 is a duplicate page.
SELECT page_type, title, url_path, page_id
FROM store_navigation_page
WHERE store_id = @store_id
  AND page_type IN (20, 50, 90)
  AND url_path REGEXP '-[0-9]+$';
