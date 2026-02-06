-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 31, 2026 at 11:47 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hfabs`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(30) NOT NULL,
  `log_message` text NOT NULL,
  `branch_id` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`log_id`, `user_id`, `action`, `log_message`, `branch_id`, `timestamp`) VALUES
(4, 1, 'UPDATE', 'admin changed facial to 100', 1, '2026-01-09 11:40:28');

-- --------------------------------------------------------

--
-- Table structure for table `branch`
--

CREATE TABLE `branch` (
  `branch_id` int(15) NOT NULL,
  `branch_name` varchar(50) NOT NULL,
  `branch_location` text NOT NULL,
  `contact_number` varchar(11) NOT NULL,
  `opening_time` time NOT NULL,
  `closing_time` time NOT NULL,
  `down_payment_rate` decimal(5,4) NOT NULL,
  `email` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branch`
--

INSERT INTO `branch` (`branch_id`, `branch_name`, `branch_location`, `contact_number`, `opening_time`, `closing_time`, `down_payment_rate`, `email`, `status`) VALUES
(1, 'caloocan', '102 Caimito Rd., Caloocan City, Unit 1D, Caimito Place', '09054543104', '10:00:00', '21:00:00', 0.0000, '', ''),
(2, 'qc', '850 Atherton, Quezon City', '0946 178 23', '08:00:00', '00:00:00', 0.0000, '', '');

-- --------------------------------------------------------

--
-- Table structure for table `branch_services`
--

