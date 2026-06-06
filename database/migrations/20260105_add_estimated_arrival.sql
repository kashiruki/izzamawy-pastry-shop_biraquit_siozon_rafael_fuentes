-- Migration: Add estimated_arrival column to orders
-- Date: 2026-01-05

START TRANSACTION;

-- Add column only if it does not exist
SET @has_column = (
  SELECT COUNT(*)
  FROM information_schema.columns
  WHERE table_schema = DATABASE() AND table_name = 'orders' AND column_name = 'estimated_arrival'
);

-- Add the column when missing
IF @has_column = 0 THEN
  ALTER TABLE `orders` ADD COLUMN `estimated_arrival` DATE DEFAULT NULL;
END IF;

COMMIT;
