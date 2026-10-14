-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 17, 2025 at 01:31 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `assetmanagement`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Elektronik'),
(2, 'Furniture');

-- --------------------------------------------------------

--
-- Table structure for table `company_settings`
--

CREATE TABLE `company_settings` (
  `id` int NOT NULL,
  `name` varchar(150) NOT NULL,
  `logo_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `company_settings`
--

INSERT INTO `company_settings` (`id`, `name`, `logo_path`) VALUES
(1, 'PT NHM', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int NOT NULL,
  `name` varchar(120) NOT NULL,
  `sku` varchar(60) DEFAULT NULL,
  `stock` int DEFAULT '0',
  `category_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `name`, `sku`, `stock`, `category_id`) VALUES
(1, 'Laptop', 'IT-001', 10041, NULL),
(2, 'Mouse', 'IT-002', 90, NULL),
(3, 'Keyboard', 'IT-003', 100020, NULL),
(4, 'Laptop Acer Aspire 5', 'LAP-ACER-A5', 10, 1),
(5, 'Printer Epson L3110', 'PRN-EP-L3110', 5, 1),
(6, 'Meja Kantor Kayu', 'MEJ-KAY-01', 15, 2),
(7, 'Kursi Putar Hitam', 'KRS-PTR-HTM', 20, 2),
(8, 'Proyektor BenQ MX532', 'PRJ-BQ-532', 3, 1);

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `id` int NOT NULL,
  `admin_id` int NOT NULL,
  `item_id` int NOT NULL,
  `supplier_id` int NOT NULL,
  `qty` int NOT NULL,
  `price_total` decimal(12,2) NOT NULL,
  `note_file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `purchase_code` varchar(60) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `unit_price` decimal(12,2) DEFAULT NULL,
  `payment_method` varchar(30) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `note_text` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `receivings`
--

CREATE TABLE `receivings` (
  `id` int NOT NULL,
  `receiver_id` int NOT NULL,
  `receiver_name` varchar(120) NOT NULL,
  `item_id` int NOT NULL,
  `supplier_id` int NOT NULL,
  `qty` int NOT NULL,
  `unit_price` decimal(12,2) DEFAULT '0.00',
  `total_price` decimal(12,2) DEFAULT '0.00',
  `payment_method` varchar(30) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `note_text` varchar(255) DEFAULT NULL,
  `receiving_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reimbursements`
--

CREATE TABLE `reimbursements` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `receipt_file` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `item_id` int NOT NULL,
  `qty` int NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `department` varchar(120) DEFAULT NULL,
  `request_date` date DEFAULT NULL,
  `photo_file` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(2, 'admin_stok'),
(1, 'admin_utama'),
(3, 'atasan'),
(4, 'user');

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int NOT NULL,
  `item_id` int NOT NULL,
  `type` enum('in','out') NOT NULL,
  `qty` int NOT NULL,
  `source` varchar(50) DEFAULT NULL,
  `ref_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `note_text` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `item_id`, `type`, `qty`, `source`, `ref_id`, `created_at`, `note_text`) VALUES
