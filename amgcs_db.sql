-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 03, 2025 at 03:34 AM
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
-- Database: `amgcs_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `alerts`
--

CREATE TABLE `alerts` (
  `alert_id` int(11) NOT NULL,
  `truck_id` int(11) NOT NULL,
  `trip_id` int(11) NOT NULL,
  `alert_type` enum('Missed Collection','Delay','Deviation') NOT NULL,
  `alert_message` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `employee_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `employee_status` enum('Active','Inactive','On Leave','Terminated') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_picture_filename` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`employee_id`, `user_id`, `first_name`, `middle_name`, `last_name`, `contact_number`, `employee_status`, `created_at`, `updated_at`, `profile_picture_filename`) VALUES
(1, 1, '', NULL, '', NULL, 'Active', '2025-03-22 05:30:01', '2025-03-22 05:30:01', NULL),
(2, 2, '', NULL, '', NULL, 'Inactive', '2025-03-22 05:45:41', '2025-04-21 05:38:38', NULL),
(3, 3, '', NULL, '', NULL, 'Active', '2025-03-22 05:56:04', '2025-04-15 10:07:08', NULL),
(4, 8, '', NULL, '', NULL, 'Active', '2025-03-30 11:44:37', '2025-04-15 11:08:54', NULL),
(5, 9, 'Joelle Joy', 'Mahinay', 'Eugenio', '', 'Active', '2025-04-02 09:28:36', '2025-08-30 03:15:22', '9_68b26cca5deac.jpg'),
(6, 10, '', NULL, '', NULL, 'Active', '2025-04-03 02:05:29', '2025-04-03 02:05:29', NULL),
(7, 11, '', NULL, '', NULL, 'Active', '2025-04-15 09:47:40', '2025-04-15 11:06:41', NULL),
(8, 13, '', NULL, '', NULL, 'Active', '2025-04-21 08:44:35', '2025-04-21 08:44:35', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `gps_tracking`
--

CREATE TABLE `gps_tracking` (
  `tracking_id` int(11) NOT NULL,
  `truck_id` int(11) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mucipalities_record`
--

CREATE TABLE `mucipalities_record` (
  `municipal_record_id` int(100) NOT NULL,
  `entry_date` date NOT NULL,
  `entry_time` time DEFAULT NULL,
  `lgu_municipality` varchar(100) DEFAULT NULL,
  `private_company` varchar(100) DEFAULT NULL,
  `estimated_volume_kgs` int(11) NOT NULL,
  `driver_name` varchar(100) DEFAULT NULL,
  `plate_number` varchar(20) NOT NULL,
  `estimated_volume_per_truck_kg` int(11) NOT NULL
) ;

--
-- Dumping data for table `mucipalities_record`
--

INSERT INTO `mucipalities_record` (`municipal_record_id`, `entry_date`, `entry_time`, `lgu_municipality`, `private_company`, `estimated_volume_kgs`, `driver_name`, `plate_number`, `estimated_volume_per_truck_kg`) VALUES
(1, '2025-04-28', '08:30:00', 'Santa Maria', NULL, 5000, 'Juan Dela Cruz', 'ABC-1234', 2500),
(2, '2025-04-28', '09:15:00', NULL, 'GreenEarth Corp', 4000, 'Maria Santos', 'XYZ-5678', 2000),
(3, '2025-04-28', '10:30:00', NULL, 'SM Rosales', 0, 'Joelle Joy', 'ABC-456', 2500),
(4, '2025-04-28', '10:33:00', 'San Carlos', NULL, 0, 'Kuromi', 'ABC-0987', 2500),
(5, '2025-04-28', '11:47:00', 'Bayambang', NULL, 0, 'Marco', 'EFG1234', 2500);

-- --------------------------------------------------------

--
-- Table structure for table `routes`
--

