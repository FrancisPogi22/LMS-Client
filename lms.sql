-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 22, 2024 at 05:54 PM
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
-- Database: `lms`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `created_at`) VALUES
(1, 'teacher1', 'teacher1@example.com', '$2y$10$.l9rolRxzjHaGK84Fh6IhuzweLhDcg0D1f3nBuQzx4LTzPEWJnsnm', '2024-10-16 14:48:24'),
(2, 'admin', 'admin@gmail.com', '$2y$10$TmnlqDQVoJb3H7BA0uza7O3iwA9WEuKU8IixgWZRDLuIy.ue2rzA2', '2024-11-06 12:18:08'),
(3, 'ADMIN2', 'admin@gmail.com', '$2y$10$Y.WU5vByhSCwvwswGwjFZ.CDM3cScaU.PHUamRK72BasQNCaeX0pG', '2024-11-10 03:35:54'),
(4, 'kenshin', 'kenshin@gmail.com', '$2y$10$IKnm.xZH8yVe.mhjHxPjGuOox3cDNHptL5/zEUq3aIbHrOYvhsaGK', '2024-11-12 16:17:57'),
(5, 'kenken', 'kenken@gmail.com', '$2y$10$AwPY8QMNVkt86QPbEsAiN.dVeod73Z9hf6uGZI8x6Oh5tgZqPnRJK', '2024-11-12 16:18:24');

-- --------------------------------------------------------

--
-- Table structure for table `assessments`
--

CREATE TABLE `assessments` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `assessment_title` varchar(255) NOT NULL,
  `assessment_description` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assessments`
--

INSERT INTO `assessments` (`id`, `course_id`, `instructor_id`, `assessment_title`, `assessment_description`, `created_at`) VALUES
(36, 42, 25, 'can you please answer this questions', 'adasdsdadads', '2024-11-22 15:52:52');

-- --------------------------------------------------------

--
-- Table structure for table `assessment_feedback`
--

CREATE TABLE `assessment_feedback` (
  `id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('instructor','student') NOT NULL,
  `comment` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessment_submissions`
--

CREATE TABLE `assessment_submissions` (
  `id` int(11) NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `submission_text` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `comment_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`comment_id`, `post_id`, `student_id`, `content`, `created_at`) VALUES
