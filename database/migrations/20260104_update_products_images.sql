-- Migration: Update products.image_url to match product_pictures previews
-- Date: 2026-01-04

START TRANSACTION;

-- Safety: do nothing if products table does not exist
SET @has_products_table = (
  SELECT COUNT(*)
  FROM information_schema.tables
  WHERE table_schema = DATABASE() AND table_name = 'products'
);

-- Update Camote / Chips related products
-- Exact product name mappings (preferred)
-- Each statement targets known product names provided by the user and sets the exact image path.
UPDATE products
SET image_url = 'product_pictures/Adobo_Garlic_Cashew.jpg'
WHERE @has_products_table = 1 AND (name = 'Adobo Garlic Cashew' OR name LIKE '%Adobo Garlic Cashew%');

UPDATE products
SET image_url = 'product_pictures/Camote_Chips.jpg'
WHERE @has_products_table = 1 AND (name = 'Camote Chips' OR name LIKE '%Camote Chips%');

UPDATE products
SET image_url = 'product_pictures/Camote_Truffle_Chips.jpg'
WHERE @has_products_table = 1 AND (name = 'Camote Truffle Chips' OR name LIKE '%Camote Truffle%');

UPDATE products
SET image_url = 'product_pictures/Gabi_Taro_Chips.jpg'
WHERE @has_products_table = 1 AND (name = 'Gabi Taro Chips' OR name LIKE '%Gabi%' OR name LIKE '%Taro%Gabi%');

UPDATE products
SET image_url = 'product_pictures/Banana_Chips.jpg'
WHERE @has_products_table = 1 AND (name = 'Banana Chips' OR name LIKE '%Banana Chips%');

UPDATE products
SET image_url = 'product_pictures/Taro_Adobo_Gata_Chips.jpg'
WHERE @has_products_table = 1 AND (name = 'Taro Adobo Gata Chips' OR name LIKE '%Adobo Gata%');

UPDATE products
SET image_url = 'product_pictures/Taro_Adobo_Gabi_Chips.jpg'
WHERE @has_products_table = 1 AND (name = 'Taro Adobo Gabi Chips' OR name LIKE '%Adobo Gabi%');

UPDATE products
SET image_url = 'product_pictures/Crunchy_Garlic_Chips.jpg'
WHERE @has_products_table = 1 AND (name = 'Crunchy Garlic Chips' OR name LIKE '%Crunchy Garlic%');

UPDATE products
SET image_url = 'product_pictures/Baked_KIng_Cashew.jpg'
WHERE @has_products_table = 1 AND (name = 'Baked King Cashew' OR name LIKE '%Baked King%');

UPDATE products
SET image_url = 'product_pictures/Premium_Whole_Cashew.jpg'
WHERE @has_products_table = 1 AND (name = 'Premium Whole Cashew' OR name LIKE '%Premium Whole%');

UPDATE products
SET image_url = 'product_pictures/Himalayan_Salted_Cashew.jpg'
WHERE @has_products_table = 1 AND (name = 'Himalayan Salted Cashew' OR name LIKE '%Himalayan%Salted%' OR name LIKE '%Himalayan%');

UPDATE products
SET image_url = 'product_pictures/Creamy_Mixed_Nuts.jpg'
WHERE @has_products_table = 1 AND (name = 'Creamy Mixed Nuts' OR name LIKE '%Creamy%Mixed%');

UPDATE products
SET image_url = 'product_pictures/Premium_Mix_Nuts.jpg'
WHERE @has_products_table = 1 AND (name = 'Premium Mixed Nuts' OR name = 'Premium Mix Nuts' OR name LIKE '%Premium Mix%');

UPDATE products
SET image_url = 'product_pictures/Crispy_Dilis.jpg'
WHERE @has_products_table = 1 AND (name = 'Crispy Dilis' OR name LIKE '%Crispy Dilis%');

UPDATE products
SET image_url = 'product_pictures/Crispy_Dilis_Adobo_Flavor_2.jpg'
WHERE @has_products_table = 1 AND (name = 'Crispy Dilis Adobo Flavor' OR name LIKE '%Adobo Flavor%' OR name LIKE '%Adobo%Dilis%');

