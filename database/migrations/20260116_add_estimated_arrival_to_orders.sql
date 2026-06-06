-- Migration: add estimated_arrival to orders
-- Adds a nullable DATE column `estimated_arrival` to `orders` to support server-side estimated arrival values.

ALTER TABLE `orders` ADD COLUMN `estimated_arrival` DATE NULL AFTER `notes`;

-- Usage:
-- 1) Backup database:
--    mysqldump -u root -p izzamawy_pastry_shop > backup_before_estimated_arrival.sql
-- 2) Apply migration:
--    mysql -u root -p izzamawy_pastry_shop < database/migrations/20260116_add_estimated_arrival_to_orders.sql

-- After applying, retry checkout to ensure the error is resolved.
