-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 28, 2025 at 03:12 PM
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
-- Database: `midnyt_fudtrip_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `reply` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `customer_name`, `message`, `reply`, `created_at`) VALUES
(1, 'Guest', 'hello po sir', 'good day po ano pong order nyo?', '2025-10-27 18:19:31'),
(2, 'Guest', 'h', NULL, '2025-10-27 20:52:31');

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `category` enum('Solo','Combo','Platter') NOT NULL,
  `price` decimal(9,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `status` enum('Available','Sold Out') DEFAULT 'Available',
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu`
--

INSERT INTO `menu` (`id`, `name`, `category`, `price`, `description`, `status`, `image`, `created_at`) VALUES
(2, 'Liemposilog', 'Solo', 100.00, '', 'Available', NULL, '2025-10-22 18:53:37'),
(3, 'Shanghaisilog', 'Solo', 100.00, '', 'Available', NULL, '2025-10-22 18:53:50'),
(4, 'Mix bulaklak/shanghai', 'Platter', 150.00, 'sold out', 'Available', NULL, '2025-10-22 19:31:28'),
(5, 'Tapsilog', 'Solo', 100.00, '', 'Available', NULL, '2025-10-26 23:20:31'),
(6, 'Tapshalak', 'Combo', 175.00, '', 'Available', NULL, '2025-10-26 23:20:46'),
(7, 'Chicksilog (thigh)', 'Solo', 110.00, '', 'Available', NULL, '2025-10-27 17:02:23'),
(8, 'Bulaklaksilog', 'Solo', 100.00, '', 'Available', NULL, '2025-10-27 17:02:32'),
(9, 'Liemshalak', 'Combo', 175.00, '', 'Available', NULL, '2025-10-27 17:02:40');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(150) NOT NULL,
  `address` text NOT NULL,
  `payment_method` enum('GCash','Cash') NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `menu_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('Pending','Completed') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_name`, `address`, `payment_method`, `total_amount`, `order_date`, `menu_id`, `quantity`, `total_price`, `status`, `created_at`) VALUES
(6, '', '', 'GCash', 0.00, '2025-10-27 17:22:56', 3, 1, 100.00, 'Completed', '2025-10-22 19:32:04'),
(7, '', '', 'GCash', 0.00, '2025-10-27 17:22:56', 2, 1, 100.00, 'Completed', '2025-10-22 19:32:07'),
(8, '', '', 'GCash', 0.00, '2025-10-27 17:22:56', 2, 1, 100.00, 'Completed', '2025-10-26 22:15:04'),
(9, 'Walk-in', '', 'GCash', 0.00, '2025-10-27 17:22:56', 3, 1, 100.00, 'Completed', '2025-10-26 22:20:44'),
(10, 'Walk-in', '', 'GCash', 0.00, '2025-10-27 17:22:56', 3, 1, 100.00, 'Completed', '2025-10-26 22:31:51'),
(11, '', '', 'GCash', 0.00, '2025-10-27 17:22:56', 2, 1, 100.00, 'Pending', '2025-10-27 17:03:12'),
(12, 'dion manalang', '', 'GCash', 0.00, '2025-10-27 17:22:56', 8, 1, 100.00, 'Completed', '2025-10-27 17:14:24'),
(15, 'dion manalang', 'bagong bayan 123', 'Cash', 0.00, '2025-10-28 14:04:46', 4, 1, 150.00, 'Pending', '2025-10-28 14:04:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `menu_id` (`menu_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