CREATE TABLE `routes` (
  `route_id` int(11) NOT NULL,
  `origin` varchar(100) NOT NULL,
  `destination` varchar(100) NOT NULL,
  `waypoints` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`waypoints`)),
  `estimated_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `schedule_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `route_description` varchar(500) NOT NULL,
  `truck_id` int(11) NOT NULL,
  `driver_name` varchar(150) NOT NULL,
  `waste_type` varchar(255) NOT NULL,
  `days` varchar(100) DEFAULT NULL,
  `quarter` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schedules`
--

INSERT INTO `schedules` (`schedule_id`, `date`, `start_time`, `end_time`, `route_description`, `truck_id`, `driver_name`, `waste_type`, `days`, `quarter`) VALUES
(11, '2025-04-30', '01:00:00', '21:00:00', 'Bayambang', 11, 'Wilson I. Guillermo', 'Biodegradable', NULL, 1),
(12, '2025-09-22', '06:25:00', '06:25:00', 'Brgy. San Juan', 10, 'Manla, Sonny Boy C.', 'Biodegradable', 'Wednesday', 1),
(13, '2025-09-23', '06:51:00', '17:00:00', 'San Pedro St.', 1, 'De Guzman, Jerry O.', 'Recyclable', 'Monday,Thursday,Friday', 1),
(14, '2025-09-24', '07:09:00', '17:30:00', 'Mangga St.', 2, 'Briones, Rodel M.', 'Recyclable', 'Friday,Saturday', 1),
(15, '2025-09-25', '07:14:00', '12:30:00', 'Apple St.', 11, 'Lacar, Teodorico L.', 'Residual', 'Thursday,Sunday', 1);

-- --------------------------------------------------------

--
-- Table structure for table `trip_logs`
--

CREATE TABLE `trip_logs` (
  `trip_id` int(11) NOT NULL,
  `truck_id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `route_id` int(11) DEFAULT NULL,
  `origin` varchar(100) NOT NULL,
  `destination` varchar(100) NOT NULL,
  `departure_time` datetime NOT NULL,
  `arrival_time` datetime DEFAULT NULL,
  `distance_travelled` decimal(10,2) DEFAULT NULL,
  `missed_collections` int(11) DEFAULT 0,
  `status` enum('Ongoing','Completed','Delayed','Cancelled') DEFAULT 'Ongoing'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `truck_driver`
--

