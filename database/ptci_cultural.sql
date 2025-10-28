-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 28, 2025 at 06:47 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ptci_cultural`
--

-- --------------------------------------------------------

--
-- Table structure for table `interpretative_dance`
--

CREATE TABLE `interpretative_dance` (
  `score_id` int(11) NOT NULL,
  `cand_id` int(11) NOT NULL,
  `judge_id` int(11) NOT NULL,
  `originality` decimal(5,2) NOT NULL CHECK (`originality` >= 0 and `originality` <= 100),
  `mastery_of_steps` decimal(5,2) NOT NULL CHECK (`mastery_of_steps` >= 0 and `mastery_of_steps` <= 100),
  `choreography_and_style` decimal(5,2) NOT NULL CHECK (`choreography_and_style` >= 0 and `choreography_and_style` <= 100),
  `costume_and_props` decimal(5,2) NOT NULL CHECK (`costume_and_props` >= 0 and `costume_and_props` <= 100),
  `stage_presence` decimal(5,2) NOT NULL CHECK (`stage_presence` >= 0 and `stage_presence` <= 100),
  `total_score` decimal(6,2) GENERATED ALWAYS AS (`originality` * 0.25 + `mastery_of_steps` * 0.15 + `choreography_and_style` * 0.20 + `costume_and_props` * 0.25 + `stage_presence` * 0.15) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `modern_score`
--

CREATE TABLE `modern_score` (
  `score_id` int(11) NOT NULL,
  `cand_id` int(11) NOT NULL,
  `judge_id` int(11) NOT NULL,
  `mastery_of_steps` decimal(5,2) NOT NULL CHECK (`mastery_of_steps` >= 0 and `mastery_of_steps` <= 100),
  `choreography_and_style` decimal(5,2) NOT NULL CHECK (`choreography_and_style` >= 0 and `choreography_and_style` <= 100),
  `costume_and_props` decimal(5,2) NOT NULL CHECK (`costume_and_props` >= 0 and `costume_and_props` <= 100),
  `stage_presence` decimal(5,2) NOT NULL CHECK (`stage_presence` >= 0 and `stage_presence` <= 100),
  `audience_impact` decimal(5,2) NOT NULL CHECK (`audience_impact` >= 0 and `audience_impact` <= 100),
  `total_score` decimal(6,2) GENERATED ALWAYS AS (`mastery_of_steps` + `choreography_and_style` + `costume_and_props` + `stage_presence` + `audience_impact`) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('judge','admin') DEFAULT 'judge',
  `has_submitted` tinyint(1) DEFAULT 0,
  `has_agreed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `has_submitted`, `has_agreed`, `created_at`) VALUES
(243602, 'Niel', '$2y$10$oC9BG7xOCn9wEytPnOoBA.s7UeVtBLFjC3TAizTIAYxJJzRf4viFO', '', 0, 0, '2025-10-28 14:59:46'),
(0, 'Ivy', 'Ivy1234', 'judge', 0, 0, '2025-10-28 17:34:40'),
(405791, 'Sample', '$2y$10$5Uaddheubx4Fu3qYNdFzM.wMIUnckPp4r6nZp4j8Jm25FYX/cJTJS', 'judge', 0, 0, '2025-10-28 17:36:53');

-- --------------------------------------------------------

--
-- Table structure for table `vocal_contestants`
--

CREATE TABLE `vocal_contestants` (
  `cand_id` int(11) NOT NULL,
  `cand_name` varchar(255) NOT NULL,
  `cand_team` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vocal_contestants`
--

INSERT INTO `vocal_contestants` (`cand_id`, `cand_name`, `cand_team`, `created_at`) VALUES
(1, 'Niel', 'Yellow', '2025-10-28 17:14:46');

-- --------------------------------------------------------

--
-- Table structure for table `contestants`
--

CREATE TABLE `contestants` (
  `cand_id` int(11) NOT NULL,
  `cand_number` varchar(10) NOT NULL,
  `cand_name` varchar(255) NOT NULL,
  `cand_team` varchar(100) NOT NULL,
  `cand_gender` enum('male','female') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `modern_final_score`
--

CREATE TABLE `modern_final_score` (
  `cand_id` int(11) NOT NULL,
  `final_score` decimal(5,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `vocal_final_score`
--

CREATE TABLE `vocal_final_score` (
  `cand_id` int(11) NOT NULL,
  `final_score` decimal(5,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `vocal_score`
--

CREATE TABLE `vocal_score` (
  `score_id` int(11) NOT NULL,
  `cand_id` int(11) NOT NULL,
  `judge_id` int(11) NOT NULL,
  `voice_tone_quality` decimal(5,2) NOT NULL CHECK (`voice_tone_quality` >= 0 and `voice_tone_quality` <= 100),
  `mastery_and_timing` decimal(5,2) NOT NULL CHECK (`mastery_and_timing` >= 0 and `mastery_and_timing` <= 100),
  `vocal_expression` decimal(5,2) NOT NULL CHECK (`vocal_expression` >= 0 and `vocal_expression` <= 100),
  `diction` decimal(5,2) NOT NULL CHECK (`diction` >= 0 and `diction` <= 100),
  `stage_presence` decimal(5,2) NOT NULL CHECK (`stage_presence` >= 0 and `stage_presence` <= 100),
  `entertainment_value` decimal(5,2) NOT NULL CHECK (`entertainment_value` >= 0 and `entertainment_value` <= 100),
  `total_score` decimal(6,2) GENERATED ALWAYS AS (`voice_tone_quality` + `mastery_and_timing` + `vocal_expression` + `diction` + `stage_presence` + `entertainment_value`) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `contestants`
--
ALTER TABLE `contestants`
  ADD PRIMARY KEY (`cand_id`);

--
-- Indexes for table `final_score`
--
ALTER TABLE `final_score`
  ADD PRIMARY KEY (`cand_id`);

--
-- Indexes for table `vocal_contestants`
--
ALTER TABLE `vocal_contestants`
  ADD PRIMARY KEY (`cand_id`);

--
-- Indexes for table `vocal_score`
--
ALTER TABLE `vocal_score`
  ADD PRIMARY KEY (`score_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `contestants`
--
ALTER TABLE `contestants`
  MODIFY `cand_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vocal_contestants`
--
ALTER TABLE `vocal_contestants`
  MODIFY `cand_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `vocal_score`
--
ALTER TABLE `vocal_score`
  MODIFY `score_id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
