-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 28, 2026 at 01:30 PM
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
-- Database: `hypercar_parking`
--

-- --------------------------------------------------------

--
-- Table structure for table `history`
--

CREATE TABLE `history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `plate_number` varchar(20) DEFAULT NULL,
  `owner_name` varchar(100) DEFAULT NULL,
  `slot_number` int(11) DEFAULT NULL,
  `time_in` datetime DEFAULT NULL,
  `time_out` datetime DEFAULT NULL,
  `total_fee` int(11) DEFAULT NULL,
  `overtime_hours` int(11) DEFAULT 0,
  `overtime_fee` decimal(10,2) DEFAULT 0.00,
  `source` enum('walk-in','user','reservation') DEFAULT 'walk-in',
  `payment_method` varchar(30) DEFAULT 'Cash',
  `payment_verified` tinyint(1) DEFAULT 0,
  `verified_by` varchar(50) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parking_sessions`
--

CREATE TABLE `parking_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `plate_number` varchar(20) DEFAULT NULL,
  `slot_number` int(11) DEFAULT NULL,
  `plan_name` varchar(50) DEFAULT NULL,
  `duration_hours` int(11) DEFAULT NULL,
  `overtime_hours` int(11) DEFAULT 0,
  `time_in` datetime DEFAULT current_timestamp(),
  `time_out` datetime DEFAULT NULL,
  `total_fee` decimal(10,2) DEFAULT NULL,
  `overtime_fee` decimal(10,2) DEFAULT 0.00,
  `payment_method` varchar(30) DEFAULT NULL,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `exit_requested` tinyint(1) DEFAULT 0,
  `exit_requested_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `plate_number` varchar(20) DEFAULT NULL,
  `slot_number` int(11) DEFAULT NULL,
  `checkin_code` varchar(20) DEFAULT NULL,
  `qr_token` varchar(64) DEFAULT NULL,
  `plan_name` varchar(50) DEFAULT NULL,
  `duration_hours` int(11) DEFAULT NULL,
  `reservation_date` date DEFAULT NULL,
  `reservation_time` time DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `payment_method` varchar(30) DEFAULT NULL,
  `status` enum('pending','approved','rejected','completed','cancelled') DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approved_by` varchar(50) DEFAULT NULL,
  `checked_in_at` datetime DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejected_reason` varchar(255) DEFAULT NULL,
  `expiry_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `user_id`, `vehicle_id`, `plate_number`, `slot_number`, `checkin_code`, `qr_token`, `plan_name`, `duration_hours`, `reservation_date`, `reservation_time`, `price`, `payment_method`, `status`, `admin_note`, `approved_at`, `approved_by`, `checked_in_at`, `rejected_at`, `rejected_reason`, `expiry_at`, `created_at`) VALUES
(1, 5, 4, 'CURR-30', NULL, 'B9C073', '38f1b6cf956c47df500209a0b5946f96', '1 Hour', 1, '2027-03-24', '14:13:00', 30.00, 'Cash', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-28 11:26:49'),
(2, 5, 3, 'DUR-274', NULL, '713385', 'ef5db656780c39e1a9f9e535093ad999', '8 Hours', 8, '2027-12-04', '00:34:00', 180.00, 'Maya', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-28 11:27:20'),
(3, 5, 1, 'MELO-6767', NULL, 'CA83EF', '0e8dccda754716f25f4f8ba41f8e4ba2', '24 Hours', 24, '2027-03-21', '14:34:00', 400.00, 'Cash', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-09-28 11:27:52');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `status` enum('active','banned') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `phone`, `username`, `password`, `role`, `status`, `created_at`) VALUES
(1, 'Aldiano Jully bert', 'botchoke2@gmail.com', '09569943452', 'Buburt', '$2y$10$QVbwM3P3Kly77/ORbERePu0fnR0WCR0yOvk8b8W00sEClpZTDigrO', 'user', 'active', '2026-09-27 07:32:37'),
(2, NULL, NULL, NULL, 'Admin', '$2y$10$t6HS2gaq0pUgepp8k7h6/.MeLRlpUDY0NQZi0WXKlvkHtjMrgg6oC', 'admin', 'active', '2026-09-27 08:49:04'),
(3, 'Charles', 'aldianojullybert@gmail.com', '09565389847', 'charles', '$2y$10$add82KgwGG0su8ibEbUyluVKXdy7dJKFodfFhTJEw5V9Pbof5f1.a', 'user', 'active', '2026-09-27 11:57:44'),
(4, NULL, NULL, NULL, 'Admins', '$2y$10$4goKYgobFj0Rr0IXjjmTY.JhmG0IHdwVq.Fmc5Ex/RRnPjXBPAsGu', 'admin', 'active', '2026-09-27 13:54:30'),
(5, 'System', 'system@gmail.com', '091234567891', 'System', '$2y$10$lbVcEk2R3t8tWMYIk7rdauP4scucjgBhSPREeF.dHez86b10DgV5y', 'user', 'active', '2026-09-27 14:07:43'),
(6, NULL, NULL, NULL, 'Owner', '$2y$10$uRWvba6qx49F9uJll1T6T.uuvntJ1APiV0Kpj.QOJzxRiejvlnuAm', 'admin', 'active', '2026-09-27 14:11:16');

-- --------------------------------------------------------

--
-- Table structure for table `user_vehicles`
--

CREATE TABLE `user_vehicles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `plate_number` varchar(20) DEFAULT NULL,
  `vehicle_type` varchar(50) DEFAULT NULL,
  `make` varchar(50) DEFAULT NULL,
  `color` varchar(30) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `category` enum('Regular','Premium','Motorcycle','Truck') DEFAULT 'Regular',
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_vehicles`
--

INSERT INTO `user_vehicles` (`id`, `user_id`, `plate_number`, `vehicle_type`, `make`, `color`, `model`, `year`, `category`, `photo`, `created_at`) VALUES
(1, 5, 'MELO-6767', 'Sports Car', 'toyota', 'yellow', 'supra', 2023, 'Premium', 'uploads/vehicles/vehicle_5_1790594581_2830.webp', '2026-09-28 11:23:01'),
(3, 5, 'DUR-274', 'SUV', 'toyota', 'red', 'civic', 2015, 'Regular', 'uploads/vehicles/vehicle_5_1790594674_5672.jpg', '2026-09-28 11:24:34'),
(4, 5, 'CURR-30', 'Sports Car', 'mclaren', 'yellow', 'moto', 2013, 'Premium', 'uploads/vehicles/vehicle_5_1790594773_1069.jpg', '2026-09-28 11:26:13');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `source` enum('walk-in','user','reservation') DEFAULT 'walk-in',
  `plate_number` varchar(20) DEFAULT NULL,
  `owner_name` varchar(100) DEFAULT NULL,
  `slot_number` int(11) DEFAULT NULL,
  `time_in` datetime DEFAULT current_timestamp(),
  `fee` int(11) DEFAULT 25
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `history`
--
ALTER TABLE `history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `parking_sessions`
--
ALTER TABLE `parking_sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_vehicles`
--
ALTER TABLE `user_vehicles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `history`
--
ALTER TABLE `history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parking_sessions`
--
ALTER TABLE `parking_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_vehicles`
--
ALTER TABLE `user_vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