UPDATE products
SET image_url = 'product_pictures/Sweet_And_Spicy_Dilis.jpg'
WHERE @has_products_table = 1 AND (name = 'Sweet and Spicy Dilis' OR name LIKE '%Sweet%Spicy%Dilis%' OR name LIKE '%Sweet and Spicy%');

UPDATE products
SET image_url = 'product_pictures/Assorted_Products_1.jpg'
WHERE @has_products_table = 1 AND (name = 'Assorted Product #1' OR name LIKE '%Assorted%');

UPDATE products
SET image_url = 'product_pictures/Bundle_1.jpg'
WHERE @has_products_table = 1 AND (name = 'Bundle #1' OR name LIKE '%Bundle 1%' OR name LIKE '%Bundle #1%');

UPDATE products
SET image_url = 'product_pictures/Bundle_2.jpg'
WHERE @has_products_table = 1 AND (name = 'Bundle #2' OR name LIKE '%Bundle 2%' OR name LIKE '%Bundle #2%');

UPDATE products
SET image_url = 'product_pictures/Bundle_3.jpg'
WHERE @has_products_table = 1 AND (name = 'Bundle #3' OR name LIKE '%Bundle 3%' OR name LIKE '%Bundle #3%');

UPDATE products
SET image_url = 'product_pictures/Gift_Box_1.jpg'
WHERE @has_products_table = 1 AND (name = 'Gift Box #1' OR name LIKE '%Gift Box%');

-- Fallback generic updates (keep broader matches for any remaining items)
UPDATE products
SET image_url = 'product_pictures/Camote_Chips.jpg'
WHERE @has_products_table = 1
  AND (
    name LIKE '%Camote%'
    OR name LIKE '%Chips%'
    OR description LIKE '%camote%'
  );
-- Prevent overriding more specific 'Truffle' variants (e.g. Camote Truffle Chips)
UPDATE products
SET image_url = image_url
WHERE @has_products_table = 1
  AND (
    name LIKE '%Truffle%'
  );

-- Update Himalayan Salted Cashew
UPDATE products
SET image_url = 'product_pictures/Himalayan_Salted_Cashew.jpg'
WHERE @has_products_table = 1
  AND (
    name LIKE '%Himalayan%'
    OR name LIKE '%Salted Cashew%'
    OR name LIKE '%Cashew%'
  );

-- Ensure we don't accidentally overwrite Truffle variants with broader matches
UPDATE products
SET image_url = image_url
WHERE @has_products_table = 1 AND name LIKE '%Truffle%';

-- Update Premium Mix Nuts
UPDATE products
SET image_url = 'product_pictures/Premium_Mix_Nuts.jpg'
WHERE @has_products_table = 1
  AND (
    name LIKE '%Premium%Nuts%'
    OR name LIKE '%Premium%'
    OR name LIKE '%Mix Nuts%'
    OR name LIKE '%Nuts%'
  );

-- Update Sweet And Spicy Dilis
UPDATE products
SET image_url = 'product_pictures/Sweet_And_Spicy_Dilis.jpg'
WHERE @has_products_table = 1
  AND (
    name LIKE '%Dilis%'
    OR name LIKE '%dilis%'
    OR description LIKE '%dilis%'
  );

-- Update Assorted Products
UPDATE products
SET image_url = 'product_pictures/Assorted_Products_1.jpg'
WHERE @has_products_table = 1
  AND (
    name LIKE '%Assorted%'
    OR name LIKE '%Assorted Products%'
  );

-- Update Bundle products
UPDATE products
SET image_url = 'product_pictures/Bundle_1.jpg'
WHERE @has_products_table = 1
  AND (
    name LIKE '%Bundle%'
    OR description LIKE '%bundle%'
  );

-- Update Gift Box products
UPDATE products
SET image_url = 'product_pictures/Gift_Box_1.jpg'
WHERE @has_products_table = 1
  AND (
    name LIKE '%Gift%Box%'
    OR name LIKE '%Gift Box%'
    OR name LIKE '%Gift%'
  );

COMMIT;

-- Notes:
-- 1) This migration updates existing product records' image_url fields based on name/description patterns.
-- 2) It is idempotent and safe to run multiple times; it will only update rows that match the patterns.
-- 3) Review the product names in your DB if you want more precise matches; I can refine the WHERE clauses if you provide product names.
