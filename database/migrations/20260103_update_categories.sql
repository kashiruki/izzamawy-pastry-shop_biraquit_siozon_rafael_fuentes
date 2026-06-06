-- Migration: Ensure categories exist and update image_url + display_order
-- Date: 2026-01-03
START TRANSACTION;

-- Safety: do nothing if categories table does not exist
-- (This avoids fatal errors if the schema hasn't been created yet.)
SET @has_categories_table = (
	SELECT COUNT(*)
	FROM information_schema.tables
	WHERE table_schema = DATABASE() AND table_name = 'categories'
);

-- Run only when the categories table exists
-- The following statements are idempotent: they insert if missing and update existing rows.
-- Use a derived table for the INSERT ... SELECT pattern to ensure compatibility.
-- Chips
INSERT INTO categories (name, description, image_url, display_order, is_active)
SELECT t.name, t.description, t.image_url, t.display_order, t.is_active
FROM (SELECT 'Chips' AS name, 'Various savory and sweet chips (camote, taro, banana, etc.)' AS description, 'product_pictures/Camote_Chips.jpg' AS image_url, 1 AS display_order, 1 AS is_active) AS t
WHERE @has_categories_table = 1
	AND NOT EXISTS (SELECT 1 FROM categories c WHERE c.name = t.name);

UPDATE categories
SET image_url = 'product_pictures/Camote_Chips.jpg', display_order = 1, description = 'Various savory and sweet chips (camote, taro, banana, etc.)'
WHERE @has_categories_table = 1 AND name = 'Chips';

-- Cashew
INSERT INTO categories (name, description, image_url, display_order, is_active)
SELECT t.name, t.description, t.image_url, t.display_order, t.is_active
FROM (SELECT 'Cashew' AS name, 'Salted, roasted, and flavored cashew selections' AS description, 'product_pictures/Himalayan_Salted_Cashew.jpg' AS image_url, 2 AS display_order, 1 AS is_active) AS t
WHERE @has_categories_table = 1
	AND NOT EXISTS (SELECT 1 FROM categories c WHERE c.name = t.name);

UPDATE categories
SET image_url = 'product_pictures/Himalayan_Salted_Cashew.jpg', display_order = 2, description = 'Salted, roasted, and flavored cashew selections'
WHERE @has_categories_table = 1 AND name = 'Cashew';

-- Nuts
INSERT INTO categories (name, description, image_url, display_order, is_active)
SELECT t.name, t.description, t.image_url, t.display_order, t.is_active
FROM (SELECT 'Nuts' AS name, 'Mixed nuts and premium nut blends' AS description, 'product_pictures/Premium_Mix_Nuts.jpg' AS image_url, 3 AS display_order, 1 AS is_active) AS t
WHERE @has_categories_table = 1
	AND NOT EXISTS (SELECT 1 FROM categories c WHERE c.name = t.name);

UPDATE categories
SET image_url = 'product_pictures/Premium_Mix_Nuts.jpg', display_order = 3, description = 'Mixed nuts and premium nut blends'
WHERE @has_categories_table = 1 AND name = 'Nuts';

-- Dilis
INSERT INTO categories (name, description, image_url, display_order, is_active)
SELECT t.name, t.description, t.image_url, t.display_order, t.is_active
FROM (SELECT 'Dilis' AS name, 'Sweet and savory dried fish (dilis) varieties' AS description, 'product_pictures/Sweet_And_Spicy_Dilis.jpg' AS image_url, 4 AS display_order, 1 AS is_active) AS t
WHERE @has_categories_table = 1
	AND NOT EXISTS (SELECT 1 FROM categories c WHERE c.name = t.name);

UPDATE categories
SET image_url = 'product_pictures/Sweet_And_Spicy_Dilis.jpg', display_order = 4, description = 'Sweet and savory dried fish (dilis) varieties'
WHERE @has_categories_table = 1 AND name = 'Dilis';

-- Assorted Products
INSERT INTO categories (name, description, image_url, display_order, is_active)
SELECT t.name, t.description, t.image_url, t.display_order, t.is_active
FROM (SELECT 'Assorted Products' AS name, 'Assorted pasalubong and mixed product packs' AS description, 'product_pictures/Assorted_Products_1.jpg' AS image_url, 5 AS display_order, 1 AS is_active) AS t
WHERE @has_categories_table = 1
	AND NOT EXISTS (SELECT 1 FROM categories c WHERE c.name = t.name);

UPDATE categories
SET image_url = 'product_pictures/Assorted_Products_1.jpg', display_order = 5, description = 'Assorted pasalubong and mixed product packs'
WHERE @has_categories_table = 1 AND name = 'Assorted Products';

-- Bundle
INSERT INTO categories (name, description, image_url, display_order, is_active)
SELECT t.name, t.description, t.image_url, t.display_order, t.is_active
FROM (SELECT 'Bundle' AS name, 'Bundle offers and value packs' AS description, 'product_pictures/Bundle_1.jpg' AS image_url, 6 AS display_order, 1 AS is_active) AS t
WHERE @has_categories_table = 1
	AND NOT EXISTS (SELECT 1 FROM categories c WHERE c.name = t.name);

UPDATE categories
SET image_url = 'product_pictures/Bundle_1.jpg', display_order = 6, description = 'Bundle offers and value packs'
WHERE @has_categories_table = 1 AND name = 'Bundle';

-- Gift Box
INSERT INTO categories (name, description, image_url, display_order, is_active)
SELECT t.name, t.description, t.image_url, t.display_order, t.is_active
FROM (SELECT 'Gift Box' AS name, 'Curated gift boxes for special occasions' AS description, 'product_pictures/Gift_Box_1.jpg' AS image_url, 7 AS display_order, 1 AS is_active) AS t
WHERE @has_categories_table = 1
	AND NOT EXISTS (SELECT 1 FROM categories c WHERE c.name = t.name);

UPDATE categories
SET image_url = 'product_pictures/Gift_Box_1.jpg', display_order = 7, description = 'Curated gift boxes for special occasions'
WHERE @has_categories_table = 1 AND name = 'Gift Box';

COMMIT;

-- Note: This migration will insert missing categories and update existing categories' image_url and display_order.
-- It intentionally avoids deleting or renaming existing categories to preserve product relationships.
