-- Courier integration phase 0: order_shipments columns + Delhivery setting fixes

ALTER TABLE `order_shipments`
  ADD COLUMN `label_path` VARCHAR(255) NULL AFTER `tracking_url`,
  ADD COLUMN `courier_status` VARCHAR(100) NULL AFTER `status`,
  ADD COLUMN `last_synced_at` TIMESTAMP NULL AFTER `courier_status`;

-- Book COD orders as COD with Delhivery (was always Prepaid)
UPDATE `business_settings` SET `value` = 'match-order'
WHERE `type` = 'delhivery_shipment_payment_mode' AND `value` = 'Prepaid';

-- Replace placeholder HSN with 3004 (medicaments)
UPDATE `business_settings` SET `value` = '3004'
WHERE `type` = 'delhivery_default_hsn_code' AND `value` = '987456645';
