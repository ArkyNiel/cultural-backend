-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 29, 2025 at 09:53 PM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u378403689_ptci_cultural`
--

-- --------------------------------------------------------

--
-- Table structure for table `interpretative_final_score`
--

CREATE TABLE `interpretative_final_score` (
  `team_id` int(11) NOT NULL,
  `final_score` decimal(5,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `interpretative_score`
--

CREATE TABLE `interpretative_score` (
  `score_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `judge_id` int(11) NOT NULL,
  `originality` decimal(5,2) NOT NULL CHECK (`originality` >= 0 and `originality` <= 100),
  `mastery_of_steps` decimal(5,2) NOT NULL CHECK (`mastery_of_steps` >= 0 and `mastery_of_steps` <= 100),
  `choreography_and_style` decimal(5,2) NOT NULL CHECK (`choreography_and_style` >= 0 and `choreography_and_style` <= 100),
  `costume_and_props` decimal(5,2) NOT NULL CHECK (`costume_and_props` >= 0 and `costume_and_props` <= 100),
  `stage_presence` decimal(5,2) NOT NULL CHECK (`stage_presence` >= 0 and `stage_presence` <= 100),
  `total_score` decimal(6,2) GENERATED ALWAYS AS (`originality` + `mastery_of_steps` + `choreography_and_style` + `costume_and_props` + `stage_presence`) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `modern_final_score`
--

CREATE TABLE `modern_final_score` (
  `team_id` int(11) NOT NULL,
  `final_score` decimal(5,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `modern_final_score`
--

INSERT INTO `modern_final_score` (`team_id`, `final_score`, `created_at`, `updated_at`) VALUES
(232495, 64.50, '2025-10-29 21:26:45', '2025-10-29 21:28:42');

-- --------------------------------------------------------

--
-- Table structure for table `modern_score`
--

CREATE TABLE `modern_score` (
  `score_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `judge_id` int(11) NOT NULL,
  `mastery_of_steps` decimal(5,2) NOT NULL CHECK (`mastery_of_steps` >= 0 and `mastery_of_steps` <= 100),
  `choreography_and_style` decimal(5,2) NOT NULL CHECK (`choreography_and_style` >= 0 and `choreography_and_style` <= 100),
  `costume_and_props` decimal(5,2) NOT NULL CHECK (`costume_and_props` >= 0 and `costume_and_props` <= 100),
  `stage_presence` decimal(5,2) NOT NULL CHECK (`stage_presence` >= 0 and `stage_presence` <= 100),
  `audience_impact` decimal(5,2) NOT NULL CHECK (`audience_impact` >= 0 and `audience_impact` <= 100),
  `total_score` decimal(6,2) GENERATED ALWAYS AS (`mastery_of_steps` + `choreography_and_style` + `costume_and_props` + `stage_presence` + `audience_impact`) STORED,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `modern_score`
--

INSERT INTO `modern_score` (`score_id`, `team_id`, `judge_id`, `mastery_of_steps`, `choreography_and_style`, `costume_and_props`, `stage_presence`, `audience_impact`, `created_at`) VALUES
(678973, 232495, 566882, 22.00, 13.00, 8.00, 8.00, 9.00, '2025-10-29 21:26:45'),
(995613, 232495, 347294, 29.00, 13.00, 10.00, 8.00, 9.00, '2025-10-29 21:28:42');

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `team_id` int(11) NOT NULL,
  `team` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`team_id`, `team`, `created_at`) VALUES
(210099, '', '2025-10-29 20:59:26'),
(232495, 'Yellow', '2025-10-29 21:23:01');

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
(405791, 'Sample', '$2y$10$5Uaddheubx4Fu3qYNdFzM.wMIUnckPp4r6nZp4j8Jm25FYX/cJTJS', 'judge', 0, 0, '2025-10-28 17:36:53'),
(474540, 'Sample', '$2y$10$X0r327SsI6NTuMWAsCMVGOUGpSEqBW6Jx1n4bXaFtMLX0aS79DP0a', 'admin', 0, 0, '2025-10-29 08:58:16'),
(921893, 'jazer', '$2y$10$W.Tq41sBngjngJA9vLFEXermI6wO9ku.5UsmNPEqeU9zn2NK0uaLW', 'admin', 0, 0, '2025-10-29 09:03:42'),
(786313, 'Niel', '$2y$10$miQUmLeRQ0kh9QMYvgpWC.3.PbHQzbma.XhMnMgEhN2/tLv0zHZkq', 'judge', 0, 0, '2025-10-29 09:32:53'),
(566882, 'judge1', '$2y$10$pxdVbJB6SbHS.AxsKInYRO6iFPNR2EJuO2xmLL0esaXQVm3HFzFVG', 'judge', 0, 0, '2025-10-29 09:34:14'),
(347294, 'judge2', '$2y$10$P.mZfhFXpoOeWj6oE7La5.Wz1eYsRWpWMTwh2ehq0oH2BWKW50gMO', 'judge', 0, 0, '2025-10-29 21:27:57');

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
-- Table structure for table `vocal_final_score`
--

CREATE TABLE `vocal_final_score` (
  `cand_id` int(11) NOT NULL,
  `final_score` decimal(5,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vocal_final_score`
--

INSERT INTO `vocal_final_score` (`cand_id`, `final_score`, `created_at`, `updated_at`) VALUES
(1, 59.00, '2025-10-29 09:30:15', '2025-10-29 11:38:52');

-- --------------------------------------------------------

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
-- Dumping data for table `vocal_score`
--

INSERT INTO `vocal_score` (`score_id`, `cand_id`, `judge_id`, `voice_tone_quality`, `mastery_and_timing`, `vocal_expression`, `diction`, `stage_presence`, `entertainment_value`, `created_at`) VALUES
(958389, 1, 921893, 30.00, 19.00, 15.00, 10.00, 8.00, 9.00, '2025-10-29 09:30:15'),
(947408, 1, 566882, 20.00, 19.00, 15.00, 8.00, 8.00, 10.00, '2025-10-29 09:35:26'),
(908776, 1, 566882, 1.00, 1.00, 1.00, 1.00, 1.00, 1.00, '2025-10-29 11:38:52');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