(1, 1, 'in', 12, 'manual', NULL, '2025-11-14 12:51:54', NULL),
(2, 1, 'in', 6, 'manual', NULL, '2025-11-14 12:55:15', NULL),
(3, 1, 'in', 6, 'manual', NULL, '2025-11-14 13:36:42', NULL),
(4, 3, 'in', 10, 'manual', NULL, '2025-11-14 13:36:56', NULL),
(5, 3, 'out', 10, 'request', 1, '2025-11-14 13:41:02', NULL),
(6, 3, 'in', 1, 'purchase', 1, '2025-11-14 13:57:28', NULL),
(7, 1, 'in', 1, 'purchase', 2, '2025-11-14 14:43:39', NULL),
(8, 1, 'out', 1, 'request', 2, '2025-11-14 14:45:11', NULL),
(9, 1, 'in', 1, 'purchase', 3, '2025-11-14 14:49:40', NULL),
(10, 1, 'out', 1, 'request', 3, '2025-11-14 14:51:29', NULL),
(11, 1, 'in', 10, 'manual', NULL, '2025-11-14 14:52:03', NULL),
(12, 2, 'in', 1, 'purchase', 4, '2025-11-14 14:54:27', NULL),
(13, 3, 'in', 10, 'manual', NULL, '2025-11-14 14:55:13', NULL),
(14, 3, 'out', 1, 'request', 4, '2025-11-14 14:56:32', NULL),
(15, 1, 'in', 10000, 'purchase', 5, '2025-11-14 15:44:36', NULL),
(16, 3, 'in', 100000, 'receiving', 1, '2025-11-14 15:52:44', NULL),
(17, 2, 'in', 23, 'manual', NULL, '2025-11-13 17:00:00', ''),
(18, 2, 'in', 23, 'manual', NULL, '2025-11-13 17:00:00', ''),
(19, 1, 'in', 2, 'manual', NULL, '2025-11-13 17:00:00', ''),
(20, 2, 'in', 23, 'manual', NULL, '2025-11-13 17:00:00', '');

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements_proofs`
--

CREATE TABLE `stock_movements_proofs` (
  `id` int NOT NULL,
  `movement_id` int NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `stock_movements_proofs`
--

INSERT INTO `stock_movements_proofs` (`id`, `movement_id`, `file_path`, `created_at`) VALUES
(1, 13, 'uploads/20251114_145513_1028_WhatsApp_Image_2025-11-13_at_20.09.33.jpeg', '2025-11-14 14:55:13');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int NOT NULL,
  `name` varchar(120) NOT NULL,
  `contact` varchar(120) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `pic_name` varchar(120) DEFAULT NULL,
  `pic_title` varchar(120) DEFAULT NULL,
  `phone` varchar(60) DEFAULT NULL,
  `payment_method` varchar(30) DEFAULT NULL,
  `status` varchar(30) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `supply_type` varchar(120) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact`, `email`, `pic_name`, `pic_title`, `phone`, `payment_method`, `status`, `address`, `supply_type`) VALUES
(1, 'PT Sumber Teknologi', '0812-3456-7890', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int NOT NULL,
  `username` varchar(60) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `job_title` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role_id`, `username`, `phone`, `job_title`, `department`) VALUES
(1, 'Admin Utama', 'admin@demo.com', '$2y$10$K/cwo0dfZ3G24aL5vbc7iutTGFrNeU.dPwIMcyW3Ho0P5ZvihFZ1K', 1, NULL, NULL, NULL, NULL),
(2, 'Admin Stok', 'stok@demo.com', '$2y$10$o2hvsDljzdw63uI3DZcPd.5abluB.3rjGcdJGtPlWoBM34afXB/hO', 2, NULL, NULL, NULL, NULL),
(3, 'Atasan', 'atasan@demo.com', '$2y$10$.kZ/1rYou/h8FzkZEDAjaOUNgjL0bn05.RU.JRWBGn2xTGm/bd41C', 3, NULL, NULL, NULL, NULL),
(4, 'User', 'user@demo.com', '$2y$10$AgAsvPp9nPkNfpRSza7yNemPuuRhBkf8C8uJ5jTUr03yz5PHsJByC', 4, NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `receivings`
--
ALTER TABLE items
ADD COLUMN tanggal DATE,
ADD COLUMN item VARCHAR(255),
ADD COLUMN harga INT;


--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `company_settings`
--
ALTER TABLE `company_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `fk_items_category` (`category_id`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `receivings`
--
ALTER TABLE `receivings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receiver_id` (`receiver_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `reimbursements`
--
ALTER TABLE `reimbursements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `stock_movements_proofs`
--
ALTER TABLE `stock_movements_proofs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_movement_proof_admin` (`movement_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `company_settings`
--
ALTER TABLE `company_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `receivings`
--
ALTER TABLE `receivings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reimbursements`
--
ALTER TABLE `reimbursements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `stock_movements_proofs`
--
ALTER TABLE `stock_movements_proofs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `fk_items_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `purchases_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  ADD CONSTRAINT `purchases_ibfk_3` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `receivings`
--
ALTER TABLE `receivings`
  ADD CONSTRAINT `receivings_ibfk_1` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `receivings_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  ADD CONSTRAINT `receivings_ibfk_3` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `reimbursements`
--
ALTER TABLE `reimbursements`
  ADD CONSTRAINT `reimbursements_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`);

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`);

--
-- Constraints for table `stock_movements_proofs`
--
ALTER TABLE `stock_movements_proofs`
  ADD CONSTRAINT `fk_movement_proof_admin` FOREIGN KEY (`movement_id`) REFERENCES `stock_movements` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
