-- Migration: Change product_stock.product_id -> product_name
-- IMPORTANT: Review before running. This migration will:
-- 1) Add a new column `product_name` to `product_stock` and populate it from `products.name`.
-- 2) Add a UNIQUE index on `product_name` (this will fail if product names are not unique).
-- 3) Drop the `product_id` column.
-- 4) Recreate triggers to keep `product_stock` in sync using product names.
-- NOTE: This is potentially destructive. BACKUP your database before running.

START TRANSACTION;

-- 1) Add new column
ALTER TABLE `product_stock` ADD COLUMN `product_name` varchar(200) DEFAULT NULL AFTER `product_id`;

-- 2) Populate product_name from products table
UPDATE `product_stock` ps
JOIN `products` p ON ps.product_id = p.id
SET ps.product_name = p.name;

-- 3) Add a unique index on product_name to support ON DUPLICATE KEY semantics in triggers
--    If your product names are not unique, DO NOT add this index and adapt triggers accordingly.
ALTER TABLE `product_stock` ADD UNIQUE KEY `uk_product_name` (`product_name`(200));

-- 4) Drop existing triggers that reference product_id
DROP TRIGGER IF EXISTS `trg_products_after_insert`;
DROP TRIGGER IF EXISTS `trg_products_after_update`;
DROP TRIGGER IF EXISTS `trg_products_after_delete`;

-- 5) Drop product_id column
ALTER TABLE `product_stock` DROP COLUMN `product_id`;

-- 6) Recreate triggers to use product_name
DELIMITER $$
CREATE TRIGGER `trg_products_after_insert`
AFTER INSERT ON `products` FOR EACH ROW
BEGIN
  INSERT INTO `product_stock` (`product_name`, `stock_quantity`, `restock_threshold`, `restock_required`, `created_at`)
  VALUES (NEW.name, NEW.stock_quantity, 10, (NEW.stock_quantity < 10), NOW())
  ON DUPLICATE KEY UPDATE stock_quantity = VALUES(stock_quantity), restock_required = VALUES(restock_required), last_checked = NOW();
END$$

CREATE TRIGGER `trg_products_after_update`
AFTER UPDATE ON `products` FOR EACH ROW
BEGIN
  UPDATE `product_stock`
  SET stock_quantity = NEW.stock_quantity,
      restock_required = (NEW.stock_quantity < restock_threshold),
      last_checked = NOW()
  WHERE product_name = NEW.name;

  IF (ROW_COUNT() = 0) THEN
    INSERT INTO `product_stock` (`product_name`, `stock_quantity`, `restock_threshold`, `restock_required`, `created_at`)
    VALUES (NEW.name, NEW.stock_quantity, 10, (NEW.stock_quantity < 10), NOW());
  END IF;
END$$

CREATE TRIGGER `trg_products_after_delete`
AFTER DELETE ON `products` FOR EACH ROW
BEGIN
  DELETE FROM `product_stock` WHERE product_name = OLD.name;
END$$
DELIMITER ;

COMMIT;

-- USAGE:
-- 1) Backup your database (mysqldump).
-- 2) Run this migration in your MySQL client: mysql -u root -p izzamawy_pastry_shop < 20260116_change_product_stock_product_id_to_product_name.sql
-- 3) After applying, search the codebase for references to `product_stock.product_id` and update to use `product_name` or adjust logic accordingly.

-- CAUTION:
-- - If any application code relies on the integer product_id for joins, those queries must be updated.
-- - Using product names as keys can break referential integrity when names change or are duplicated.
-- Consider keeping `product_id` and instead adding `product_name` for display purposes if possible.
