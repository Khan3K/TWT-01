-- Medical Store Management System Database Schema (User Provided)
CREATE DATABASE IF NOT EXISTS medical_store;
USE medical_store;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Table structure for table `categories`
CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`category_id`, `category_name`, `description`) VALUES
(1, 'Tablet', 'Solid oral dosage form'),
(2, 'Syrup', 'Liquid oral medicine'),
(3, 'Injection', 'Injectable medicine'),
(4, 'Ointment', 'Topical medicine'),
(5, 'Capsule', 'Encapsulated medicine');

-- Table structure for table `customers`
CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `medicines`
CREATE TABLE `medicines` (
  `medicine_id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `sales`
CREATE TABLE `sales` (
  `sale_id` int(11) NOT NULL,
  `invoice_no` varchar(30) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `sale_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('cash','card','online') NOT NULL DEFAULT 'cash',
  `payment_status` enum('paid','pending','refunded') NOT NULL DEFAULT 'paid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `sale_items`
CREATE TABLE `sale_items` (
  `item_id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `stock_transactions`
CREATE TABLE `stock_transactions` (
  `transaction_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('IN','OUT','ADJUSTMENT','RETURN') NOT NULL,
  `quantity` int(11) NOT NULL,
  `reference_no` varchar(50) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `suppliers`
CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `supplier_name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for table `users`
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','pharmacist','manager') NOT NULL DEFAULT 'pharmacist',
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Admin User (Password: admin123)
INSERT INTO `users` (`user_id`, `full_name`, `username`, `password`, `role`, `status`) VALUES
(1, 'System Admin', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Triggers
DELIMITER $$
CREATE TRIGGER `trg_after_sale_item_insert` AFTER INSERT ON `sale_items` FOR EACH ROW BEGIN
    UPDATE medicines
    SET quantity = quantity - NEW.quantity
    WHERE medicine_id = NEW.medicine_id;

    INSERT INTO stock_transactions (medicine_id, user_id, type, quantity, reference_no, notes)
    SELECT NEW.medicine_id, s.user_id, 'OUT', NEW.quantity, s.invoice_no, 'Auto-logged from sale'
    FROM sales s
    WHERE s.sale_id = NEW.sale_id;
END
$$
DELIMITER ;

-- Views
CREATE VIEW `v_expired_medicines` AS SELECT `medicine_id`, `medicine_name`, `batch_no`, `expiry_date`, `quantity` FROM `medicines` WHERE `expiry_date` < curdate();
CREATE VIEW `v_expiring_medicines` AS SELECT `medicine_id`, `medicine_name`, `batch_no`, `expiry_date`, `quantity` FROM `medicines` WHERE `expiry_date` <= curdate() + interval 30 day;
CREATE VIEW `v_low_stock_medicines` AS SELECT `medicine_id`, `medicine_name`, `quantity`, `reorder_level` FROM `medicines` WHERE `quantity` <= `reorder_level`;

-- Indexes
ALTER TABLE `categories` ADD PRIMARY KEY (`category_id`), ADD UNIQUE KEY `category_name` (`category_name`);
ALTER TABLE `customers` ADD PRIMARY KEY (`customer_id`);
ALTER TABLE `medicines` ADD PRIMARY KEY (`medicine_id`), ADD KEY `fk_medicine_category` (`category_id`), ADD KEY `fk_medicine_supplier` (`supplier_id`);
ALTER TABLE `sales` ADD PRIMARY KEY (`sale_id`), ADD UNIQUE KEY `invoice_no` (`invoice_no`), ADD KEY `fk_sale_customer` (`customer_id`), ADD KEY `fk_sale_user` (`user_id`);
ALTER TABLE `sale_items` ADD PRIMARY KEY (`item_id`), ADD KEY `fk_saleitem_sale` (`sale_id`), ADD KEY `fk_saleitem_medicine` (`medicine_id`);
ALTER TABLE `stock_transactions` ADD PRIMARY KEY (`transaction_id`), ADD KEY `fk_stock_user` (`user_id`);
ALTER TABLE `suppliers` ADD PRIMARY KEY (`supplier_id`);
ALTER TABLE `users` ADD PRIMARY KEY (`user_id`), ADD UNIQUE KEY `username` (`username`);

-- Auto Increment
ALTER TABLE `categories` MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
ALTER TABLE `customers` MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `medicines` MODIFY `medicine_id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `sales` MODIFY `sale_id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `sale_items` MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `stock_transactions` MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `suppliers` MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `users` MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

-- Constraints
ALTER TABLE `medicines` ADD CONSTRAINT `fk_medicine_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL ON UPDATE CASCADE, ADD CONSTRAINT `fk_medicine_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE `sales` ADD CONSTRAINT `fk_sale_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL ON UPDATE CASCADE, ADD CONSTRAINT `fk_sale_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;
ALTER TABLE `sale_items` ADD CONSTRAINT `fk_saleitem_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`medicine_id`) ON UPDATE CASCADE, ADD CONSTRAINT `fk_saleitem_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `stock_transactions` ADD CONSTRAINT `fk_stock_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`medicine_id`) ON DELETE CASCADE ON UPDATE CASCADE, ADD CONSTRAINT `fk_stock_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

-- Table structure for table `activity_logs`
CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `activity_logs` ADD PRIMARY KEY (`log_id`), ADD KEY `fk_log_user` (`user_id`);
ALTER TABLE `activity_logs` MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `activity_logs` ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

COMMIT;