(55, 48, 8, 'Lorem ipsum dolor sit amet consectetur, adipisicing elit. Consectetur, unde amet. Reiciendis facilis ex voluptatum nulla quod perspiciatis suscipit, doloribus obcaecati. Illum, expedita nobis tempora dolore sint aut explicabo id.\r\nLorem ipsum dolor sit amet consectetur, adipisicing elit. Consectetur, unde amet. Reiciendis facilis ex voluptatum nulla quod perspiciatis suscipit, doloribus obcaecati. Illum, expedita nobis tempora dolore sint aut explicabo id.\r\nLorem ipsum dolor sit amet consectetur, adipisicing elit. Consectetur, unde amet. Reiciendis facilis ex voluptatum nulla quod perspiciatis suscipit, doloribus obcaecati. Illum, expedita nobis tempora dolore sint aut explicabo id.\r\nLorem ipsum dolor sit amet consectetur, adipisicing elit. Consectetur, unde amet. Reiciendis facilis ex voluptatum nulla quod perspiciatis suscipit, doloribus obcaecati. Illum, expedita nobis tempora dolore sint aut explicabo id.\r\nLorem ipsum dolor sit amet consectetur, adipisicing elit. Consectetur, unde amet. Reiciendis facilis ex voluptatum nulla quod perspiciatis suscipit, doloribus obcaecati. Illum, expedita nobis tempora dolore sint aut explicabo id.\r\nLorem ipsum dolor sit amet consectetur, adipisicing elit. Consectetur, unde amet. Reiciendis facilis ex voluptatum nulla quod perspiciatis suscipit, doloribus obcaecati. Illum, expedita nobis tempora dolore sint aut explicabo id.\r\nLorem ipsum dolor sit amet consectetur, adipisicing elit. Consectetur, unde amet. Reiciendis facilis ex voluptatum nulla quod perspiciatis suscipit, doloribus obcaecati. Illum, expedita nobis tempora dolore sint aut explicabo id.\r\nLorem ipsum dolor sit amet consectetur, adipisicing elit. Consectetur, unde amet. Reiciendis facilis ex voluptatum nulla quod perspiciatis suscipit, doloribus obcaecati. Illum, expedita nobis tempora dolore sint aut explicabo id.\r\nLorem ipsum dolor sit amet consectetur, adipisicing elit. Consectetur, unde amet. Reiciendis facilis ex voluptatum nulla quod perspiciatis suscipit, doloribus obcaecati. Illum, expedita nobis tempora dolore sint aut explicabo id.\r\nLorem ipsum dolor sit amet consectetur, adipisicing elit. Consectetur, unde amet. Reiciendis facilis ex voluptatum nulla quod perspiciatis suscipit, doloribus obcaecati. Illum, expedita nobis tempora dolore sint aut explicabo id.\r\nLorem ipsum dolor sit amet consectetur, adipisicing elit. Consectetur, unde amet. Reiciendis facilis ex voluptatum nulla quod perspiciatis suscipit, doloribus obcaecati. Illum, expedita nobis tempora dolore sint aut explicabo id.\r\nLorem ipsum dolor sit amet consectetur, adipisicing elit. Consectetur, unde amet. Reiciendis facilis ex voluptatum nulla quod perspiciatis suscipit, doloribus obcaecati. Illum, expedita nobis tempora dolore sint aut explicabo id.\r\nLorem ipsum dolor sit amet consectetur, adipisicing elit. Consectetur, unde amet. Reiciendis facilis ex voluptatum nulla quod perspiciatis suscipit, doloribus obcaecati. Illum, expedita nobis tempora dolore sint aut explicabo id.\r\nLorem ipsum dolor sit amet consectetur, adipisicing elit. Consectetur, unde amet. Reiciendis facilis ex voluptatum nulla quod perspiciatis suscipit, doloribus obcaecati. Illum, expedita nobis tempora dolore sint aut explicabo id.\r\nLorem ipsum dolor sit amet consectetur, adipisicing elit. Consectetur, unde amet. Reiciendis facilis ex voluptatum nulla quod perspiciatis suscipit, doloribus obcaecati. Illum, expedita nobis tempora dolore sint aut explicabo id.\r\nLorem ipsum dolor sit amet consectetur, adipisicing elit. Consectetur, unde amet. Reiciendis facilis ex voluptatum nulla quod perspiciatis suscipit, doloribus obcaecati. Illum, expedita nobis tempora dolore sint aut explicabo id.\r\nLorem ipsum dolor sit amet consectetur, adipisicing elit. Consectetur, unde amet. Reiciendis facilis ex voluptatum nulla quod perspiciatis suscipit, doloribus obcaecati. Illum, expedita nobis tempora dolore sint aut explicabo id.\r\nLorem ipsum dolor sit amet consectetur, adipisicing elit. Consectetur, unde amet. Reiciendis facilis ex voluptatum nulla quod perspiciatis suscipit, doloribus obcaecati. Illum, expedita nobis tempora dolore sint aut explicabo id.\r\n', '2024-11-20 23:19:02'),
(56, 51, 8, 'sadnadhasjdhajdgasjhdabdhabdhsabdjhabvdahsdbasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadadsasdasdadadasdadadsadadadadadasdsadasdadadasdadads', '2024-11-20 23:29:10'),
(57, 21, 8, 'dsadasda', '2024-11-20 23:42:55');

-- --------------------------------------------------------

--
-- Table structure for table `comment_replies`
--

