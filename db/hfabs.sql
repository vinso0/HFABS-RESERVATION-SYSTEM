-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 14, 2026 at 07:58 AM
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
  `status` enum('active','inactive') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branch`
--

INSERT INTO `branch` (`branch_id`, `branch_name`, `branch_location`, `contact_number`, `opening_time`, `closing_time`, `down_payment_rate`, `email`, `status`) VALUES
(1, 'caloocan branch', '102 Caimito Rd., Caloocan City, Unit 1D, Caimito Place', '09054543104', '10:00:00', '21:00:00', 0.5000, 'hfabscal@gmail.com', 'active'),
(2, 'quezon city branch', '850 Atherton, Quezon City', '0946 178 23', '08:00:00', '20:00:00', 0.5000, 'hfabsqc@gmail.com', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `branch_category_overrides`
--

CREATE TABLE `branch_category_overrides` (
  `branch_category_override_id` int(11) NOT NULL,
  `branch_id` int(15) NOT NULL,
  `default_category_id` int(11) NOT NULL,
  `display_name` varchar(50) DEFAULT NULL,
  `description_override` text DEFAULT NULL,
  `capacity_override` int(11) DEFAULT NULL,
  `is_active_override` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branch_category_overrides`
--

INSERT INTO `branch_category_overrides` (`branch_category_override_id`, `branch_id`, `default_category_id`, `display_name`, `description_override`, `capacity_override`, `is_active_override`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, NULL, 5, NULL, '2026-02-07 06:29:09', '2026-02-07 14:41:00'),
(2, 2, 1, NULL, NULL, NULL, NULL, '2026-02-07 06:29:09', '2026-02-07 06:29:09'),
(3, 1, 2, NULL, NULL, NULL, NULL, '2026-02-07 06:29:09', '2026-02-07 06:29:09'),
(4, 2, 2, NULL, NULL, NULL, NULL, '2026-02-07 06:29:09', '2026-02-07 06:29:09'),
(5, 1, 3, NULL, NULL, NULL, NULL, '2026-02-07 06:29:09', '2026-02-07 06:29:09'),
(6, 2, 3, NULL, NULL, NULL, NULL, '2026-02-07 06:29:09', '2026-02-07 06:29:09'),
(7, 1, 4, NULL, NULL, NULL, NULL, '2026-02-07 06:29:09', '2026-02-07 06:29:09'),
(8, 2, 4, NULL, NULL, NULL, NULL, '2026-02-07 06:29:09', '2026-02-07 06:29:09');

-- --------------------------------------------------------

--
-- Table structure for table `branch_service_overrides`
--