CREATE TABLE `truck_driver` (
  `driver_id` int(11) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `contact_no` varchar(15) NOT NULL,
  `truck_id` int(11) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `truck_driver`
--

INSERT INTO `truck_driver` (`driver_id`, `first_name`, `middle_name`, `last_name`, `contact_no`, `truck_id`, `status`, `user_id`) VALUES
(1, 'Wilson', 'I.', 'Guillermo', '0956-321-9742', 1, 'Active', NULL),
(2, 'Ritchie', 'J.', 'Tablada', '0963-859-0058', 2, 'Active', NULL),
(3, 'Francisco', 'D.', 'Mansat Jr.', '0963-850-5112', 3, 'Active', NULL),
(4, 'Froilan', 'L.', 'Bautista', '0945-211-3894', 4, 'Active', NULL),
(5, 'Jerry', 'O.', 'De Guzman', '0916-148-6550', 5, 'Active', 9),
(6, 'Ferdinand', 'Q.', 'Saguiped', '0985-269-8398', 6, 'Active', NULL),
(7, 'Jerry', 'U.', 'Morales', '0946-691-6830', 7, 'Active', NULL),
(8, 'Dennis', 'B.', 'Venzon', '0910-011-5292', 8, 'Active', NULL),
(9, 'Alex', 'A.', 'Ragos', '0918-281-2582', 9, 'Active', NULL),
(10, 'Eugene', 'Q.', 'Asuncion', '0945-744-8860', 10, 'Active', NULL),
(11, 'Teodorico', 'L.', 'Lacar', '0910-008-1128', 11, 'Active', NULL),
(12, 'Gemmark', 'B.', 'Eleccion', '0977-619-1439', 12, 'Active', NULL),
(13, 'Sonny Boy', 'C. ', 'Manla', '0926-647-7241', 13, 'Active', NULL),
(14, 'Augusto', 'G.', 'Cavero', '0995-515-2732', 14, 'Active', NULL),
(15, 'Rodolfo', 'A.', 'Antuerpia', '0994-839-7241', 15, 'Active', NULL),
(16, 'Rodel', 'M.', 'Briones', '0918-584-3562', 16, 'Active', NULL),
(17, 'Angelo', 'E.', 'Serain', '0992-608-5561', 17, 'Active', NULL),
(18, 'Ranilo', 'P.', 'Canceran', '0992-447-5639', 17, 'Active', NULL),
(19, 'Joel', 'D.', 'Mecha', '0966-792-2169', 18, 'Active', NULL),
(20, 'Gielord Kim', 'T.', 'Mendez', '0945-841-6567', 19, 'Active', NULL),
(21, 'Marvin', 'L.', 'Mamasig', '0967-108-1349', 20, 'Active', NULL),
(22, 'Marco', 'M.', 'Eleccion', '0945-271-9256', 20, 'Active', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `truck_info`
--

CREATE TABLE `truck_info` (
  `truck_id` int(11) NOT NULL,
  `plate_number` varchar(20) NOT NULL,
  `availability_status` enum('Available','Assigned','Maintenance','Inactive') DEFAULT 'Available',
  `gps_tracker_id` varchar(50) DEFAULT NULL,
  `capacity_kg` int(11) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `truck_info`
--

INSERT INTO `truck_info` (`truck_id`, `plate_number`, `availability_status`, `gps_tracker_id`, `capacity_kg`, `model`) VALUES
(1, 'NEF5466', 'Assigned', NULL, NULL, NULL),
(2, 'NEF5467', 'Assigned', NULL, NULL, NULL),
(3, 'NAN4244', 'Assigned', NULL, NULL, NULL),
(4, 'NAN4243', 'Assigned', NULL, NULL, NULL),
(5, 'NAN4242', 'Assigned', NULL, 1200, 'ISUZU'),
(6, 'NGA2670', 'Assigned', NULL, NULL, NULL),
(7, 'NGA1539', 'Assigned', NULL, NULL, NULL),
(8, 'NGB8646', 'Assigned', NULL, NULL, NULL),
(9, '1301-1588283', 'Assigned', NULL, NULL, NULL),
(10, 'NGA5851', 'Assigned', NULL, NULL, NULL),
(11, 'NGN7061', 'Assigned', NULL, NULL, NULL),
(12, '1301-1669301', 'Assigned', NULL, NULL, NULL),
(13, '1301-1669306', 'Assigned', NULL, NULL, NULL),
(14, 'NGN7060', 'Assigned', NULL, NULL, NULL),
(15, 'NGN7092', 'Assigned', NULL, NULL, NULL),
(16, 'NGN7091', 'Assigned', NULL, NULL, NULL),
(17, '1301-1673463', 'Available', NULL, NULL, NULL),
(18, '1301-1675867', 'Assigned', NULL, NULL, NULL),
(19, 'NGN7510', 'Assigned', NULL, NULL, NULL),
(20, '130105', 'Assigned', NULL, NULL, NULL),
(22, 'nef1234', 'Maintenance', NULL, 1200, 'Hyundai');

-- --------------------------------------------------------

--
-- Table structure for table `user_table`
--

CREATE TABLE `user_table` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `role` text DEFAULT NULL,
  `status` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_table`
--

INSERT INTO `user_table` (`user_id`, `username`, `password`, `email`, `created_at`, `updated_at`, `role`, `status`) VALUES
(1, 'Admin', '$2y$10$b9yH2CW8AT.7OGWbqK3Xz.gRvbANxs21TqcWVr2EXiCmdljTDFJgW', 'sample@gmail.com', '2025-03-22 05:30:01', '2025-06-17 09:03:04', 'admin', 'active'),
(2, 'Sen', '$2y$10$ThVOirms0EDoYJXdczAHj.R9HJuNfWdiUoOyw90XUdkb4haPP029a', 'admin1@gmail.com', '2025-03-22 05:45:41', '2025-04-21 05:38:38', 'admin', 'inactive'),
(3, 'user1', '$2y$10$xes2nRdb4ohmq5fUKmFV8Oqq6RuG8iEivJa5nJTbnQADI5XdK2/Dy', 'user1@gmail.com', '2025-03-22 05:56:04', '2025-06-17 05:35:48', 'collector', 'active'),
(8, 'Admin4', '$2y$10$BLOMLq89/2cDvkekPO6VHeEAF4ogxMS3K9mjAo1h4ELIwdsUMuaWO', 'admin4@gmail.com', '2025-03-30 11:44:37', '2025-06-17 05:34:53', 'admin', 'active'),
(9, 'Joelle Joy', '$2y$10$uf9T4cIc3h43bxRDoci/oOjQFyw7CEP0Yn4ver7VKbIDxmxREu4QW', 'joelle@gmail.com', '2025-04-02 09:28:36', '2025-06-17 08:58:25', 'collector', 'active'),
(10, 'Kuromi', '$2y$10$zG0ii.uiexsxHkPCwgo47./GhfDP3YiBTrAQop/yqMAU1zNZ8c6KW', 'kuromi@gmail.com', '2025-04-03 02:05:29', '2025-06-17 05:33:39', 'admin', 'active'),
(11, 'lala', '$2y$10$XWPtcPQu5nNOvAKSZJYEqu5aqaDEACaGj878ytwdIlzBprAa1JqM6', 'lala@gmail.com', '2025-04-15 09:47:40', '2025-04-15 11:06:41', 'admin', 'active'),
(13, 'newuser', '$2y$10$7Me8g5kHg7o2UozJ5FHm7unD8r1w.6F6dVEuK3V1zTD1PMsyVXBx.', 'newuser@gmail.com', '2025-04-21 08:44:35', '2025-05-19 09:47:31', 'admin', 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alerts`
--
ALTER TABLE `alerts`
  ADD PRIMARY KEY (`alert_id`),
  ADD KEY `truck_id` (`truck_id`),
  ADD KEY `trip_id` (`trip_id`);

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `gps_tracking`
--
ALTER TABLE `gps_tracking`
  ADD PRIMARY KEY (`tracking_id`),
  ADD KEY `truck_id` (`truck_id`);

--
-- Indexes for table `mucipalities_record`
--
ALTER TABLE `mucipalities_record`
  ADD PRIMARY KEY (`municipal_record_id`);

--
-- Indexes for table `routes`
--
ALTER TABLE `routes`
  ADD PRIMARY KEY (`route_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `truck_id` (`truck_id`);

--
-- Indexes for table `trip_logs`
--
ALTER TABLE `trip_logs`
  ADD PRIMARY KEY (`trip_id`),
  ADD KEY `truck_id` (`truck_id`),
  ADD KEY `driver_id` (`driver_id`),
  ADD KEY `route_id` (`route_id`),
  ADD KEY `fk_trip_schedule` (`schedule_id`);

--
-- Indexes for table `truck_driver`
--
ALTER TABLE `truck_driver`
  ADD PRIMARY KEY (`driver_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `truck_driver_ibfk_1` (`truck_id`);

--
-- Indexes for table `truck_info`
--
ALTER TABLE `truck_info`
  ADD PRIMARY KEY (`truck_id`),
  ADD UNIQUE KEY `plate_number` (`plate_number`),
  ADD UNIQUE KEY `gps_tracker_id` (`gps_tracker_id`);

--
-- Indexes for table `user_table`
--
ALTER TABLE `user_table`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alerts`
--
ALTER TABLE `alerts`
  MODIFY `alert_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `gps_tracking`
--
ALTER TABLE `gps_tracking`
  MODIFY `tracking_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mucipalities_record`
--
ALTER TABLE `mucipalities_record`
  MODIFY `municipal_record_id` int(100) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `routes`
--
ALTER TABLE `routes`
  MODIFY `route_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `trip_logs`
--
ALTER TABLE `trip_logs`
  MODIFY `trip_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `truck_driver`
--
ALTER TABLE `truck_driver`
  MODIFY `driver_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `truck_info`
--
ALTER TABLE `truck_info`
  MODIFY `truck_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `user_table`
--
ALTER TABLE `user_table`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `alerts`
--
ALTER TABLE `alerts`
  ADD CONSTRAINT `alerts_ibfk_1` FOREIGN KEY (`truck_id`) REFERENCES `truck_info` (`truck_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alerts_ibfk_2` FOREIGN KEY (`trip_id`) REFERENCES `trip_logs` (`trip_id`) ON DELETE CASCADE;

--
-- Constraints for table `employee`
--
ALTER TABLE `employee`
  ADD CONSTRAINT `fk_employee_user` FOREIGN KEY (`user_id`) REFERENCES `user_table` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `gps_tracking`
--
ALTER TABLE `gps_tracking`
  ADD CONSTRAINT `gps_tracking_ibfk_1` FOREIGN KEY (`truck_id`) REFERENCES `truck_info` (`truck_id`) ON DELETE CASCADE;

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`truck_id`) REFERENCES `truck_info` (`truck_id`) ON DELETE CASCADE;

--
-- Constraints for table `trip_logs`
--
ALTER TABLE `trip_logs`
  ADD CONSTRAINT `fk_trip_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`schedule_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `trip_logs_ibfk_1` FOREIGN KEY (`truck_id`) REFERENCES `truck_info` (`truck_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trip_logs_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `truck_driver` (`driver_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trip_logs_ibfk_3` FOREIGN KEY (`route_id`) REFERENCES `routes` (`route_id`) ON DELETE SET NULL;

--
-- Constraints for table `truck_driver`
--
ALTER TABLE `truck_driver`
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `user_table` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `truck_driver_ibfk_1` FOREIGN KEY (`truck_id`) REFERENCES `truck_info` (`truck_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
