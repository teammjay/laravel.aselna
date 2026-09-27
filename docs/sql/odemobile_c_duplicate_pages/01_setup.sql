-- =====================================================================================
-- Duplicate-page test on OdeMobile-C (ode-clone.myshopify.com): recreate the UZID condition.
--
-- On UZID, 601 pages exist on Shopify but have no store_navigation_page row. Generate All then
-- creates the row with no url_path and ShopifyIndvPagesSyncJob creates the page again, which
-- Shopify saves as "<handle>-1".
--
-- This removes the local rows for the same 8 examples used in the UZID report, leaving their
-- Shopify pages in place. Back up first: 99_restore.sql holds the original rows.
-- =====================================================================================

SET @store_id := '8a6f1c6c-1d84-11f1-aaa9-ac1f6b9eb7e3';

DELETE FROM store_navigation_page
WHERE store_id = @store_id
  AND id IN (
      -- 90 Brand device product types
      '3fb0b255-e8fb-4886-96cb-908440fef3f6',  -- apple-ipod-touch-7th-gen-accessories  (page 132092166362)
      'fd181a04-6a98-49ba-9c45-d4b5388a7870',  -- samsung-galaxy-z-fold6-accessories    (page 132092297434)
      '24fc34ac-b86f-4f9e-8178-c5824c18f06a',  -- nokia-g310-5g-accessories             (page 132092199130)
      -- 50 Brand devices
      '984d26a7-9b10-45ac-acd0-fcd60b9f6caf',  -- fujitsu                               (page 132137451738)
      '4819a9f4-3846-4324-9514-d9a311062c48',  -- plantronics                           (page 132135190746)
      '88fe67ea-a694-41d0-b043-be5b94ece1c7',  -- coby                                  (page 132136894682)
      -- 20 Product type brands
      'e2dc656a-481c-4cea-b021-8b6d038b7015',  -- health-household                      (page 132160454874)
      '8a93c221-eef4-4d8b-95fb-c0afccbfb075'   -- clothing-accessories                  (page 132160422106)
  );


select * from store_navigation_page
WHERE store_id = @store_id COLLATE utf8mb4_unicode_ci
  AND id IN (
    -- 90 Brand device product types
             '3fb0b255-e8fb-4886-96cb-908440fef3f6',  -- apple-ipod-touch-7th-gen-accessories  (page 132092166362)
             'fd181a04-6a98-49ba-9c45-d4b5388a7870',  -- samsung-galaxy-z-fold6-accessories    (page 132092297434)
             '24fc34ac-b86f-4f9e-8178-c5824c18f06a',  -- nokia-g310-5g-accessories             (page 132092199130)
    -- 50 Brand devices
             '984d26a7-9b10-45ac-acd0-fcd60b9f6caf',  -- fujitsu                               (page 132137451738)
             '4819a9f4-3846-4324-9514-d9a311062c48',  -- plantronics                           (page 132135190746)
             '88fe67ea-a694-41d0-b043-be5b94ece1c7',  -- coby                                  (page 132136894682)
    -- 20 Product type brands
             'e2dc656a-481c-4cea-b021-8b6d038b7015',  -- health-household                      (page 132160454874)
             '8a93c221-eef4-4d8b-95fb-c0afccbfb075'   -- clothing-accessories                  (page 132160422106)
    );
