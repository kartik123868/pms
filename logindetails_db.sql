-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 17, 2025 at 08:03 PM
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
-- Database: `logindetails_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`id`, `project_id`, `employee_id`) VALUES
(4, 6, 1),
(6, 9, 1);

-- --------------------------------------------------------

--
-- Table structure for table `lgntable`
--

CREATE TABLE `lgntable` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL,
  `usertype` varchar(50) NOT NULL DEFAULT 'employee'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lgntable`
--

INSERT INTO `lgntable` (`id`, `username`, `password`, `usertype`) VALUES
(1, 'kartik', 'abcd', 'employee'),
(2, 'admin', 'qwer', 'admin'),
(10, 'james ', 'ppp', 'projectleader'),
(11, 'dir', 'tttt', 'director'),
(12, 'subdir', '1234', 'subdirector'),
(13, 'sdf', 'sdf', 'employee');

-- --------------------------------------------------------

--
-- Table structure for table `project`
--

CREATE TABLE `project` (
  `project_number` int(11) NOT NULL,
  `project_leader` varchar(255) NOT NULL,
  `project_proposal` date NOT NULL,
  `project_sanction` date NOT NULL,
  `project_completed` date NOT NULL,
  `project_objective` varchar(255) NOT NULL,
  `project_progress` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project`
--

INSERT INTO `project` (`project_number`, `project_leader`, `project_proposal`, `project_sanction`, `project_completed`, `project_objective`, `project_progress`) VALUES
(9, 'james', '2025-07-18', '2025-07-22', '2025-07-29', 'trjuru', 0);

-- --------------------------------------------------------

--
-- Table structure for table `project_goals`
--

CREATE TABLE `project_goals` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `goal_text` varchar(255) NOT NULL,
  `is_done` tinyint(1) NOT NULL,
  `is_completed` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_goals`
--

INSERT INTO `project_goals` (`id`, `project_id`, `goal_text`, `is_done`, `is_completed`) VALUES
(7, 9, '5utyuirturt', 0, 1),
(8, 9, 'jktyiei685685', 0, 1),
(9, 9, 'fhdjhdfurturt', 0, 0),
(10, 9, 'jdfurtutujhdf', 0, 0),
(11, 9, 'tkityio', 0, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lgntable`
--
ALTER TABLE `lgntable`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `project`
--
ALTER TABLE `project`
  ADD PRIMARY KEY (`project_number`);

--
-- Indexes for table `project_goals`
--
ALTER TABLE `project_goals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `lgntable`
--
ALTER TABLE `lgntable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `project`
--
ALTER TABLE `project`
  MODIFY `project_number` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `project_goals`
--
ALTER TABLE `project_goals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `project_goals`
--
ALTER TABLE `project_goals`
  ADD CONSTRAINT `project_goals_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `project` (`project_number`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
