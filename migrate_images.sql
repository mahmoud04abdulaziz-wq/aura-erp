-- MiskStone AURA ERP — Product Images Migration (v2 - Generated Images)
-- Run this on the production database via phpMyAdmin Import

-- Add column if not exists (safe to re-run)
ALTER TABLE `inventory_finished_goods` ADD COLUMN IF NOT EXISTS `image_path` VARCHAR(255) DEFAULT NULL;

-- Core products
UPDATE `inventory_finished_goods` SET `image_path` = 'images/product_marble_slab.png' WHERE `item_id` = 'FG-001';
UPDATE `inventory_finished_goods` SET `image_path` = 'images/product_granite_tile.png' WHERE `item_id` = 'FG-002';
UPDATE `inventory_finished_goods` SET `image_path` = 'images/product_decorative_panel.png' WHERE `item_id` = 'FG-003';
UPDATE `inventory_finished_goods` SET `image_path` = 'images/product_countertop.png' WHERE `item_id` = 'FG-004';
UPDATE `inventory_finished_goods` SET `image_path` = 'images/product_wall_cladding.png' WHERE `item_id` = 'FG-005';
UPDATE `inventory_finished_goods` SET `image_path` = 'images/product_vanity_top.png' WHERE `item_id` = 'FG-006';

-- Crowns
UPDATE `inventory_finished_goods` SET `image_path` = 'images/product_cornice.png' WHERE `item_id` IN ('FG-C01','FG-C02');

-- Bases
UPDATE `inventory_finished_goods` SET `image_path` = 'images/product_column_base.png' WHERE `item_id` IN ('FG-C03','FG-C04');

-- Columns
UPDATE `inventory_finished_goods` SET `image_path` = 'images/product_column.png' WHERE `item_id` IN ('FG-COL1','FG-COL2','FG-COL3','FG-COL4','FG-COL5','FG-COL6');

-- Cornices
UPDATE `inventory_finished_goods` SET `image_path` = 'images/product_cornice.png' WHERE `item_id` IN ('FG-SC01','FG-SC02','FG-SC03','FG-SC04','FG-SC05');

-- Corners
UPDATE `inventory_finished_goods` SET `image_path` = 'images/product_corner_piece.png' WHERE `item_id` IN ('FG-SCC1','FG-SCC3','FG-SCC4');

-- Ceramics / Tiles
UPDATE `inventory_finished_goods` SET `image_path` = 'images/product_ceramic_tile.png' WHERE `item_id` IN ('FG-ST01','FG-ST02','FG-ST03','FG-ST04','FG-ST05','FG-ST06','FG-ST07','FG-STT1','FG-STT2','FG-STT3');
