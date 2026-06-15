-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 15, 2026 at 09:22 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `medical_store`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `description`) VALUES
(1, 'Tablet', 'Solid oral dosage form'),
(2, 'Syrup', 'Liquid oral medicine'),
(3, 'Injection', 'Injectable medicine'),
(4, 'Ointment', 'Topical medicine'),
(5, 'Capsule', 'Encapsulated medicine');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `item_id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `sale_items`
--
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

-- --------------------------------------------------------

--
-- Table structure for table `stock_transactions`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `supplier_name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

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

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `username`, `password`, `role`, `email`, `phone`, `status`, `created_at`, `updated_at`) VALUES
(1, 'System Admin', 'admin', '$2y$10$REPLACE_WITH_PASSWORD_HASH', 'admin', 'admin@medstore.com', NULL, 'active', '2026-06-15 07:21:37', '2026-06-15 07:21:37');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_expired_medicines`
-- (See below for the actual view)
--
CREATE TABLE `v_expired_medicines` (
`medicine_id` int(11)
,`medicine_name` varchar(150)
,`batch_no` varchar(50)
,`expiry_date` date
,`quantity` int(11)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_expiring_medicines`
-- (See below for the actual view)
--
CREATE TABLE `v_expiring_medicines` (
`medicine_id` int(11)
,`medicine_name` varchar(150)
,`batch_no` varchar(50)
,`expiry_date` date
,`quantity` int(11)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_low_stock_medicines`
-- (See below for the actual view)
--
CREATE TABLE `v_low_stock_medicines` (
`medicine_id` int(11)
,`medicine_name` varchar(150)
,`quantity` int(11)
,`reorder_level` int(11)
);

-- --------------------------------------------------------

--
-- Structure for view `v_expired_medicines`
--
DROP TABLE IF EXISTS `v_expired_medicines`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_expired_medicines`  AS SELECT `medicines`.`medicine_id` AS `medicine_id`, `medicines`.`medicine_name` AS `medicine_name`, `medicines`.`batch_no` AS `batch_no`, `medicines`.`expiry_date` AS `expiry_date`, `medicines`.`quantity` AS `quantity` FROM `medicines` WHERE `medicines`.`expiry_date` < curdate() ;

-- --------------------------------------------------------

--
-- Structure for view `v_expiring_medicines`
--
DROP TABLE IF EXISTS `v_expiring_medicines`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_expiring_medicines`  AS SELECT `medicines`.`medicine_id` AS `medicine_id`, `medicines`.`medicine_name` AS `medicine_name`, `medicines`.`batch_no` AS `batch_no`, `medicines`.`expiry_date` AS `expiry_date`, `medicines`.`quantity` AS `quantity` FROM `medicines` WHERE `medicines`.`expiry_date` <= curdate() + interval 30 day ;

-- --------------------------------------------------------

--
-- Structure for view `v_low_stock_medicines`
--
DROP TABLE IF EXISTS `v_low_stock_medicines`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_low_stock_medicines`  AS SELECT `medicines`.`medicine_id` AS `medicine_id`, `medicines`.`medicine_name` AS `medicine_name`, `medicines`.`quantity` AS `quantity`, `medicines`.`reorder_level` AS `reorder_level` FROM `medicines` WHERE `medicines`.`quantity` <= `medicines`.`reorder_level` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`medicine_id`),
  ADD KEY `fk_medicine_category` (`category_id`),
  ADD KEY `fk_medicine_supplier` (`supplier_id`),
  ADD KEY `idx_medicine_name` (`medicine_name`),
  ADD KEY `idx_expiry_date` (`expiry_date`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`sale_id`),
  ADD UNIQUE KEY `invoice_no` (`invoice_no`),
  ADD KEY `fk_sale_customer` (`customer_id`),
  ADD KEY `fk_sale_user` (`user_id`),
  ADD KEY `idx_sale_date` (`sale_date`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `fk_saleitem_sale` (`sale_id`),
  ADD KEY `fk_saleitem_medicine` (`medicine_id`);

--
-- Indexes for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `fk_stock_user` (`user_id`),
  ADD KEY `idx_stock_medicine_date` (`medicine_id`,`transaction_date`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `medicine_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `sale_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `medicines`
--
ALTER TABLE `medicines`
  ADD CONSTRAINT `fk_medicine_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_medicine_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_sale_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sale_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `fk_saleitem_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`medicine_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_saleitem_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`sale_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  ADD CONSTRAINT `fk_stock_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`medicine_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_stock_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
