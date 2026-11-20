-- Order invoice adjustments + e-way bill.
-- The admin order form already sends these and the invoice already reads them,
-- but the columns were missing, so the values were silently dropped on save.
-- Amounts use 3 decimals to match how OrderPlacementService rounds them.

ALTER TABLE `orders`
  ADD COLUMN `eway_bill_number` VARCHAR(50) NULL DEFAULT NULL,
  ADD COLUMN `eway_bill_date` DATE NULL DEFAULT NULL,
  ADD COLUMN `pack_forwarding_amount` DECIMAL(20,3) NOT NULL DEFAULT 0,
  ADD COLUMN `insurance_amount` DECIMAL(20,3) NOT NULL DEFAULT 0,
  ADD COLUMN `other_charges_amount` DECIMAL(20,3) NOT NULL DEFAULT 0,
  ADD COLUMN `amountwise_discount` DECIMAL(20,3) NOT NULL DEFAULT 0,
  ADD COLUMN `cr_db_adjustment` DECIMAL(20,3) NOT NULL DEFAULT 0;