CREATE TABLE `comment_replies` (
  `reply_id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `completed_modules`
--

CREATE TABLE `completed_modules` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `course_description` text DEFAULT NULL,
  `course_image` varchar(255) DEFAULT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `instructor_name` varchar(255) DEFAULT NULL,
  `overview` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `course_name`, `course_description`, `course_image`, `instructor_id`, `instructor_name`, `overview`) VALUES
(41, 'Writing courses', 'Learn about data analysis on this  course ', 'uploads/wallpaperflare.com_wallpaper (3).jpg', 27, NULL, NULL),
(42, 'fundamental analytics', 'Fundamental analysis is a method used to evaluate the intrinsic value of a security, such as stocks or bonds. It involves analyzing various factors such as a company\'s financial statements, management team, industry trends, and market conditions to determine the true worth of an investment.', 'uploads/evohelmet.jpg', 25, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `student_id`, `course_id`) VALUES
(109, 8, 37),
(110, 8, 38),
(111, 8, 40),
(112, 8, 41);

-- --------------------------------------------------------

--
-- Table structure for table `e_certificates`
--

CREATE TABLE `e_certificates` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `certificate_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `content` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `course_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `instructors`
--

CREATE TABLE `instructors` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_picture` text NOT NULL,
  `gender` enum('male','female','other') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instructors`
--

INSERT INTO `instructors` (`id`, `name`, `email`, `password`, `created_at`, `profile_picture`, `gender`) VALUES
(25, 'Niana Guerero', 'niana@gmail.com', '$2y$10$XOx3jFCeAoUNMcrE3NCVOed4l.zpO6dkXfZh2wSQZmPYgbUMtegm.', '2024-11-18 17:11:42', 'uploads/profile_picture/467025826_596145056089198_369990306562908076_n.jpg', 'female'),
(27, 'DAZEL JANE DIMACULANGAN', 'dazel@gmail.com', '$2y$10$eXb7Id31mz.gTyypHo.BL.6eVdlQqsK6tj.WDJH93CMFQAxdDCTBu', '2024-11-19 18:57:19', 'uploads/profile_picture/images.jfif', 'female');

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `module_file` varchar(255) DEFAULT NULL,
  `video_file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `module_progress`
--

CREATE TABLE `module_progress` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `content` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `course_id`, `student_id`, `content`, `image`, `created_at`) VALUES
(21, 37, 8, 'hello world', 'uploads/evohelmet.jpg', '2024-11-20 23:42:48');

-- --------------------------------------------------------

--
-- Table structure for table `replies`
--

CREATE TABLE `replies` (
  `reply_id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reply_content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `course_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `approved` tinyint(1) DEFAULT 0,
  `reset_token` varchar(255) NOT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `username`, `email`, `password`, `created_at`, `name`, `code`, `approved`, `reset_token`, `profile_pic`, `gender`) VALUES
(7, 'kenshiro', 'kenshino@gmail.com', '$2y$10$XWbnxpbd1.tK2zjMOMOt4.3sEOSyU4hjwa0iCjE2qxSvH0OlcjPXy', '2024-11-20 22:45:06', 'kenshiro', 'CHV265', 1, '', NULL, NULL),
(8, 'jenskie mercado', 'jenskie@gmail.com', '$2y$10$N0zM7MISmbbMUcEwVSjce.CFo4ockxMNw4lTO4DuGaUQrXP92Epxe', '2024-11-20 22:58:27', 'jenskie', 'KBK004', 1, '', 'uploads/profile_8.jpg', 'Female');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assessments`
--
ALTER TABLE `assessments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `assessment_feedback`
--
ALTER TABLE `assessment_feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `assessment_submissions`
--
ALTER TABLE `assessment_submissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`comment_id`);

--
-- Indexes for table `comment_replies`
--
ALTER TABLE `comment_replies`
  ADD PRIMARY KEY (`reply_id`),
  ADD KEY `comment_id` (`comment_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `completed_modules`
--
ALTER TABLE `completed_modules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `e_certificates`
--
ALTER TABLE `e_certificates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `instructors`
--
ALTER TABLE `instructors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `module_progress`
--
ALTER TABLE `module_progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `replies`
--
ALTER TABLE `replies`
  ADD PRIMARY KEY (`reply_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assessments`
--
ALTER TABLE `assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `assessment_feedback`
--
ALTER TABLE `assessment_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `assessment_submissions`
--
ALTER TABLE `assessment_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `comment_replies`
--
ALTER TABLE `comment_replies`
  MODIFY `reply_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `completed_modules`
--
ALTER TABLE `completed_modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=115;

--
-- AUTO_INCREMENT for table `e_certificates`
--
ALTER TABLE `e_certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `instructors`
--
ALTER TABLE `instructors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `module_progress`
--
ALTER TABLE `module_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `replies`
--
ALTER TABLE `replies`
  MODIFY `reply_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `e_certificates`
--
ALTER TABLE `e_certificates`
  ADD CONSTRAINT `e_certificates_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `e_certificates_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `module_progress`
--
ALTER TABLE `module_progress`
  ADD CONSTRAINT `module_progress_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `module_progress_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `module_progress_ibfk_3` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
