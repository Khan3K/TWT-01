-- ============================================================
-- Medical Store - Clean Reimport Script (XAMPP / phpMyAdmin)
-- ============================================================
-- This script resets and recreates the whole database safely.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

DROP DATABASE IF EXISTS `medical_store`;
CREATE DATABASE `medical_store` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `medical_store`;

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Tables
-- ----------------------------
CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','pharmacist','manager') NOT NULL DEFAULT 'pharmacist',
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `medicines` (
  `medicine_id` int(11) NOT NULL AUTO_INCREMENT,
  `medicine_name` varchar(150) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `batch_no` varchar(50) DEFAULT NULL,
  `unit` varchar(20) DEFAULT 'unit',
  `manufacture_date` date DEFAULT NULL,
  `expiry_date` date NOT NULL,
  `purchase_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `reorder_level` int(11) NOT NULL DEFAULT 10,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`medicine_id`),
  KEY `fk_medicine_category` (`category_id`),
  KEY `fk_medicine_supplier` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sales` (
  `sale_id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_no` varchar(30) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `sale_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('cash','card','online') NOT NULL DEFAULT 'cash',
  `payment_status` enum('paid','pending','refunded') NOT NULL DEFAULT 'paid',
  PRIMARY KEY (`sale_id`),
  UNIQUE KEY `invoice_no` (`invoice_no`),
  KEY `fk_sale_customer` (`customer_id`),
  KEY `fk_sale_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sale_items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`item_id`),
  KEY `fk_saleitem_sale` (`sale_id`),
  KEY `fk_saleitem_medicine` (`medicine_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `stock_transactions` (
  `transaction_id` int(11) NOT NULL AUTO_INCREMENT,
  `medicine_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('IN','OUT','ADJUSTMENT','RETURN') NOT NULL,
  `quantity` int(11) NOT NULL,
  `reference_no` varchar(50) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`transaction_id`),
  KEY `fk_stock_user` (`user_id`),
  KEY `idx_stock_medicine_date` (`medicine_id`,`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `fk_log_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Constraints
-- ----------------------------
ALTER TABLE `medicines`
  ADD CONSTRAINT `fk_medicine_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_medicine_supplier`
    FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`)
    ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `sales`
  ADD CONSTRAINT `fk_sale_customer`
    FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sale_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
    ON UPDATE CASCADE;

ALTER TABLE `sale_items`
  ADD CONSTRAINT `fk_saleitem_medicine`
    FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`medicine_id`)
    ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_saleitem_sale`
    FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `stock_transactions`
  ADD CONSTRAINT `fk_stock_medicine`
    FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`medicine_id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_stock_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
    ON UPDATE CASCADE;

ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_log_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
    ON DELETE SET NULL ON UPDATE CASCADE;

-- ----------------------------
-- Seed data
-- ----------------------------
INSERT INTO `categories` (`category_id`, `category_name`, `description`) VALUES
(1, 'Tablet', 'Solid oral dosage form'),
(2, 'Syrup', 'Liquid oral medicine'),
(3, 'Injection', 'Injectable medicine'),
(4, 'Ointment', 'Topical medicine'),
(5, 'Capsule', 'Encapsulated medicine');

-- admin / password
INSERT INTO `users` (`user_id`, `full_name`, `username`, `password`, `role`, `status`) VALUES
(1, 'System Admin', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- ----------------------------
-- Trigger
-- ----------------------------
DELIMITER $$
CREATE TRIGGER `trg_after_sale_item_insert`
AFTER INSERT ON `sale_items`
FOR EACH ROW
BEGIN
    UPDATE `medicines`
    SET `quantity` = `quantity` - NEW.`quantity`
    WHERE `medicine_id` = NEW.`medicine_id`;

    INSERT INTO `stock_transactions` (`medicine_id`, `user_id`, `type`, `quantity`, `reference_no`, `notes`)
    SELECT NEW.`medicine_id`, s.`user_id`, 'OUT', NEW.`quantity`, s.`invoice_no`, 'Auto-logged from sale'
    FROM `sales` s
    WHERE s.`sale_id` = NEW.`sale_id`;
END$$
DELIMITER ;

-- ----------------------------
-- Views
-- ----------------------------
CREATE OR REPLACE VIEW `v_expired_medicines` AS
SELECT `medicine_id`, `medicine_name`, `batch_no`, `expiry_date`, `quantity`
FROM `medicines`
WHERE `expiry_date` < CURDATE();

CREATE OR REPLACE VIEW `v_expiring_medicines` AS
SELECT `medicine_id`, `medicine_name`, `batch_no`, `expiry_date`, `quantity`
FROM `medicines`
WHERE `expiry_date` <= CURDATE() + INTERVAL 30 DAY;

CREATE OR REPLACE VIEW `v_low_stock_medicines` AS
SELECT `medicine_id`, `medicine_name`, `quantity`, `reorder_level`
FROM `medicines`
WHERE `quantity` <= `reorder_level`;

SET FOREIGN_KEY_CHECKS = 1;