CREATE TABLE `branch_services` (
  `branch_service_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `service_name` varchar(30) NOT NULL,
  `description` text NOT NULL,
  `duration_minutes` int(11) NOT NULL,
  `price` decimal(6,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branch_services`
--

INSERT INTO `branch_services` (`branch_service_id`, `branch_id`, `category_id`, `service_name`, `description`, `duration_minutes`, `price`) VALUES
(1, 1, 1, '', '', 0, 0.00),
(2, 1, 1, '', '', 0, 0.00),
(3, 1, 3, '', '', 0, 0.00),
(4, 1, 3, '', '', 0, 0.00),
(5, 1, 4, '', '', 0, 0.00),
(6, 1, 4, '', '', 0, 0.00),
(7, 1, 1, '', '', 0, 0.00),
(8, 1, 1, '', '', 0, 0.00),
(9, 1, 2, '', '', 0, 0.00),
(10, 1, 2, '', '', 0, 0.00),
(11, 1, 2, '', '', 0, 0.00),
(14, 2, 1, '', '', 0, 0.00),
(15, 2, 1, '', '', 0, 0.00),
(16, 2, 4, '', '', 0, 0.00),
(17, 2, 4, '', '', 0, 0.00),
(18, 2, 3, '', '', 0, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `branch_services_categories`
--

CREATE TABLE `branch_services_categories` (
  `branch_services_category_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `category_name` varchar(30) NOT NULL,
  `description` text NOT NULL,
  `capacity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `default_services`
--

CREATE TABLE `default_services` (
  `service_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `service_name` varchar(30) NOT NULL,
  `description` text NOT NULL,
  `duration_minutes` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `default_services`
--

INSERT INTO `default_services` (`service_id`, `category_id`, `service_name`, `description`, `duration_minutes`, `price`, `is_available`) VALUES
(1, 1, 'Hair Spa Treatment', 'Deep conditioning hair treatment', 0, 1800.00, 1),
(2, 1, 'Hair Rebonding', 'Permanent hair straightening', 0, 3500.00, 1),
(3, 3, 'Classic Manicure', 'Basic nail care and polish', 0, 350.00, 1),
(4, 3, 'Gel Pedicure', 'Long-lasting gel nail treatment', 0, 600.00, 1),
(5, 4, 'Deep Cleansing Facial', 'Deep pore cleansing facial', 0, 1200.00, 1),
(6, 4, 'Anti-Aging Facial', 'Rejuvenating facial treatment', 0, 2000.00, 1),
(7, 1, 'Keratin Treatment', 'Smoothing keratin therapy', 0, 4500.00, 1),
(8, 1, 'Hair Botox', 'Deep repair treatment', 0, 3800.00, 1),
(9, 2, 'Swedish Massage', 'Relaxing full body massage', 0, 1500.00, 1),
(10, 2, 'Hot Stone Therapy', 'Therapeutic hot stone massage', 0, 2000.00, 1),
(11, 2, 'Aromatherapy Massage', 'Essential oil massage therapy', 0, 1600.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `default_services_categories`
--

CREATE TABLE `default_services_categories` (
  `service_category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `def_capacity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `default_services_categories`
--

INSERT INTO `default_services_categories` (`service_category_id`, `category_name`, `description`, `def_capacity`) VALUES
(1, 'hair', 'hair placeholder', 10),
(2, 'massage', 'face placeholder', 10),
(3, 'nail', 'nail placeholder', 10),
(4, 'facial', 'facial placeholder', 10);

-- --------------------------------------------------------

--
-- Table structure for table `down_payments`
--

CREATE TABLE `down_payments` (
  `down_payment_id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `down_payment_amount` decimal(6,2) NOT NULL,
  `transaction_proof_url` text NOT NULL,
  `payment_status` varchar(20) NOT NULL,
  `payment_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `down_payments`
--

INSERT INTO `down_payments` (`down_payment_id`, `reservation_id`, `down_payment_amount`, `transaction_proof_url`, `payment_status`, `payment_date`) VALUES
(1, 1, 900.00, 'placeholder', 'paid', '2026-01-15');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL,
  `reservation_service_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`feedback_id`, `reservation_service_id`, `user_id`, `branch_id`, `rating`, `comment`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 1, 5, 'good good good', '2026-01-09 11:40:45', '2026-01-31 17:59:05');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `order_status` enum('paid','failed','pending','') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `branch_id`, `total_amount`, `order_status`, `created_at`) VALUES
(1, 2, 1, 100.00, 'paid', '2026-01-09 11:41:01'),
(2, 2, 1, 150.00, 'pending', '2026-01-30 08:59:10');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `paymongo_payment_id` varchar(255) DEFAULT NULL,
  `order_id` int(11) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_method` enum('gcash','maya') NOT NULL,
  `status` enum('paid','unpaid') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `paymongo_payment_id`, `order_id`, `amount_paid`, `payment_method`, `status`, `created_at`) VALUES
(1, 'test_1769766719', 1, 100.00, 'gcash', 'paid', '2026-01-09 11:41:29'),
(2, NULL, 1, 100.00, 'gcash', 'paid', '2026-01-28 05:25:41'),
(4, 'paymongo_test_1769578052', 1, 150.00, 'gcash', 'paid', '2026-01-28 05:27:32'),
(5, 'pay_test_123', 1, 150.00, 'gcash', 'paid', '2026-01-28 07:09:30'),
(7, 'pay_test_123456789', 1, 150.00, 'gcash', 'paid', '2026-01-30 08:48:23'),
(8, NULL, 1, 100.00, 'gcash', 'paid', '2026-01-30 09:51:59');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `reservation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `reservation_date` date NOT NULL,
  `reservation_status` varchar(20) NOT NULL,
  `total_price` decimal(6,2) NOT NULL,
  `total_remaining_balance` decimal(6,2) NOT NULL,
  `status` enum('pending','confirmed','rescheduled','cancelled') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`reservation_id`, `user_id`, `order_id`, `branch_id`, `reservation_date`, `reservation_status`, `total_price`, `total_remaining_balance`, `status`, `created_at`) VALUES
(1, 2, 1, 1, '2026-01-09', '', 0.00, 0.00, 'confirmed', '2026-01-09 11:42:11');

-- --------------------------------------------------------

--
-- Table structure for table `reservation_schedule`
--

CREATE TABLE `reservation_schedule` (
  `schedule_id` int(11) NOT NULL,
  `reservation_service_id` int(11) NOT NULL,
  `schedule_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `schedule_status` varchar(30) NOT NULL,
  `is_rescheduled` tinyint(1) NOT NULL,
  `previous_schedule_id` int(11) NOT NULL,
  `reschedule_reason` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservation_services`
--

CREATE TABLE `reservation_services` (
  `reservation_service_id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `remaining_balance` decimal(6,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservation_services`
--

INSERT INTO `reservation_services` (`reservation_service_id`, `reservation_id`, `service_id`, `remaining_balance`) VALUES
(1, 1, 11, 999.99);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(30) NOT NULL,
  `email` varchar(30) NOT NULL,
  `contact_number` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','admin','superadmin') NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `contact_number`, `password`, `role`, `branch_id`, `is_active`, `deleted_at`, `created_at`) VALUES
(1, 'admin', 'admin@admin', '', '$2y$10$I1.9ERjHmtwVbWKKA.5s5eS0nmxIt7lccNII7hTCPgmkc1U9fJd1C', 'admin', 1, 1, NULL, '2026-01-09 11:42:55'),
(2, 'customer', 'cus@cus', '', '$2y$10$mCV9Q5Insk1wdOx9V.HOpO8/8wNrzx5U.vgHXlKbmNXgjPu4ehOxe', 'customer', NULL, 1, NULL, '2026-01-09 11:43:23'),
(3, 'superadmin', 'super@super', '', '$2y$10$I1.9ERjHmtwVbWKKA.5s5eS0nmxIt7lccNII7hTCPgmkc1U9fJd1C', 'superadmin', NULL, 1, NULL, '2026-01-16 02:38:57'),
(4, 'kier', 'kier@kier', '123456789', '$2y$10$Gt/Eb/Wbx0D3ClCzf/Qqx.nv.bisN7Chveadx5Vhk354vBVEwPzx6', 'customer', NULL, 1, NULL, '2026-01-17 07:32:57'),
(5, 'Kierloyd Vince Schofield', 'kier@email', '912345789', '$2y$10$zbOE4cRMEc4TOOsRE/.gae4eZKrbAl8.XwkLjUWVwZUmTT9gJ173.', 'customer', NULL, 1, NULL, '2026-01-17 08:34:32');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_audit` (`user_id`),
  ADD KEY `branch_audit` (`branch_id`);

--
-- Indexes for table `branch`
--
ALTER TABLE `branch`
  ADD PRIMARY KEY (`branch_id`);

--
-- Indexes for table `branch_services`
--
ALTER TABLE `branch_services`
  ADD PRIMARY KEY (`branch_service_id`),
  ADD KEY `category_branch_service` (`category_id`),
  ADD KEY `branch_service` (`branch_id`);

--
-- Indexes for table `branch_services_categories`
--
ALTER TABLE `branch_services_categories`
  ADD PRIMARY KEY (`branch_services_category_id`),
  ADD KEY `branch` (`branch_id`);

--
-- Indexes for table `default_services`
--
ALTER TABLE `default_services`
  ADD PRIMARY KEY (`service_id`),
  ADD KEY `service_category` (`category_id`);

--
-- Indexes for table `default_services_categories`
--
ALTER TABLE `default_services_categories`
  ADD PRIMARY KEY (`service_category_id`);

--
-- Indexes for table `down_payments`
--
ALTER TABLE `down_payments`
  ADD PRIMARY KEY (`down_payment_id`),
  ADD KEY `reservation_dp` (`reservation_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD UNIQUE KEY `reservation_id` (`reservation_service_id`),
  ADD KEY `user_feedback` (`user_id`),
  ADD KEY `branch_feedback` (`branch_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_order` (`user_id`),
  ADD KEY `branch_order` (`branch_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `order_payment` (`order_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `user_reservation` (`user_id`),
  ADD KEY `order_reservation` (`order_id`),
  ADD KEY `branch_reservation` (`branch_id`);

--
-- Indexes for table `reservation_schedule`
--
ALTER TABLE `reservation_schedule`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `prev_sched` (`previous_schedule_id`),
  ADD KEY `res_service` (`reservation_service_id`);

--
-- Indexes for table `reservation_services`
--
ALTER TABLE `reservation_services`
  ADD PRIMARY KEY (`reservation_service_id`),
  ADD KEY `reservation_service` (`service_id`),
  ADD KEY `service_reservation` (`reservation_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `user_branch` (`branch_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `branch`
--
ALTER TABLE `branch`
  MODIFY `branch_id` int(15) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `branch_services`
--
ALTER TABLE `branch_services`
  MODIFY `branch_service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `branch_services_categories`
--
ALTER TABLE `branch_services_categories`
  MODIFY `branch_services_category_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `default_services`
--
ALTER TABLE `default_services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `default_services_categories`
--
ALTER TABLE `default_services_categories`
  MODIFY `service_category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `down_payments`
--
ALTER TABLE `down_payments`
  MODIFY `down_payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reservation_schedule`
--
ALTER TABLE `reservation_schedule`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservation_services`
--
ALTER TABLE `reservation_services`
  MODIFY `reservation_service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `branch_audit` FOREIGN KEY (`branch_id`) REFERENCES `branch` (`branch_id`),
  ADD CONSTRAINT `user_audit` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `branch_services`
--
ALTER TABLE `branch_services`
  ADD CONSTRAINT `branch_service` FOREIGN KEY (`branch_id`) REFERENCES `branch` (`branch_id`),
  ADD CONSTRAINT `category_branch_service` FOREIGN KEY (`category_id`) REFERENCES `default_services_categories` (`service_category_id`);

--
-- Constraints for table `branch_services_categories`
--
ALTER TABLE `branch_services_categories`
  ADD CONSTRAINT `branch` FOREIGN KEY (`branch_id`) REFERENCES `branch` (`branch_id`);

--
-- Constraints for table `default_services`
--
ALTER TABLE `default_services`
  ADD CONSTRAINT `service_category` FOREIGN KEY (`category_id`) REFERENCES `default_services_categories` (`service_category_id`);

--
-- Constraints for table `down_payments`
--
ALTER TABLE `down_payments`
  ADD CONSTRAINT `reservation_dp` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`);

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `branch_feedback` FOREIGN KEY (`branch_id`) REFERENCES `branch` (`branch_id`),
  ADD CONSTRAINT `reservation_feedback` FOREIGN KEY (`reservation_service_id`) REFERENCES `reservation_services` (`reservation_service_id`),
  ADD CONSTRAINT `user_feedback` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `branch_order` FOREIGN KEY (`branch_id`) REFERENCES `branch` (`branch_id`),
  ADD CONSTRAINT `user_order` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `order_payment` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `branch_reservation` FOREIGN KEY (`branch_id`) REFERENCES `branch` (`branch_id`),
  ADD CONSTRAINT `order_reservation` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `user_reservation` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `reservation_schedule`
--
ALTER TABLE `reservation_schedule`
  ADD CONSTRAINT `prev_sched` FOREIGN KEY (`previous_schedule_id`) REFERENCES `reservation_schedule` (`schedule_id`),
  ADD CONSTRAINT `res_service` FOREIGN KEY (`reservation_service_id`) REFERENCES `reservation_services` (`reservation_service_id`);

--
-- Constraints for table `reservation_services`
--
ALTER TABLE `reservation_services`
  ADD CONSTRAINT `reservation_service` FOREIGN KEY (`service_id`) REFERENCES `default_services` (`service_id`),
  ADD CONSTRAINT `service_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `user_branch` FOREIGN KEY (`branch_id`) REFERENCES `branch` (`branch_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