CREATE TABLE `branch_service_overrides` (
  `branch_service_override_id` int(11) NOT NULL,
  `branch_id` int(15) NOT NULL,
  `default_service_id` int(11) NOT NULL,
  `display_name` varchar(60) DEFAULT NULL,
  `description_override` text DEFAULT NULL,
  `duration_minutes_override` int(11) DEFAULT NULL,
  `price_override` decimal(10,2) DEFAULT NULL,
  `is_available_override` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branch_service_overrides`
--

INSERT INTO `branch_service_overrides` (`branch_service_override_id`, `branch_id`, `default_service_id`, `display_name`, `description_override`, `duration_minutes_override`, `price_override`, `is_available_override`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Hair Spa Treatment', 'Deep conditioning hair treatment', 60, 800.00, 1, '2026-02-07 06:27:40', '2026-02-14 06:13:28'),
(2, 2, 1, NULL, NULL, NULL, NULL, NULL, '2026-02-07 06:27:40', '2026-02-07 06:27:40'),
(3, 1, 2, NULL, NULL, NULL, NULL, NULL, '2026-02-07 06:27:40', '2026-02-07 06:27:40'),
(4, 2, 2, NULL, NULL, NULL, NULL, NULL, '2026-02-07 06:27:40', '2026-02-07 06:27:40'),
(5, 1, 7, 'Keratin Treatment', 'Smoothing keratin therapy', 90, 4500.00, 0, '2026-02-07 06:27:40', '2026-02-14 06:16:29'),
(6, 2, 7, NULL, NULL, NULL, NULL, NULL, '2026-02-07 06:27:40', '2026-02-07 06:27:40'),
(7, 1, 8, 'Hair Botox', 'Deep repair treatment', 120, 3800.00, 1, '2026-02-07 06:27:40', '2026-02-13 05:44:25'),
(8, 2, 8, NULL, NULL, NULL, NULL, NULL, '2026-02-07 06:27:40', '2026-02-07 06:27:40'),
(9, 1, 9, NULL, NULL, NULL, NULL, NULL, '2026-02-07 06:27:40', '2026-02-07 06:27:40'),
(10, 2, 9, NULL, NULL, NULL, NULL, 0, '2026-02-07 06:27:40', '2026-02-07 06:30:39'),
(11, 1, 10, NULL, NULL, NULL, NULL, NULL, '2026-02-07 06:27:40', '2026-02-07 06:27:40'),
(12, 2, 10, NULL, NULL, NULL, NULL, 0, '2026-02-07 06:27:40', '2026-02-07 06:30:39'),
(13, 1, 11, NULL, NULL, NULL, NULL, NULL, '2026-02-07 06:27:40', '2026-02-07 06:27:40'),
(14, 2, 11, NULL, NULL, NULL, NULL, 0, '2026-02-07 06:27:40', '2026-02-07 06:30:39'),
(15, 1, 3, NULL, NULL, NULL, NULL, NULL, '2026-02-07 06:27:40', '2026-02-07 06:27:40'),
(16, 2, 3, NULL, NULL, NULL, NULL, NULL, '2026-02-07 06:27:40', '2026-02-07 06:27:40'),
(17, 1, 4, NULL, NULL, NULL, NULL, NULL, '2026-02-07 06:27:40', '2026-02-07 06:27:40'),
(18, 2, 4, NULL, NULL, NULL, NULL, NULL, '2026-02-07 06:27:40', '2026-02-07 06:27:40'),
(19, 1, 5, NULL, NULL, NULL, NULL, NULL, '2026-02-07 06:27:40', '2026-02-07 06:27:40'),
(20, 2, 5, NULL, NULL, NULL, NULL, NULL, '2026-02-07 06:27:40', '2026-02-07 06:27:40'),
(22, 2, 6, NULL, NULL, NULL, NULL, NULL, '2026-02-07 06:27:40', '2026-02-07 06:27:40');

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
(1, 1, 'Hair Spa Treatment', 'Deep conditioning hair treatment', 60, 800.00, 1),
(2, 1, 'Hair Rebonding', 'Permanent hair straightening', 120, 3500.00, 1),
(3, 3, 'Classic Manicure', 'Basic nail care and polish', 60, 350.00, 1),
(4, 3, 'Gel Pedicure', 'Long-lasting gel nail treatment', 90, 600.00, 1),
(5, 4, 'Deep Cleansing Facial', 'Deep pore cleansing facial', 60, 1200.00, 1),
(6, 4, 'Anti-Aging Facial', 'Rejuvenating facial treatment', 60, 2000.00, 1),
(7, 1, 'Keratin Treatment', 'Smoothing keratin therapy', 90, 4500.00, 1),
(8, 1, 'Hair Botox', 'Deep repair treatment', 120, 3800.00, 1),
(9, 2, 'Swedish Massage', 'Relaxing full body massage', 60, 1500.00, 1),
(10, 2, 'Hot Stone Therapy', 'Therapeutic hot stone massage', 60, 2000.00, 1),
(11, 2, 'Aromatherapy Massage', 'Essential oil massage therapy', 60, 1600.00, 1);

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
(1, 'hair', 'hair related services', 10),
(2, 'massage', 'face related services', 10),
(3, 'nail', 'nail related services', 10),
(4, 'facial', 'facial related services', 10);

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
(1, 1, 2, 1, 1, 'Good service', '2026-01-09 11:40:45', '2026-02-14 14:10:59');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `paymongo_payment_id` varchar(255) DEFAULT NULL,
  `reservation_id` int(11) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `payment_method` enum('gcash','maya') NOT NULL,
  `status` enum('paid','unpaid') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `paymongo_payment_id`, `reservation_id`, `amount_paid`, `payment_method`, `status`, `created_at`) VALUES
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
  `branch_id` int(11) NOT NULL,
  `reservation_date` date NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('confirmed','no-show','completed','rescheduled','cancelled') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`reservation_id`, `user_id`, `branch_id`, `reservation_date`, `total_price`, `status`, `created_at`) VALUES
(1, 2, 1, '2026-01-09', 1600.00, 'cancelled', '2026-01-09 11:42:11'),
(2, 6, 1, '2026-02-14', 750.00, 'confirmed', '2026-02-13 16:40:37'),
(3, 2, 1, '2026-02-14', 750.00, 'completed', '2026-02-14 02:54:38'),
(4, 2, 1, '2026-02-13', 350.00, 'confirmed', '2026-02-14 06:06:27');

-- --------------------------------------------------------

--
-- Table structure for table `reservation_schedule`
--

CREATE TABLE `reservation_schedule` (
  `reservation_schedule_id` int(11) NOT NULL,
  `reservation_service_id` int(11) NOT NULL,
  `schedule_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_rescheduled` tinyint(1) DEFAULT NULL,
  `previous_schedule_id` int(11) DEFAULT NULL,
  `reschedule_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservation_schedule`
--

INSERT INTO `reservation_schedule` (`reservation_schedule_id`, `reservation_service_id`, `schedule_date`, `start_time`, `end_time`, `is_rescheduled`, `previous_schedule_id`, `reschedule_reason`) VALUES
(2, 1, '2026-01-20', '14:00:00', '15:00:00', NULL, NULL, NULL),
(3, 2, '2026-02-16', '14:00:00', '15:00:00', NULL, NULL, NULL),
(4, 3, '2026-02-14', '16:00:00', '17:00:00', NULL, NULL, NULL),
(5, 4, '2026-02-16', '10:00:00', '11:00:00', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reservation_services`
--

CREATE TABLE `reservation_services` (
  `reservation_service_id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `default_service_id` int(11) NOT NULL,
  `branch_service_override_id` int(11) DEFAULT NULL,
  `remaining_balance` decimal(10,2) NOT NULL,
  `booked_service_name` varchar(60) NOT NULL,
  `booked_description` text NOT NULL,
  `booked_duration_minutes` int(11) NOT NULL,
  `booked_unit_price` decimal(10,2) NOT NULL,
  `booked_category_name` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservation_services`
--

INSERT INTO `reservation_services` (`reservation_service_id`, `reservation_id`, `default_service_id`, `branch_service_override_id`, `remaining_balance`, `booked_service_name`, `booked_description`, `booked_duration_minutes`, `booked_unit_price`, `booked_category_name`) VALUES
(1, 1, 11, NULL, 800.00, 'Aromatherapy Massage', 'Essential oil massage therapy', 60, 1600.00, 'massage'),
(2, 2, 1, 1, 375.00, 'Hair Spa Treatment', 'Deep conditioning hair treatment', 60, 750.00, 'hair'),
(3, 3, 1, 1, 375.00, 'Hair Spa Treatment', 'Deep conditioning hair treatment', 60, 750.00, 'hair'),
(4, 4, 3, NULL, 125.00, 'Classic Manicure', 'Basic nail care and polish', 60, 350.00, 'nail');

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
(1, 'admin', 'admin@admin', '', '$2y$10$FoP25bz38JnM5X.4tsIbo.tvJ.TkhPUSziAwgdSNG7Ugk310i1iOS', 'admin', 1, 1, NULL, '2026-01-09 11:42:55'),
(2, 'customer', 'cus@cus', '', '$2y$10$0LuBPG.O6c6cAEuQSftB6evQcj05FpLu955hneNKqwtwE7tiN4RHy', 'customer', NULL, 1, NULL, '2026-01-09 11:43:23'),
(3, 'superadmin', 'super@super', '', '$2y$10$I1.9ERjHmtwVbWKKA.5s5eS0nmxIt7lccNII7hTCPgmkc1U9fJd1C', 'superadmin', NULL, 1, NULL, '2026-01-16 02:38:57'),
(4, 'kier', 'kier@kier', '123456789', '$2y$10$Gt/Eb/Wbx0D3ClCzf/Qqx.nv.bisN7Chveadx5Vhk354vBVEwPzx6', 'customer', NULL, 1, NULL, '2026-01-17 07:32:57'),
(5, 'Kierloyd Vince Schofield', 'kier@email', '912345789', '$2y$10$zbOE4cRMEc4TOOsRE/.gae4eZKrbAl8.XwkLjUWVwZUmTT9gJ173.', 'customer', NULL, 1, NULL, '2026-01-17 08:34:32'),
(6, 'vin', 'vin@gmail.com', '09103452674', '$2y$10$xeMnSgBQR2O7VWYNoCXDkuJCiKC1buOj2R9QQjkpwhguHC2czkXvG', 'customer', NULL, 1, NULL, '2026-02-13 16:39:27');

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
-- Indexes for table `branch_category_overrides`
--
ALTER TABLE `branch_category_overrides`
  ADD PRIMARY KEY (`branch_category_override_id`),
  ADD UNIQUE KEY `uq_branch_category` (`branch_id`,`default_category_id`),
  ADD KEY `idx_default_category_id` (`default_category_id`);

--
-- Indexes for table `branch_service_overrides`
--
ALTER TABLE `branch_service_overrides`
  ADD PRIMARY KEY (`branch_service_override_id`),
  ADD UNIQUE KEY `uq_branch_service` (`branch_id`,`default_service_id`),
  ADD KEY `idx_defaultserviceid` (`default_service_id`),
  ADD KEY `idx_branchid` (`branch_id`);

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
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD UNIQUE KEY `reservation_id` (`reservation_service_id`),
  ADD KEY `user_feedback` (`user_id`),
  ADD KEY `branch_feedback` (`branch_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `idx_payments_reservation_id` (`reservation_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `user_reservation` (`user_id`),
  ADD KEY `branch_reservation` (`branch_id`);

--
-- Indexes for table `reservation_schedule`
--
ALTER TABLE `reservation_schedule`
  ADD PRIMARY KEY (`reservation_schedule_id`),
  ADD KEY `prev_sched` (`previous_schedule_id`),
  ADD KEY `res_service` (`reservation_service_id`);

--
-- Indexes for table `reservation_services`
--
ALTER TABLE `reservation_services`
  ADD PRIMARY KEY (`reservation_service_id`),
  ADD KEY `reservation_service` (`default_service_id`),
  ADD KEY `service_reservation` (`reservation_id`),
  ADD KEY `idx_default_service_id` (`default_service_id`),
  ADD KEY `idx_branch_service_override_id` (`branch_service_override_id`);

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
-- AUTO_INCREMENT for table `branch_category_overrides`
--
ALTER TABLE `branch_category_overrides`
  MODIFY `branch_category_override_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `branch_service_overrides`
--
ALTER TABLE `branch_service_overrides`
  MODIFY `branch_service_override_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

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
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reservation_schedule`
--
ALTER TABLE `reservation_schedule`
  MODIFY `reservation_schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reservation_services`
--
ALTER TABLE `reservation_services`
  MODIFY `reservation_service_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
-- Constraints for table `branch_category_overrides`
--
ALTER TABLE `branch_category_overrides`
  ADD CONSTRAINT `fk_bco_branch` FOREIGN KEY (`branch_id`) REFERENCES `branch` (`branch_id`),
  ADD CONSTRAINT `fk_bco_default_category` FOREIGN KEY (`default_category_id`) REFERENCES `default_services_categories` (`service_category_id`);

--
-- Constraints for table `branch_service_overrides`
--
ALTER TABLE `branch_service_overrides`
  ADD CONSTRAINT `fk_bso_branch` FOREIGN KEY (`branch_id`) REFERENCES `branch` (`branch_id`),
  ADD CONSTRAINT `fk_bso_default_service` FOREIGN KEY (`default_service_id`) REFERENCES `default_services` (`service_id`);

--
-- Constraints for table `default_services`
--
ALTER TABLE `default_services`
  ADD CONSTRAINT `service_category` FOREIGN KEY (`category_id`) REFERENCES `default_services_categories` (`service_category_id`);

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `branch_feedback` FOREIGN KEY (`branch_id`) REFERENCES `branch` (`branch_id`),
  ADD CONSTRAINT `reservation_feedback` FOREIGN KEY (`reservation_service_id`) REFERENCES `reservation_services` (`reservation_service_id`),
  ADD CONSTRAINT `user_feedback` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_reservations` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`);

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `branch_reservation` FOREIGN KEY (`branch_id`) REFERENCES `branch` (`branch_id`),
  ADD CONSTRAINT `user_reservation` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `reservation_schedule`
--
ALTER TABLE `reservation_schedule`
  ADD CONSTRAINT `prev_sched` FOREIGN KEY (`previous_schedule_id`) REFERENCES `reservation_schedule` (`reservation_schedule_id`),
  ADD CONSTRAINT `res_service` FOREIGN KEY (`reservation_service_id`) REFERENCES `reservation_services` (`reservation_service_id`);

--
-- Constraints for table `reservation_services`
--
ALTER TABLE `reservation_services`
  ADD CONSTRAINT `fk_rs_branch_service_override` FOREIGN KEY (`branch_service_override_id`) REFERENCES `branch_service_overrides` (`branch_service_override_id`),
  ADD CONSTRAINT `fk_rs_default_service` FOREIGN KEY (`default_service_id`) REFERENCES `default_services` (`service_id`),
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
