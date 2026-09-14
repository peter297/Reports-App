-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 26, 2024 at 05:53 AM
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
-- Database: `smartreports`
--

-- --------------------------------------------------------

--
-- Table structure for table `attachments`
--

CREATE TABLE `attachments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `report_id` bigint(20) UNSIGNED NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendances`
--

CREATE TABLE `attendances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `year_session_id` bigint(20) UNSIGNED NOT NULL,
  `term_id` bigint(20) UNSIGNED NOT NULL,
  `week_id` bigint(20) UNSIGNED NOT NULL,
  `class_id` bigint(20) UNSIGNED NOT NULL,
  `stream_id` bigint(20) UNSIGNED NOT NULL,
  `total_boys` int(11) NOT NULL,
  `total_girls` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendances`
--

INSERT INTO `attendances` (`id`, `year_session_id`, `term_id`, `week_id`, `class_id`, `stream_id`, `total_boys`, `total_girls`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, 1, 20, 30, '2024-08-25 18:27:06', '2024-08-25 18:27:06');

-- --------------------------------------------------------

--
-- Table structure for table `billings`
--

CREATE TABLE `billings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `year_session_id` bigint(20) UNSIGNED NOT NULL,
  `term_id` bigint(20) UNSIGNED NOT NULL,
  `name_of_bill` varchar(255) NOT NULL,
  `amount` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('356a192b7913b04c54574d18c28d46e6395428ab', 'i:1;', 1724445172),
('356a192b7913b04c54574d18c28d46e6395428ab:timer', 'i:1724445172;', 1724445172),
('a17961fa74e9275d529f489537f179c05d50c2f3', 'i:1;', 1724614074),
('a17961fa74e9275d529f489537f179c05d50c2f3:timer', 'i:1724614074;', 1724614074),
('spatie.permission.cache', 'a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:210:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:15:\"view_attendance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:19:\"view_any_attendance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:17:\"create_attendance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:17:\"update_attendance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:18:\"restore_attendance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:22:\"restore_any_attendance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:20:\"replicate_attendance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:18:\"reorder_attendance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:17:\"delete_attendance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:21:\"delete_any_attendance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:23:\"force_delete_attendance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:27:\"force_delete_any_attendance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:12:\"view_billing\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:16:\"view_any_billing\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:14:\"create_billing\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:15;a:4:{s:1:\"a\";i:16;s:1:\"b\";s:14:\"update_billing\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:16;a:4:{s:1:\"a\";i:17;s:1:\"b\";s:15:\"restore_billing\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:17;a:4:{s:1:\"a\";i:18;s:1:\"b\";s:19:\"restore_any_billing\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:18;a:4:{s:1:\"a\";i:19;s:1:\"b\";s:17:\"replicate_billing\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:19;a:4:{s:1:\"a\";i:20;s:1:\"b\";s:15:\"reorder_billing\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:20;a:4:{s:1:\"a\";i:21;s:1:\"b\";s:14:\"delete_billing\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:21;a:4:{s:1:\"a\";i:22;s:1:\"b\";s:18:\"delete_any_billing\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:22;a:4:{s:1:\"a\";i:23;s:1:\"b\";s:20:\"force_delete_billing\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:23;a:4:{s:1:\"a\";i:24;s:1:\"b\";s:24:\"force_delete_any_billing\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:24;a:4:{s:1:\"a\";i:25;s:1:\"b\";s:12:\"view_classes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:25;a:4:{s:1:\"a\";i:26;s:1:\"b\";s:16:\"view_any_classes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:26;a:4:{s:1:\"a\";i:27;s:1:\"b\";s:14:\"create_classes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:27;a:4:{s:1:\"a\";i:28;s:1:\"b\";s:14:\"update_classes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:28;a:4:{s:1:\"a\";i:29;s:1:\"b\";s:15:\"restore_classes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:29;a:4:{s:1:\"a\";i:30;s:1:\"b\";s:19:\"restore_any_classes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:30;a:4:{s:1:\"a\";i:31;s:1:\"b\";s:17:\"replicate_classes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:31;a:4:{s:1:\"a\";i:32;s:1:\"b\";s:15:\"reorder_classes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:32;a:4:{s:1:\"a\";i:33;s:1:\"b\";s:14:\"delete_classes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:33;a:4:{s:1:\"a\";i:34;s:1:\"b\";s:18:\"delete_any_classes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:34;a:4:{s:1:\"a\";i:35;s:1:\"b\";s:20:\"force_delete_classes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:35;a:4:{s:1:\"a\";i:36;s:1:\"b\";s:24:\"force_delete_any_classes\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:36;a:4:{s:1:\"a\";i:37;s:1:\"b\";s:19:\"view_co::curricular\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:37;a:4:{s:1:\"a\";i:38;s:1:\"b\";s:23:\"view_any_co::curricular\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:38;a:4:{s:1:\"a\";i:39;s:1:\"b\";s:21:\"create_co::curricular\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:39;a:4:{s:1:\"a\";i:40;s:1:\"b\";s:21:\"update_co::curricular\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:40;a:4:{s:1:\"a\";i:41;s:1:\"b\";s:22:\"restore_co::curricular\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:41;a:4:{s:1:\"a\";i:42;s:1:\"b\";s:26:\"restore_any_co::curricular\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:42;a:4:{s:1:\"a\";i:43;s:1:\"b\";s:24:\"replicate_co::curricular\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:43;a:4:{s:1:\"a\";i:44;s:1:\"b\";s:22:\"reorder_co::curricular\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:44;a:4:{s:1:\"a\";i:45;s:1:\"b\";s:21:\"delete_co::curricular\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:45;a:4:{s:1:\"a\";i:46;s:1:\"b\";s:25:\"delete_any_co::curricular\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:46;a:4:{s:1:\"a\";i:47;s:1:\"b\";s:27:\"force_delete_co::curricular\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:47;a:4:{s:1:\"a\";i:48;s:1:\"b\";s:31:\"force_delete_any_co::curricular\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:48;a:4:{s:1:\"a\";i:49;s:1:\"b\";s:15:\"view_department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:49;a:4:{s:1:\"a\";i:50;s:1:\"b\";s:19:\"view_any_department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:50;a:4:{s:1:\"a\";i:51;s:1:\"b\";s:17:\"create_department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:51;a:4:{s:1:\"a\";i:52;s:1:\"b\";s:17:\"update_department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:52;a:4:{s:1:\"a\";i:53;s:1:\"b\";s:18:\"restore_department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:53;a:4:{s:1:\"a\";i:54;s:1:\"b\";s:22:\"restore_any_department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:54;a:4:{s:1:\"a\";i:55;s:1:\"b\";s:20:\"replicate_department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:55;a:4:{s:1:\"a\";i:56;s:1:\"b\";s:18:\"reorder_department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:56;a:4:{s:1:\"a\";i:57;s:1:\"b\";s:17:\"delete_department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:57;a:4:{s:1:\"a\";i:58;s:1:\"b\";s:21:\"delete_any_department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:58;a:4:{s:1:\"a\";i:59;s:1:\"b\";s:23:\"force_delete_department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:59;a:4:{s:1:\"a\";i:60;s:1:\"b\";s:27:\"force_delete_any_department\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:60;a:4:{s:1:\"a\";i:61;s:1:\"b\";s:15:\"view_discipline\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:61;a:4:{s:1:\"a\";i:62;s:1:\"b\";s:19:\"view_any_discipline\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:62;a:4:{s:1:\"a\";i:63;s:1:\"b\";s:17:\"create_discipline\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:63;a:4:{s:1:\"a\";i:64;s:1:\"b\";s:17:\"update_discipline\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:64;a:4:{s:1:\"a\";i:65;s:1:\"b\";s:18:\"restore_discipline\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:65;a:4:{s:1:\"a\";i:66;s:1:\"b\";s:22:\"restore_any_discipline\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:66;a:4:{s:1:\"a\";i:67;s:1:\"b\";s:20:\"replicate_discipline\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:67;a:4:{s:1:\"a\";i:68;s:1:\"b\";s:18:\"reorder_discipline\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:68;a:4:{s:1:\"a\";i:69;s:1:\"b\";s:17:\"delete_discipline\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:69;a:4:{s:1:\"a\";i:70;s:1:\"b\";s:21:\"delete_any_discipline\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:70;a:4:{s:1:\"a\";i:71;s:1:\"b\";s:23:\"force_delete_discipline\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:71;a:4:{s:1:\"a\";i:72;s:1:\"b\";s:27:\"force_delete_any_discipline\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:72;a:4:{s:1:\"a\";i:73;s:1:\"b\";s:13:\"view_employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:73;a:4:{s:1:\"a\";i:74;s:1:\"b\";s:17:\"view_any_employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:74;a:4:{s:1:\"a\";i:75;s:1:\"b\";s:15:\"create_employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:75;a:4:{s:1:\"a\";i:76;s:1:\"b\";s:15:\"update_employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:76;a:4:{s:1:\"a\";i:77;s:1:\"b\";s:16:\"restore_employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:77;a:4:{s:1:\"a\";i:78;s:1:\"b\";s:20:\"restore_any_employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:78;a:4:{s:1:\"a\";i:79;s:1:\"b\";s:18:\"replicate_employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:79;a:4:{s:1:\"a\";i:80;s:1:\"b\";s:16:\"reorder_employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:80;a:4:{s:1:\"a\";i:81;s:1:\"b\";s:15:\"delete_employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:81;a:4:{s:1:\"a\";i:82;s:1:\"b\";s:19:\"delete_any_employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:82;a:4:{s:1:\"a\";i:83;s:1:\"b\";s:21:\"force_delete_employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:83;a:4:{s:1:\"a\";i:84;s:1:\"b\";s:25:\"force_delete_any_employee\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:84;a:4:{s:1:\"a\";i:85;s:1:\"b\";s:15:\"view_enrollment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:85;a:4:{s:1:\"a\";i:86;s:1:\"b\";s:19:\"view_any_enrollment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:86;a:4:{s:1:\"a\";i:87;s:1:\"b\";s:17:\"create_enrollment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:87;a:4:{s:1:\"a\";i:88;s:1:\"b\";s:17:\"update_enrollment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:88;a:4:{s:1:\"a\";i:89;s:1:\"b\";s:18:\"restore_enrollment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:89;a:4:{s:1:\"a\";i:90;s:1:\"b\";s:22:\"restore_any_enrollment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:90;a:4:{s:1:\"a\";i:91;s:1:\"b\";s:20:\"replicate_enrollment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:91;a:4:{s:1:\"a\";i:92;s:1:\"b\";s:18:\"reorder_enrollment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:92;a:4:{s:1:\"a\";i:93;s:1:\"b\";s:17:\"delete_enrollment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:93;a:4:{s:1:\"a\";i:94;s:1:\"b\";s:21:\"delete_any_enrollment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:94;a:4:{s:1:\"a\";i:95;s:1:\"b\";s:23:\"force_delete_enrollment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:95;a:4:{s:1:\"a\";i:96;s:1:\"b\";s:27:\"force_delete_any_enrollment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:96;a:4:{s:1:\"a\";i:97;s:1:\"b\";s:22:\"view_extra::curricular\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:97;a:4:{s:1:\"a\";i:98;s:1:\"b\";s:26:\"view_any_extra::curricular\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:98;a:4:{s:1:\"a\";i:99;s:1:\"b\";s:24:\"create_extra::curricular\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:99;a:4:{s:1:\"a\";i:100;s:1:\"b\";s:24:\"update_extra::curricular\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:100;a:4:{s:1:\"a\";i:101;s:1:\"b\";s:25:\"restore_extra::curricular\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:101;a:4:{s:1:\"a\";i:102;s:1:\"b\";s:29:\"restore_any_extra::curricular\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:102;a:4:{s:1:\"a\";i:103;s:1:\"b\";s:27:\"replicate_extra::curricular\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:103;a:4:{s:1:\"a\";i:104;s:1:\"b\";s:25:\"reorder_extra::curricular\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:104;a:4:{s:1:\"a\";i:105;s:1:\"b\";s:24:\"delete_extra::curricular\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:105;a:4:{s:1:\"a\";i:106;s:1:\"b\";s:28:\"delete_any_extra::curricular\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:106;a:4:{s:1:\"a\";i:107;s:1:\"b\";s:30:\"force_delete_extra::curricular\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:107;a:4:{s:1:\"a\";i:108;s:1:\"b\";s:34:\"force_delete_any_extra::curricular\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:108;a:4:{s:1:\"a\";i:109;s:1:\"b\";s:18:\"view_item::request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:109;a:4:{s:1:\"a\";i:110;s:1:\"b\";s:22:\"view_any_item::request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:110;a:4:{s:1:\"a\";i:111;s:1:\"b\";s:20:\"create_item::request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:111;a:4:{s:1:\"a\";i:112;s:1:\"b\";s:20:\"update_item::request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:112;a:4:{s:1:\"a\";i:113;s:1:\"b\";s:21:\"restore_item::request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:113;a:4:{s:1:\"a\";i:114;s:1:\"b\";s:25:\"restore_any_item::request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:114;a:4:{s:1:\"a\";i:115;s:1:\"b\";s:23:\"replicate_item::request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:115;a:4:{s:1:\"a\";i:116;s:1:\"b\";s:21:\"reorder_item::request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:116;a:4:{s:1:\"a\";i:117;s:1:\"b\";s:20:\"delete_item::request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:117;a:4:{s:1:\"a\";i:118;s:1:\"b\";s:24:\"delete_any_item::request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:118;a:4:{s:1:\"a\";i:119;s:1:\"b\";s:26:\"force_delete_item::request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:119;a:4:{s:1:\"a\";i:120;s:1:\"b\";s:30:\"force_delete_any_item::request\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:120;a:4:{s:1:\"a\";i:121;s:1:\"b\";s:16:\"view_maintenance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:121;a:4:{s:1:\"a\";i:122;s:1:\"b\";s:20:\"view_any_maintenance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:122;a:4:{s:1:\"a\";i:123;s:1:\"b\";s:18:\"create_maintenance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:123;a:4:{s:1:\"a\";i:124;s:1:\"b\";s:18:\"update_maintenance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:124;a:4:{s:1:\"a\";i:125;s:1:\"b\";s:19:\"restore_maintenance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:125;a:4:{s:1:\"a\";i:126;s:1:\"b\";s:23:\"restore_any_maintenance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:126;a:4:{s:1:\"a\";i:127;s:1:\"b\";s:21:\"replicate_maintenance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:127;a:4:{s:1:\"a\";i:128;s:1:\"b\";s:19:\"reorder_maintenance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:128;a:4:{s:1:\"a\";i:129;s:1:\"b\";s:18:\"delete_maintenance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:129;a:4:{s:1:\"a\";i:130;s:1:\"b\";s:22:\"delete_any_maintenance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:130;a:4:{s:1:\"a\";i:131;s:1:\"b\";s:24:\"force_delete_maintenance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:131;a:4:{s:1:\"a\";i:132;s:1:\"b\";s:28:\"force_delete_any_maintenance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:132;a:4:{s:1:\"a\";i:133;s:1:\"b\";s:11:\"view_report\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:133;a:4:{s:1:\"a\";i:134;s:1:\"b\";s:15:\"view_any_report\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:134;a:4:{s:1:\"a\";i:135;s:1:\"b\";s:13:\"create_report\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:135;a:4:{s:1:\"a\";i:136;s:1:\"b\";s:13:\"update_report\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:136;a:4:{s:1:\"a\";i:137;s:1:\"b\";s:14:\"restore_report\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:137;a:4:{s:1:\"a\";i:138;s:1:\"b\";s:18:\"restore_any_report\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:138;a:4:{s:1:\"a\";i:139;s:1:\"b\";s:16:\"replicate_report\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:139;a:4:{s:1:\"a\";i:140;s:1:\"b\";s:14:\"reorder_report\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:140;a:4:{s:1:\"a\";i:141;s:1:\"b\";s:13:\"delete_report\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:141;a:4:{s:1:\"a\";i:142;s:1:\"b\";s:17:\"delete_any_report\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:142;a:4:{s:1:\"a\";i:143;s:1:\"b\";s:19:\"force_delete_report\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:143;a:4:{s:1:\"a\";i:144;s:1:\"b\";s:23:\"force_delete_any_report\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:144;a:4:{s:1:\"a\";i:145;s:1:\"b\";s:9:\"view_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:145;a:4:{s:1:\"a\";i:146;s:1:\"b\";s:13:\"view_any_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:146;a:4:{s:1:\"a\";i:147;s:1:\"b\";s:11:\"create_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:147;a:4:{s:1:\"a\";i:148;s:1:\"b\";s:11:\"update_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:148;a:4:{s:1:\"a\";i:149;s:1:\"b\";s:11:\"delete_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:149;a:4:{s:1:\"a\";i:150;s:1:\"b\";s:15:\"delete_any_role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:150;a:4:{s:1:\"a\";i:151;s:1:\"b\";s:11:\"view_stream\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:151;a:4:{s:1:\"a\";i:152;s:1:\"b\";s:15:\"view_any_stream\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:152;a:4:{s:1:\"a\";i:153;s:1:\"b\";s:13:\"create_stream\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:153;a:4:{s:1:\"a\";i:154;s:1:\"b\";s:13:\"update_stream\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:154;a:4:{s:1:\"a\";i:155;s:1:\"b\";s:14:\"restore_stream\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:155;a:4:{s:1:\"a\";i:156;s:1:\"b\";s:18:\"restore_any_stream\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:156;a:4:{s:1:\"a\";i:157;s:1:\"b\";s:16:\"replicate_stream\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:157;a:4:{s:1:\"a\";i:158;s:1:\"b\";s:14:\"reorder_stream\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:158;a:4:{s:1:\"a\";i:159;s:1:\"b\";s:13:\"delete_stream\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:159;a:4:{s:1:\"a\";i:160;s:1:\"b\";s:17:\"delete_any_stream\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:160;a:4:{s:1:\"a\";i:161;s:1:\"b\";s:19:\"force_delete_stream\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:161;a:4:{s:1:\"a\";i:162;s:1:\"b\";s:23:\"force_delete_any_stream\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:162;a:4:{s:1:\"a\";i:163;s:1:\"b\";s:9:\"view_term\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:163;a:4:{s:1:\"a\";i:164;s:1:\"b\";s:13:\"view_any_term\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:164;a:4:{s:1:\"a\";i:165;s:1:\"b\";s:11:\"create_term\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:165;a:4:{s:1:\"a\";i:166;s:1:\"b\";s:11:\"update_term\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:166;a:4:{s:1:\"a\";i:167;s:1:\"b\";s:12:\"restore_term\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:167;a:4:{s:1:\"a\";i:168;s:1:\"b\";s:16:\"restore_any_term\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:168;a:4:{s:1:\"a\";i:169;s:1:\"b\";s:14:\"replicate_term\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:169;a:4:{s:1:\"a\";i:170;s:1:\"b\";s:12:\"reorder_term\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:170;a:4:{s:1:\"a\";i:171;s:1:\"b\";s:11:\"delete_term\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:171;a:4:{s:1:\"a\";i:172;s:1:\"b\";s:15:\"delete_any_term\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:172;a:4:{s:1:\"a\";i:173;s:1:\"b\";s:17:\"force_delete_term\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:173;a:4:{s:1:\"a\";i:174;s:1:\"b\";s:21:\"force_delete_any_term\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:174;a:4:{s:1:\"a\";i:175;s:1:\"b\";s:9:\"view_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:175;a:4:{s:1:\"a\";i:176;s:1:\"b\";s:13:\"view_any_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:176;a:4:{s:1:\"a\";i:177;s:1:\"b\";s:11:\"create_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:177;a:4:{s:1:\"a\";i:178;s:1:\"b\";s:11:\"update_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:178;a:4:{s:1:\"a\";i:179;s:1:\"b\";s:12:\"restore_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:179;a:4:{s:1:\"a\";i:180;s:1:\"b\";s:16:\"restore_any_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:180;a:4:{s:1:\"a\";i:181;s:1:\"b\";s:14:\"replicate_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:181;a:4:{s:1:\"a\";i:182;s:1:\"b\";s:12:\"reorder_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:182;a:4:{s:1:\"a\";i:183;s:1:\"b\";s:11:\"delete_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:183;a:4:{s:1:\"a\";i:184;s:1:\"b\";s:15:\"delete_any_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:184;a:4:{s:1:\"a\";i:185;s:1:\"b\";s:17:\"force_delete_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:185;a:4:{s:1:\"a\";i:186;s:1:\"b\";s:21:\"force_delete_any_user\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:186;a:4:{s:1:\"a\";i:187;s:1:\"b\";s:9:\"view_week\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:187;a:4:{s:1:\"a\";i:188;s:1:\"b\";s:13:\"view_any_week\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:188;a:4:{s:1:\"a\";i:189;s:1:\"b\";s:11:\"create_week\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:189;a:4:{s:1:\"a\";i:190;s:1:\"b\";s:11:\"update_week\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:190;a:4:{s:1:\"a\";i:191;s:1:\"b\";s:12:\"restore_week\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:191;a:4:{s:1:\"a\";i:192;s:1:\"b\";s:16:\"restore_any_week\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:192;a:4:{s:1:\"a\";i:193;s:1:\"b\";s:14:\"replicate_week\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:193;a:4:{s:1:\"a\";i:194;s:1:\"b\";s:12:\"reorder_week\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:194;a:4:{s:1:\"a\";i:195;s:1:\"b\";s:11:\"delete_week\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:195;a:4:{s:1:\"a\";i:196;s:1:\"b\";s:15:\"delete_any_week\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:196;a:4:{s:1:\"a\";i:197;s:1:\"b\";s:17:\"force_delete_week\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:197;a:4:{s:1:\"a\";i:198;s:1:\"b\";s:21:\"force_delete_any_week\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:198;a:4:{s:1:\"a\";i:199;s:1:\"b\";s:18:\"view_year::session\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:199;a:4:{s:1:\"a\";i:200;s:1:\"b\";s:22:\"view_any_year::session\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:200;a:4:{s:1:\"a\";i:201;s:1:\"b\";s:20:\"create_year::session\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:201;a:4:{s:1:\"a\";i:202;s:1:\"b\";s:20:\"update_year::session\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:202;a:4:{s:1:\"a\";i:203;s:1:\"b\";s:21:\"restore_year::session\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:203;a:4:{s:1:\"a\";i:204;s:1:\"b\";s:25:\"restore_any_year::session\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:204;a:4:{s:1:\"a\";i:205;s:1:\"b\";s:23:\"replicate_year::session\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:205;a:4:{s:1:\"a\";i:206;s:1:\"b\";s:21:\"reorder_year::session\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:206;a:4:{s:1:\"a\";i:207;s:1:\"b\";s:20:\"delete_year::session\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:207;a:4:{s:1:\"a\";i:208;s:1:\"b\";s:24:\"delete_any_year::session\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:208;a:4:{s:1:\"a\";i:209;s:1:\"b\";s:26:\"force_delete_year::session\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:209;a:4:{s:1:\"a\";i:210;s:1:\"b\";s:30:\"force_delete_any_year::session\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}}s:5:\"roles\";a:3:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:11:\"super_admin\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:4;s:1:\"b\";s:12:\"teacher_user\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:8:\"app_user\";s:1:\"c\";s:3:\"web\";}}}', 1724698235);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Grade 1 ', '2024-08-25 16:43:32', '2024-08-25 16:43:51');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `report_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `comment_text` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `co_curriculars`
--

CREATE TABLE `co_curriculars` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `year_session_id` bigint(20) UNSIGNED NOT NULL,
  `term_id` bigint(20) UNSIGNED NOT NULL,
  `activity` varchar(255) NOT NULL,
  `day_of_the_week` date NOT NULL,
  `amount` decimal(8,2) NOT NULL,
  `comment` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Teaching', '2024-08-25 16:27:32', '2024-08-25 16:27:32'),
(2, 'Admins', '2024-08-25 16:28:46', '2024-08-25 16:28:46'),
(3, 'Masjid', '2024-08-25 16:29:10', '2024-08-25 16:29:10');

-- --------------------------------------------------------

--
-- Table structure for table `disciplines`
--

CREATE TABLE `disciplines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `year_session_id` bigint(20) UNSIGNED NOT NULL,
  `term_id` bigint(20) UNSIGNED NOT NULL,
  `week_id` bigint(20) UNSIGNED NOT NULL,
  `class_id` bigint(20) UNSIGNED NOT NULL,
  `stream_id` bigint(20) UNSIGNED NOT NULL,
  `case_name` varchar(255) NOT NULL,
  `learner_name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `person_responsible` varchar(255) NOT NULL,
  `action_taken` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL,
  `zip_code` char(255) NOT NULL,
  `date_of_birth` date NOT NULL,
  `date_hired` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `department_id`, `first_name`, `middle_name`, `last_name`, `address`, `zip_code`, `date_of_birth`, `date_hired`, `created_at`, `updated_at`) VALUES
(1, 2, 'Clayton', 'Hamisi', 'Mmbehi', 'Nairobi, Kenya', '00100', '1994-12-12', '2023-01-16', '2024-08-25 16:38:23', '2024-08-25 16:38:23');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `year_session_id` bigint(20) UNSIGNED NOT NULL,
  `term_id` bigint(20) UNSIGNED NOT NULL,
  `current_enrollment` int(11) NOT NULL,
  `departures` int(11) NOT NULL,
  `new_admissions` int(11) NOT NULL,
  `comment` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `extra_curriculars`
--

CREATE TABLE `extra_curriculars` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `year_session_id` bigint(20) UNSIGNED NOT NULL,
  `term_id` bigint(20) UNSIGNED NOT NULL,
  `week_id` bigint(20) UNSIGNED NOT NULL,
  `venue` varchar(255) NOT NULL,
  `date_of_event` date NOT NULL,
  `attendees` varchar(255) NOT NULL,
  `remarks` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `item_requests`
--

CREATE TABLE `item_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `year_session_id` bigint(20) UNSIGNED NOT NULL,
  `term_id` bigint(20) UNSIGNED NOT NULL,
  `week_id` bigint(20) UNSIGNED NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `estimate_cost` int(11) NOT NULL,
  `remarks` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maintenances`
--

CREATE TABLE `maintenances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `department_id` bigint(20) UNSIGNED NOT NULL,
  `year_session_id` bigint(20) UNSIGNED NOT NULL,
  `term_id` bigint(20) UNSIGNED NOT NULL,
  `week_id` bigint(20) UNSIGNED NOT NULL,
  `maintenance_name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `estimate_cost` int(11) NOT NULL,
  `remarks` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(5, '2024_08_23_191647_create_reports_table', 3),
(6, '2024_08_23_191659_create_comments_table', 3),
(7, '2024_08_23_191718_create_attachments_table', 3),
(11, '2024_08_23_191753_create_report_user_pivote_table', 4),
(12, '2024_08_24_085419_create_year_sessions_table', 5),
(13, '2024_08_24_085420_create_terms_table', 5),
(14, '2024_08_24_085446_create_weeks_table', 5),
(15, '2024_08_24_085511_create_departments_table', 5),
(16, '2024_08_24_085610_create_employees_table', 5),
(17, '2024_08_24_112356_create_extra_curriculars_table', 6),
(18, '2024_08_24_112420_create_co_curriculars_table', 6),
(19, '2024_08_24_112448_create_enrollments_table', 6),
(20, '2024_08_24_112550_create_item_requests_table', 6),
(21, '2024_08_24_112612_create_maintenances_table', 6),
(22, '2024_08_24_112635_create_classes_table', 7),
(23, '2024_08_24_112637_create_streams_table', 7),
(24, '2024_08_24_112638_create_disciplines_table', 7),
(25, '2024_08_24_112715_create_attendances_table', 7),
(26, '2024_08_24_112732_create_billings_table', 7),
(30, '2024_08_23_185113_create_permission_tables', 8);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 1),
(2, 'App\\Models\\User', 5),
(3, 'App\\Models\\User', 2),
(3, 'App\\Models\\User', 5),
(4, 'App\\Models\\User', 4),
(4, 'App\\Models\\User', 5);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'view_attendance', 'web', '2024-08-25 14:26:07', '2024-08-25 14:26:07'),
(2, 'view_any_attendance', 'web', '2024-08-25 14:26:07', '2024-08-25 14:26:07'),
(3, 'create_attendance', 'web', '2024-08-25 14:26:07', '2024-08-25 14:26:07'),
(4, 'update_attendance', 'web', '2024-08-25 14:26:07', '2024-08-25 14:26:07'),
(5, 'restore_attendance', 'web', '2024-08-25 14:26:07', '2024-08-25 14:26:07'),
(6, 'restore_any_attendance', 'web', '2024-08-25 14:26:08', '2024-08-25 14:26:08'),
(7, 'replicate_attendance', 'web', '2024-08-25 14:26:08', '2024-08-25 14:26:08'),
(8, 'reorder_attendance', 'web', '2024-08-25 14:26:08', '2024-08-25 14:26:08'),
(9, 'delete_attendance', 'web', '2024-08-25 14:26:08', '2024-08-25 14:26:08'),
(10, 'delete_any_attendance', 'web', '2024-08-25 14:26:08', '2024-08-25 14:26:08'),
(11, 'force_delete_attendance', 'web', '2024-08-25 14:26:08', '2024-08-25 14:26:08'),
(12, 'force_delete_any_attendance', 'web', '2024-08-25 14:26:09', '2024-08-25 14:26:09'),
(13, 'view_billing', 'web', '2024-08-25 14:26:09', '2024-08-25 14:26:09'),
(14, 'view_any_billing', 'web', '2024-08-25 14:26:09', '2024-08-25 14:26:09'),
(15, 'create_billing', 'web', '2024-08-25 14:26:09', '2024-08-25 14:26:09'),
(16, 'update_billing', 'web', '2024-08-25 14:26:09', '2024-08-25 14:26:09'),
(17, 'restore_billing', 'web', '2024-08-25 14:26:09', '2024-08-25 14:26:09'),
(18, 'restore_any_billing', 'web', '2024-08-25 14:26:09', '2024-08-25 14:26:09'),
(19, 'replicate_billing', 'web', '2024-08-25 14:26:09', '2024-08-25 14:26:09'),
(20, 'reorder_billing', 'web', '2024-08-25 14:26:09', '2024-08-25 14:26:09'),
(21, 'delete_billing', 'web', '2024-08-25 14:26:09', '2024-08-25 14:26:09'),
(22, 'delete_any_billing', 'web', '2024-08-25 14:26:09', '2024-08-25 14:26:09'),
(23, 'force_delete_billing', 'web', '2024-08-25 14:26:09', '2024-08-25 14:26:09'),
(24, 'force_delete_any_billing', 'web', '2024-08-25 14:26:09', '2024-08-25 14:26:09'),
(25, 'view_classes', 'web', '2024-08-25 14:26:09', '2024-08-25 14:26:09'),
(26, 'view_any_classes', 'web', '2024-08-25 14:26:10', '2024-08-25 14:26:10'),
(27, 'create_classes', 'web', '2024-08-25 14:26:10', '2024-08-25 14:26:10'),
(28, 'update_classes', 'web', '2024-08-25 14:26:10', '2024-08-25 14:26:10'),
(29, 'restore_classes', 'web', '2024-08-25 14:26:10', '2024-08-25 14:26:10'),
(30, 'restore_any_classes', 'web', '2024-08-25 14:26:10', '2024-08-25 14:26:10'),
(31, 'replicate_classes', 'web', '2024-08-25 14:26:10', '2024-08-25 14:26:10'),
(32, 'reorder_classes', 'web', '2024-08-25 14:26:10', '2024-08-25 14:26:10'),
(33, 'delete_classes', 'web', '2024-08-25 14:26:10', '2024-08-25 14:26:10'),
(34, 'delete_any_classes', 'web', '2024-08-25 14:26:10', '2024-08-25 14:26:10'),
(35, 'force_delete_classes', 'web', '2024-08-25 14:26:10', '2024-08-25 14:26:10'),
(36, 'force_delete_any_classes', 'web', '2024-08-25 14:26:10', '2024-08-25 14:26:10'),
(37, 'view_co::curricular', 'web', '2024-08-25 14:26:10', '2024-08-25 14:26:10'),
(38, 'view_any_co::curricular', 'web', '2024-08-25 14:26:10', '2024-08-25 14:26:10'),
(39, 'create_co::curricular', 'web', '2024-08-25 14:26:10', '2024-08-25 14:26:10'),
(40, 'update_co::curricular', 'web', '2024-08-25 14:26:10', '2024-08-25 14:26:10'),
(41, 'restore_co::curricular', 'web', '2024-08-25 14:26:10', '2024-08-25 14:26:10'),
(42, 'restore_any_co::curricular', 'web', '2024-08-25 14:26:10', '2024-08-25 14:26:10'),
(43, 'replicate_co::curricular', 'web', '2024-08-25 14:26:10', '2024-08-25 14:26:10'),
(44, 'reorder_co::curricular', 'web', '2024-08-25 14:26:10', '2024-08-25 14:26:10'),
(45, 'delete_co::curricular', 'web', '2024-08-25 14:26:10', '2024-08-25 14:26:10'),
(46, 'delete_any_co::curricular', 'web', '2024-08-25 14:26:10', '2024-08-25 14:26:10'),
(47, 'force_delete_co::curricular', 'web', '2024-08-25 14:26:10', '2024-08-25 14:26:10'),
(48, 'force_delete_any_co::curricular', 'web', '2024-08-25 14:26:11', '2024-08-25 14:26:11'),
(49, 'view_department', 'web', '2024-08-25 14:26:11', '2024-08-25 14:26:11'),
(50, 'view_any_department', 'web', '2024-08-25 14:26:11', '2024-08-25 14:26:11'),
(51, 'create_department', 'web', '2024-08-25 14:26:11', '2024-08-25 14:26:11'),
(52, 'update_department', 'web', '2024-08-25 14:26:11', '2024-08-25 14:26:11'),
(53, 'restore_department', 'web', '2024-08-25 14:26:11', '2024-08-25 14:26:11'),
(54, 'restore_any_department', 'web', '2024-08-25 14:26:11', '2024-08-25 14:26:11'),
(55, 'replicate_department', 'web', '2024-08-25 14:26:11', '2024-08-25 14:26:11'),
(56, 'reorder_department', 'web', '2024-08-25 14:26:11', '2024-08-25 14:26:11'),
(57, 'delete_department', 'web', '2024-08-25 14:26:11', '2024-08-25 14:26:11'),
(58, 'delete_any_department', 'web', '2024-08-25 14:26:11', '2024-08-25 14:26:11'),
(59, 'force_delete_department', 'web', '2024-08-25 14:26:11', '2024-08-25 14:26:11'),
(60, 'force_delete_any_department', 'web', '2024-08-25 14:26:11', '2024-08-25 14:26:11'),
(61, 'view_discipline', 'web', '2024-08-25 14:26:11', '2024-08-25 14:26:11'),
(62, 'view_any_discipline', 'web', '2024-08-25 14:26:11', '2024-08-25 14:26:11'),
(63, 'create_discipline', 'web', '2024-08-25 14:26:11', '2024-08-25 14:26:11'),
(64, 'update_discipline', 'web', '2024-08-25 14:26:11', '2024-08-25 14:26:11'),
(65, 'restore_discipline', 'web', '2024-08-25 14:26:11', '2024-08-25 14:26:11'),
(66, 'restore_any_discipline', 'web', '2024-08-25 14:26:11', '2024-08-25 14:26:11'),
(67, 'replicate_discipline', 'web', '2024-08-25 14:26:11', '2024-08-25 14:26:11'),
(68, 'reorder_discipline', 'web', '2024-08-25 14:26:12', '2024-08-25 14:26:12'),
(69, 'delete_discipline', 'web', '2024-08-25 14:26:12', '2024-08-25 14:26:12'),
(70, 'delete_any_discipline', 'web', '2024-08-25 14:26:12', '2024-08-25 14:26:12'),
(71, 'force_delete_discipline', 'web', '2024-08-25 14:26:12', '2024-08-25 14:26:12'),
(72, 'force_delete_any_discipline', 'web', '2024-08-25 14:26:12', '2024-08-25 14:26:12'),
(73, 'view_employee', 'web', '2024-08-25 14:26:13', '2024-08-25 14:26:13'),
(74, 'view_any_employee', 'web', '2024-08-25 14:26:13', '2024-08-25 14:26:13'),
(75, 'create_employee', 'web', '2024-08-25 14:26:13', '2024-08-25 14:26:13'),
(76, 'update_employee', 'web', '2024-08-25 14:26:13', '2024-08-25 14:26:13'),
(77, 'restore_employee', 'web', '2024-08-25 14:26:13', '2024-08-25 14:26:13'),
(78, 'restore_any_employee', 'web', '2024-08-25 14:26:13', '2024-08-25 14:26:13'),
(79, 'replicate_employee', 'web', '2024-08-25 14:26:13', '2024-08-25 14:26:13'),
(80, 'reorder_employee', 'web', '2024-08-25 14:26:13', '2024-08-25 14:26:13'),
(81, 'delete_employee', 'web', '2024-08-25 14:26:13', '2024-08-25 14:26:13'),
(82, 'delete_any_employee', 'web', '2024-08-25 14:26:13', '2024-08-25 14:26:13'),
(83, 'force_delete_employee', 'web', '2024-08-25 14:26:13', '2024-08-25 14:26:13'),
(84, 'force_delete_any_employee', 'web', '2024-08-25 14:26:13', '2024-08-25 14:26:13'),
(85, 'view_enrollment', 'web', '2024-08-25 14:26:13', '2024-08-25 14:26:13'),
(86, 'view_any_enrollment', 'web', '2024-08-25 14:26:13', '2024-08-25 14:26:13'),
(87, 'create_enrollment', 'web', '2024-08-25 14:26:14', '2024-08-25 14:26:14'),
(88, 'update_enrollment', 'web', '2024-08-25 14:26:14', '2024-08-25 14:26:14'),
(89, 'restore_enrollment', 'web', '2024-08-25 14:26:14', '2024-08-25 14:26:14'),
(90, 'restore_any_enrollment', 'web', '2024-08-25 14:26:14', '2024-08-25 14:26:14'),
(91, 'replicate_enrollment', 'web', '2024-08-25 14:26:14', '2024-08-25 14:26:14'),
(92, 'reorder_enrollment', 'web', '2024-08-25 14:26:14', '2024-08-25 14:26:14'),
(93, 'delete_enrollment', 'web', '2024-08-25 14:26:14', '2024-08-25 14:26:14'),
(94, 'delete_any_enrollment', 'web', '2024-08-25 14:26:14', '2024-08-25 14:26:14'),
(95, 'force_delete_enrollment', 'web', '2024-08-25 14:26:14', '2024-08-25 14:26:14'),
(96, 'force_delete_any_enrollment', 'web', '2024-08-25 14:26:14', '2024-08-25 14:26:14'),
(97, 'view_extra::curricular', 'web', '2024-08-25 14:26:14', '2024-08-25 14:26:14'),
(98, 'view_any_extra::curricular', 'web', '2024-08-25 14:26:14', '2024-08-25 14:26:14'),
(99, 'create_extra::curricular', 'web', '2024-08-25 14:26:14', '2024-08-25 14:26:14'),
(100, 'update_extra::curricular', 'web', '2024-08-25 14:26:15', '2024-08-25 14:26:15'),
(101, 'restore_extra::curricular', 'web', '2024-08-25 14:26:15', '2024-08-25 14:26:15'),
(102, 'restore_any_extra::curricular', 'web', '2024-08-25 14:26:15', '2024-08-25 14:26:15'),
(103, 'replicate_extra::curricular', 'web', '2024-08-25 14:26:15', '2024-08-25 14:26:15'),
(104, 'reorder_extra::curricular', 'web', '2024-08-25 14:26:15', '2024-08-25 14:26:15'),
(105, 'delete_extra::curricular', 'web', '2024-08-25 14:26:15', '2024-08-25 14:26:15'),
(106, 'delete_any_extra::curricular', 'web', '2024-08-25 14:26:15', '2024-08-25 14:26:15'),
(107, 'force_delete_extra::curricular', 'web', '2024-08-25 14:26:15', '2024-08-25 14:26:15'),
(108, 'force_delete_any_extra::curricular', 'web', '2024-08-25 14:26:15', '2024-08-25 14:26:15'),
(109, 'view_item::request', 'web', '2024-08-25 14:26:15', '2024-08-25 14:26:15'),
(110, 'view_any_item::request', 'web', '2024-08-25 14:26:15', '2024-08-25 14:26:15'),
(111, 'create_item::request', 'web', '2024-08-25 14:26:15', '2024-08-25 14:26:15'),
(112, 'update_item::request', 'web', '2024-08-25 14:26:15', '2024-08-25 14:26:15'),
(113, 'restore_item::request', 'web', '2024-08-25 14:26:15', '2024-08-25 14:26:15'),
(114, 'restore_any_item::request', 'web', '2024-08-25 14:26:15', '2024-08-25 14:26:15'),
(115, 'replicate_item::request', 'web', '2024-08-25 14:26:15', '2024-08-25 14:26:15'),
(116, 'reorder_item::request', 'web', '2024-08-25 14:26:15', '2024-08-25 14:26:15'),
(117, 'delete_item::request', 'web', '2024-08-25 14:26:15', '2024-08-25 14:26:15'),
(118, 'delete_any_item::request', 'web', '2024-08-25 14:26:15', '2024-08-25 14:26:15'),
(119, 'force_delete_item::request', 'web', '2024-08-25 14:26:15', '2024-08-25 14:26:15'),
(120, 'force_delete_any_item::request', 'web', '2024-08-25 14:26:15', '2024-08-25 14:26:15'),
(121, 'view_maintenance', 'web', '2024-08-25 14:26:16', '2024-08-25 14:26:16'),
(122, 'view_any_maintenance', 'web', '2024-08-25 14:26:16', '2024-08-25 14:26:16'),
(123, 'create_maintenance', 'web', '2024-08-25 14:26:16', '2024-08-25 14:26:16'),
(124, 'update_maintenance', 'web', '2024-08-25 14:26:16', '2024-08-25 14:26:16'),
(125, 'restore_maintenance', 'web', '2024-08-25 14:26:16', '2024-08-25 14:26:16'),
(126, 'restore_any_maintenance', 'web', '2024-08-25 14:26:16', '2024-08-25 14:26:16'),
(127, 'replicate_maintenance', 'web', '2024-08-25 14:26:16', '2024-08-25 14:26:16'),
(128, 'reorder_maintenance', 'web', '2024-08-25 14:26:16', '2024-08-25 14:26:16'),
(129, 'delete_maintenance', 'web', '2024-08-25 14:26:16', '2024-08-25 14:26:16'),
(130, 'delete_any_maintenance', 'web', '2024-08-25 14:26:16', '2024-08-25 14:26:16'),
(131, 'force_delete_maintenance', 'web', '2024-08-25 14:26:16', '2024-08-25 14:26:16'),
(132, 'force_delete_any_maintenance', 'web', '2024-08-25 14:26:16', '2024-08-25 14:26:16'),
(133, 'view_report', 'web', '2024-08-25 14:26:16', '2024-08-25 14:26:16'),
(134, 'view_any_report', 'web', '2024-08-25 14:26:16', '2024-08-25 14:26:16'),
(135, 'create_report', 'web', '2024-08-25 14:26:16', '2024-08-25 14:26:16'),
(136, 'update_report', 'web', '2024-08-25 14:26:17', '2024-08-25 14:26:17'),
(137, 'restore_report', 'web', '2024-08-25 14:26:17', '2024-08-25 14:26:17'),
(138, 'restore_any_report', 'web', '2024-08-25 14:26:17', '2024-08-25 14:26:17'),
(139, 'replicate_report', 'web', '2024-08-25 14:26:17', '2024-08-25 14:26:17'),
(140, 'reorder_report', 'web', '2024-08-25 14:26:17', '2024-08-25 14:26:17'),
(141, 'delete_report', 'web', '2024-08-25 14:26:17', '2024-08-25 14:26:17'),
(142, 'delete_any_report', 'web', '2024-08-25 14:26:17', '2024-08-25 14:26:17'),
(143, 'force_delete_report', 'web', '2024-08-25 14:26:17', '2024-08-25 14:26:17'),
(144, 'force_delete_any_report', 'web', '2024-08-25 14:26:17', '2024-08-25 14:26:17'),
(145, 'view_role', 'web', '2024-08-25 14:26:17', '2024-08-25 14:26:17'),
(146, 'view_any_role', 'web', '2024-08-25 14:26:17', '2024-08-25 14:26:17'),
(147, 'create_role', 'web', '2024-08-25 14:26:17', '2024-08-25 14:26:17'),
(148, 'update_role', 'web', '2024-08-25 14:26:17', '2024-08-25 14:26:17'),
(149, 'delete_role', 'web', '2024-08-25 14:26:17', '2024-08-25 14:26:17'),
(150, 'delete_any_role', 'web', '2024-08-25 14:26:17', '2024-08-25 14:26:17'),
(151, 'view_stream', 'web', '2024-08-25 14:26:17', '2024-08-25 14:26:17'),
(152, 'view_any_stream', 'web', '2024-08-25 14:26:17', '2024-08-25 14:26:17'),
(153, 'create_stream', 'web', '2024-08-25 14:26:18', '2024-08-25 14:26:18'),
(154, 'update_stream', 'web', '2024-08-25 14:26:18', '2024-08-25 14:26:18'),
(155, 'restore_stream', 'web', '2024-08-25 14:26:18', '2024-08-25 14:26:18'),
(156, 'restore_any_stream', 'web', '2024-08-25 14:26:18', '2024-08-25 14:26:18'),
(157, 'replicate_stream', 'web', '2024-08-25 14:26:18', '2024-08-25 14:26:18'),
(158, 'reorder_stream', 'web', '2024-08-25 14:26:18', '2024-08-25 14:26:18'),
(159, 'delete_stream', 'web', '2024-08-25 14:26:18', '2024-08-25 14:26:18'),
(160, 'delete_any_stream', 'web', '2024-08-25 14:26:18', '2024-08-25 14:26:18'),
(161, 'force_delete_stream', 'web', '2024-08-25 14:26:18', '2024-08-25 14:26:18'),
(162, 'force_delete_any_stream', 'web', '2024-08-25 14:26:18', '2024-08-25 14:26:18'),
(163, 'view_term', 'web', '2024-08-25 14:26:18', '2024-08-25 14:26:18'),
(164, 'view_any_term', 'web', '2024-08-25 14:26:18', '2024-08-25 14:26:18'),
(165, 'create_term', 'web', '2024-08-25 14:26:18', '2024-08-25 14:26:18'),
(166, 'update_term', 'web', '2024-08-25 14:26:19', '2024-08-25 14:26:19'),
(167, 'restore_term', 'web', '2024-08-25 14:26:19', '2024-08-25 14:26:19'),
(168, 'restore_any_term', 'web', '2024-08-25 14:26:19', '2024-08-25 14:26:19'),
(169, 'replicate_term', 'web', '2024-08-25 14:26:19', '2024-08-25 14:26:19'),
(170, 'reorder_term', 'web', '2024-08-25 14:26:19', '2024-08-25 14:26:19'),
(171, 'delete_term', 'web', '2024-08-25 14:26:19', '2024-08-25 14:26:19'),
(172, 'delete_any_term', 'web', '2024-08-25 14:26:19', '2024-08-25 14:26:19'),
(173, 'force_delete_term', 'web', '2024-08-25 14:26:19', '2024-08-25 14:26:19'),
(174, 'force_delete_any_term', 'web', '2024-08-25 14:26:19', '2024-08-25 14:26:19'),
(175, 'view_user', 'web', '2024-08-25 14:26:20', '2024-08-25 14:26:20'),
(176, 'view_any_user', 'web', '2024-08-25 14:26:20', '2024-08-25 14:26:20'),
(177, 'create_user', 'web', '2024-08-25 14:26:20', '2024-08-25 14:26:20'),
(178, 'update_user', 'web', '2024-08-25 14:26:20', '2024-08-25 14:26:20'),
(179, 'restore_user', 'web', '2024-08-25 14:26:20', '2024-08-25 14:26:20'),
(180, 'restore_any_user', 'web', '2024-08-25 14:26:20', '2024-08-25 14:26:20'),
(181, 'replicate_user', 'web', '2024-08-25 14:26:20', '2024-08-25 14:26:20'),
(182, 'reorder_user', 'web', '2024-08-25 14:26:20', '2024-08-25 14:26:20'),
(183, 'delete_user', 'web', '2024-08-25 14:26:20', '2024-08-25 14:26:20'),
(184, 'delete_any_user', 'web', '2024-08-25 14:26:20', '2024-08-25 14:26:20'),
(185, 'force_delete_user', 'web', '2024-08-25 14:26:20', '2024-08-25 14:26:20'),
(186, 'force_delete_any_user', 'web', '2024-08-25 14:26:20', '2024-08-25 14:26:20'),
(187, 'view_week', 'web', '2024-08-25 14:26:20', '2024-08-25 14:26:20'),
(188, 'view_any_week', 'web', '2024-08-25 14:26:20', '2024-08-25 14:26:20'),
(189, 'create_week', 'web', '2024-08-25 14:26:21', '2024-08-25 14:26:21'),
(190, 'update_week', 'web', '2024-08-25 14:26:21', '2024-08-25 14:26:21'),
(191, 'restore_week', 'web', '2024-08-25 14:26:21', '2024-08-25 14:26:21'),
(192, 'restore_any_week', 'web', '2024-08-25 14:26:21', '2024-08-25 14:26:21'),
(193, 'replicate_week', 'web', '2024-08-25 14:26:21', '2024-08-25 14:26:21'),
(194, 'reorder_week', 'web', '2024-08-25 14:26:21', '2024-08-25 14:26:21'),
(195, 'delete_week', 'web', '2024-08-25 14:26:21', '2024-08-25 14:26:21'),
(196, 'delete_any_week', 'web', '2024-08-25 14:26:21', '2024-08-25 14:26:21'),
(197, 'force_delete_week', 'web', '2024-08-25 14:26:21', '2024-08-25 14:26:21'),
(198, 'force_delete_any_week', 'web', '2024-08-25 14:26:21', '2024-08-25 14:26:21'),
(199, 'view_year::session', 'web', '2024-08-25 14:26:22', '2024-08-25 14:26:22'),
(200, 'view_any_year::session', 'web', '2024-08-25 14:26:22', '2024-08-25 14:26:22'),
(201, 'create_year::session', 'web', '2024-08-25 14:26:22', '2024-08-25 14:26:22'),
(202, 'update_year::session', 'web', '2024-08-25 14:26:22', '2024-08-25 14:26:22'),
(203, 'restore_year::session', 'web', '2024-08-25 14:26:22', '2024-08-25 14:26:22'),
(204, 'restore_any_year::session', 'web', '2024-08-25 14:26:22', '2024-08-25 14:26:22'),
(205, 'replicate_year::session', 'web', '2024-08-25 14:26:22', '2024-08-25 14:26:22'),
(206, 'reorder_year::session', 'web', '2024-08-25 14:26:22', '2024-08-25 14:26:22'),
(207, 'delete_year::session', 'web', '2024-08-25 14:26:22', '2024-08-25 14:26:22'),
(208, 'delete_any_year::session', 'web', '2024-08-25 14:26:22', '2024-08-25 14:26:22'),
(209, 'force_delete_year::session', 'web', '2024-08-25 14:26:22', '2024-08-25 14:26:22'),
(210, 'force_delete_any_year::session', 'web', '2024-08-25 14:26:22', '2024-08-25 14:26:22');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `category` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `summary` text NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `category`, `subject`, `summary`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 'General Reports', 'here is the Subject', 'Test test Summary', 1, '2024-08-23 17:22:28', '2024-08-23 17:22:28'),
(3, 'General Reports', 'HERE IS THE SUBJECT OF MY REPORT', 'Here is the summary of my Report. Take a Look!', 1, '2024-08-23 17:56:08', '2024-08-23 17:56:08'),
(4, 'General Reports', 'How to make table columns reactive in Laravel Filament?', '0\n\nI have created custom page in Filament with filter form and report table.\n\nFilter form has month field, where user can choose for which month (and year) he wants to display the report.\n\nThe report table has all workers (users) as rows, all days in selected month as columns and in the cells it shows whether given worker was present on given day.\n\nI made month select reactive so whenever user changes the month, new data is loaded instantly - for selected month.\n\nIt works fine with getTableQuery() function - in it\'s body, I call $this->form->getState(); and according to selected month I compose correct query.\n\nBut I want also the number of columns to change dynamically. I defined my getTableColumns function like this:', 1, '2024-08-23 18:40:33', '2024-08-23 18:40:33');

-- --------------------------------------------------------

--
-- Table structure for table `report_user`
--

CREATE TABLE `report_user` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `report_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'super_admin', 'web', '2024-08-25 14:26:09', '2024-08-25 14:26:09'),
(2, 'staff_user', 'web', '2024-08-25 14:26:23', '2024-08-25 14:26:23'),
(3, 'app_user', 'web', '2024-08-25 14:26:23', '2024-08-25 14:26:23'),
(4, 'teacher_user', 'web', '2024-08-25 14:26:23', '2024-08-25 14:26:23');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1),
(1, 4),
(2, 1),
(2, 4),
(3, 1),
(3, 4),
(4, 1),
(4, 4),
(5, 1),
(5, 4),
(6, 1),
(6, 4),
(7, 1),
(7, 4),
(8, 1),
(8, 4),
(9, 1),
(9, 4),
(10, 1),
(10, 4),
(11, 1),
(11, 4),
(12, 1),
(12, 4),
(13, 1),
(13, 3),
(14, 1),
(14, 3),
(15, 1),
(15, 3),
(16, 1),
(16, 3),
(17, 1),
(17, 3),
(18, 1),
(18, 3),
(19, 1),
(19, 3),
(20, 1),
(20, 3),
(21, 1),
(21, 3),
(22, 1),
(22, 3),
(23, 1),
(23, 3),
(24, 1),
(24, 3),
(25, 1),
(26, 1),
(27, 1),
(28, 1),
(29, 1),
(30, 1),
(31, 1),
(32, 1),
(33, 1),
(34, 1),
(35, 1),
(36, 1),
(37, 1),
(37, 3),
(38, 1),
(38, 3),
(39, 1),
(39, 3),
(40, 1),
(40, 3),
(41, 1),
(41, 3),
(42, 1),
(42, 3),
(43, 1),
(43, 3),
(44, 1),
(44, 3),
(45, 1),
(45, 3),
(46, 1),
(46, 3),
(47, 1),
(47, 3),
(48, 1),
(48, 3),
(49, 1),
(50, 1),
(51, 1),
(52, 1),
(53, 1),
(54, 1),
(55, 1),
(56, 1),
(57, 1),
(58, 1),
(59, 1),
(60, 1),
(61, 1),
(61, 4),
(62, 1),
(62, 4),
(63, 1),
(63, 4),
(64, 1),
(64, 4),
(65, 1),
(65, 4),
(66, 1),
(66, 4),
(67, 1),
(67, 4),
(68, 1),
(68, 4),
(69, 1),
(69, 4),
(70, 1),
(70, 4),
(71, 1),
(71, 4),
(72, 1),
(72, 4),
(73, 1),
(74, 1),
(75, 1),
(76, 1),
(77, 1),
(78, 1),
(79, 1),
(80, 1),
(81, 1),
(82, 1),
(83, 1),
(84, 1),
(85, 1),
(85, 3),
(86, 1),
(86, 3),
(87, 1),
(87, 3),
(88, 1),
(88, 3),
(89, 1),
(89, 3),
(90, 1),
(90, 3),
(91, 1),
(91, 3),
(92, 1),
(92, 3),
(93, 1),
(93, 3),
(94, 1),
(94, 3),
(95, 1),
(95, 3),
(96, 1),
(96, 3),
(97, 1),
(97, 3),
(98, 1),
(98, 3),
(99, 1),
(99, 3),
(100, 1),
(100, 3),
(101, 1),
(101, 3),
(102, 1),
(102, 3),
(103, 1),
(103, 3),
(104, 1),
(104, 3),
(105, 1),
(105, 3),
(106, 1),
(106, 3),
(107, 1),
(107, 3),
(108, 1),
(108, 3),
(109, 1),
(109, 3),
(110, 1),
(110, 3),
(111, 1),
(111, 3),
(112, 1),
(112, 3),
(113, 1),
(113, 3),
(114, 1),
(114, 3),
(115, 1),
(115, 3),
(116, 1),
(116, 3),
(117, 1),
(117, 3),
(118, 1),
(118, 3),
(119, 1),
(119, 3),
(120, 1),
(120, 3),
(121, 1),
(121, 3),
(122, 1),
(122, 3),
(123, 1),
(123, 3),
(124, 1),
(124, 3),
(125, 1),
(125, 3),
(126, 1),
(126, 3),
(127, 1),
(127, 3),
(128, 1),
(128, 3),
(129, 1),
(129, 3),
(130, 1),
(130, 3),
(131, 1),
(131, 3),
(132, 1),
(132, 3),
(133, 1),
(133, 3),
(134, 1),
(134, 3),
(135, 1),
(135, 3),
(136, 1),
(136, 3),
(137, 1),
(137, 3),
(138, 1),
(138, 3),
(139, 1),
(139, 3),
(140, 1),
(140, 3),
(141, 1),
(141, 3),
(142, 1),
(142, 3),
(143, 1),
(143, 3),
(144, 1),
(144, 3),
(145, 1),
(146, 1),
(147, 1),
(148, 1),
(149, 1),
(150, 1),
(151, 1),
(152, 1),
(153, 1),
(154, 1),
(155, 1),
(156, 1),
(157, 1),
(158, 1),
(159, 1),
(160, 1),
(161, 1),
(162, 1),
(163, 1),
(164, 1),
(165, 1),
(166, 1),
(167, 1),
(168, 1),
(169, 1),
(170, 1),
(171, 1),
(172, 1),
(173, 1),
(174, 1),
(175, 1),
(176, 1),
(177, 1),
(178, 1),
(179, 1),
(180, 1),
(181, 1),
(182, 1),
(183, 1),
(184, 1),
(185, 1),
(186, 1),
(187, 1),
(188, 1),
(189, 1),
(190, 1),
(191, 1),
(192, 1),
(193, 1),
(194, 1),
(195, 1),
(196, 1),
(197, 1),
(198, 1),
(199, 1),
(200, 1),
(201, 1),
(202, 1),
(203, 1),
(204, 1),
(205, 1),
(206, 1),
(207, 1),
(208, 1),
(209, 1),
(210, 1);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('Howsu8nplnJCFCzyZRzgXFGps8KAjRESXdEKt2lg', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36', 'YTo4OntzOjY6Il90b2tlbiI7czo0MDoiaXhDYm9MZ1E2Ym56TXJ4bU5qNlhxdUxBYVNzSmg0SEN2ek1iZjIxRSI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjQ1OiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYWRtaW4vZXh0cmEtY3VycmljdWxhcnMiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO3M6MTc6InBhc3N3b3JkX2hhc2hfd2ViIjtzOjYwOiIkMnkkMTIkbHFqUlcvQmxVVDAyMFphMTdjSXlsT29qcS5venFjSXExcTBwbDZKWmdOaGNMYlozWmw4UHEiO3M6ODoiZmlsYW1lbnQiO2E6MDp7fXM6NjoidGFibGVzIjthOjY6e3M6MzE6Ikxpc3REZXBhcnRtZW50c190b2dnbGVkX2NvbHVtbnMiO2E6Mjp7czoxMDoiY3JlYXRlZF9hdCI7YjoxO3M6MTA6InVwZGF0ZWRfYXQiO2I6MTt9czoyOToiTGlzdEVtcGxveWVlc190b2dnbGVkX2NvbHVtbnMiO2E6Njp7czoxMDoiY3JlYXRlZF9hdCI7YjowO3M6MTA6InVwZGF0ZWRfYXQiO2I6MDtzOjc6ImFkZHJlc3MiO2I6MDtzOjg6InppcF9jb2RlIjtiOjA7czoxMzoiZGF0ZV9vZl9iaXJ0aCI7YjowO3M6MTA6ImRhdGVfaGlyZWQiO2I6MDt9czoyNzoiTGlzdENsYXNzZXNfdG9nZ2xlZF9jb2x1bW5zIjthOjI6e3M6MTA6ImNyZWF0ZWRfYXQiO2I6MTtzOjEwOiJ1cGRhdGVkX2F0IjtiOjE7fXM6MzI6Ikxpc3RZZWFyU2Vzc2lvbnNfdG9nZ2xlZF9jb2x1bW5zIjthOjI6e3M6MTA6ImNyZWF0ZWRfYXQiO2I6MTtzOjEwOiJ1cGRhdGVkX2F0IjtiOjE7fXM6MjU6Ikxpc3RUZXJtc190b2dnbGVkX2NvbHVtbnMiO2E6Mjp7czoxMDoiY3JlYXRlZF9hdCI7YjoxO3M6MTA6InVwZGF0ZWRfYXQiO2I6MTt9czozMToiTGlzdEF0dGVuZGFuY2VzX3RvZ2dsZWRfY29sdW1ucyI7YToyOntzOjEwOiJjcmVhdGVkX2F0IjtiOjE7czoxMDoidXBkYXRlZF9hdCI7YjoxO319fQ==', 1724621515);

-- --------------------------------------------------------

--
-- Table structure for table `streams`
--

CREATE TABLE `streams` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `class_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `streams`
--

INSERT INTO `streams` (`id`, `class_id`, `name`, `created_at`, `updated_at`) VALUES
(1, 1, 'Blue', '2024-08-25 16:48:52', '2024-08-25 16:48:52'),
(2, 1, 'Green', '2024-08-25 16:49:34', '2024-08-25 16:49:34');

-- --------------------------------------------------------

--
-- Table structure for table `terms`
--

CREATE TABLE `terms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `year_session_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `terms`
--

INSERT INTO `terms` (`id`, `year_session_id`, `name`, `created_at`, `updated_at`) VALUES
(1, 1, 'Term 1', '2024-08-25 17:00:32', '2024-08-25 17:00:32'),
(2, 1, 'Term 2', '2024-08-25 17:04:23', '2024-08-25 17:05:01'),
(3, 1, 'Term 3', '2024-08-25 17:05:28', '2024-08-25 17:05:28');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Clayton M. Hamisi', 'mmbehiclayton@gmail.com', NULL, '$2y$12$lqjRW/BlUT020Za17cIylOojq.ozqcIq1q0pl6JZgNhcLbZ3Zl8Pq', NULL, '2024-08-23 15:44:49', '2024-08-23 15:44:49'),
(2, 'Elvis Vina', 'elvis@gmail.com', NULL, '$2y$12$KRXi7bOD4M63tBPJIIGMr.uYQ0N/1j4e0rJ5k.L9txy.3L39Zkly.', NULL, '2024-08-23 17:30:27', '2024-08-25 14:36:27'),
(3, 'Ethan Hamisi', 'ethan@gmail.com', NULL, '$2y$12$rphoZdcXmjfz9SS5vSWUx.SjQKAFo.5UoJrAQeoibbEA04wjbCiOG', NULL, '2024-08-24 07:42:58', '2024-08-24 07:42:58'),
(4, 'Nickson Hamisi', 'nickson@gmail.com', NULL, '$2y$12$i4yBgnUdeE9YAE5Nbf.XV.JZbQmjFNW905Lc9VMEmnpwgY7GIiwyK', 'OwTnZORf0TXBgAv7NM8J4tzSmXANUcqFQmixZLSzRtlXRlKg0wRhkKHqNDhK', '2024-08-24 10:02:15', '2024-08-25 15:48:37'),
(5, 'App User', 'app@gmail.com', NULL, '$2y$12$kAe0Ip0yLNBBS6KPAylQ.ueqXrxr/3n1wimMA3PlAUcxt7VTck9E2', NULL, '2024-08-25 14:39:52', '2024-08-25 14:39:52');

-- --------------------------------------------------------

--
-- Table structure for table `weeks`
--

CREATE TABLE `weeks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `term_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `weeks`
--

INSERT INTO `weeks` (`id`, `term_id`, `name`, `created_at`, `updated_at`) VALUES
(1, 1, 'Week 1', '2024-08-25 17:12:32', '2024-08-25 17:12:32'),
(2, 1, 'Week 2', '2024-08-25 17:13:30', '2024-08-25 17:13:30');

-- --------------------------------------------------------

--
-- Table structure for table `year_sessions`
--

CREATE TABLE `year_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `year_sessions`
--

INSERT INTO `year_sessions` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Academic Year 2024', '2024-08-25 16:55:20', '2024-08-25 16:55:20');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attachments`
--
ALTER TABLE `attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attachments_report_id_foreign` (`report_id`);

--
-- Indexes for table `attendances`
--
ALTER TABLE `attendances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attendances_year_session_id_foreign` (`year_session_id`),
  ADD KEY `attendances_term_id_foreign` (`term_id`),
  ADD KEY `attendances_week_id_foreign` (`week_id`),
  ADD KEY `attendances_class_id_foreign` (`class_id`),
  ADD KEY `attendances_stream_id_foreign` (`stream_id`);

--
-- Indexes for table `billings`
--
ALTER TABLE `billings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `billings_year_session_id_foreign` (`year_session_id`),
  ADD KEY `billings_term_id_foreign` (`term_id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `comments_report_id_foreign` (`report_id`),
  ADD KEY `comments_user_id_foreign` (`user_id`);

--
-- Indexes for table `co_curriculars`
--
ALTER TABLE `co_curriculars`
  ADD PRIMARY KEY (`id`),
  ADD KEY `co_curriculars_year_session_id_foreign` (`year_session_id`),
  ADD KEY `co_curriculars_term_id_foreign` (`term_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `disciplines`
--
ALTER TABLE `disciplines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `disciplines_year_session_id_foreign` (`year_session_id`),
  ADD KEY `disciplines_term_id_foreign` (`term_id`),
  ADD KEY `disciplines_week_id_foreign` (`week_id`),
  ADD KEY `disciplines_class_id_foreign` (`class_id`),
  ADD KEY `disciplines_stream_id_foreign` (`stream_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employees_department_id_foreign` (`department_id`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `enrollments_year_session_id_foreign` (`year_session_id`),
  ADD KEY `enrollments_term_id_foreign` (`term_id`);

--
-- Indexes for table `extra_curriculars`
--
ALTER TABLE `extra_curriculars`
  ADD PRIMARY KEY (`id`),
  ADD KEY `extra_curriculars_year_session_id_foreign` (`year_session_id`),
  ADD KEY `extra_curriculars_term_id_foreign` (`term_id`),
  ADD KEY `extra_curriculars_week_id_foreign` (`week_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `item_requests`
--
ALTER TABLE `item_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_requests_department_id_foreign` (`department_id`),
  ADD KEY `item_requests_year_session_id_foreign` (`year_session_id`),
  ADD KEY `item_requests_term_id_foreign` (`term_id`),
  ADD KEY `item_requests_week_id_foreign` (`week_id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `maintenances`
--
ALTER TABLE `maintenances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `maintenances_department_id_foreign` (`department_id`),
  ADD KEY `maintenances_year_session_id_foreign` (`year_session_id`),
  ADD KEY `maintenances_term_id_foreign` (`term_id`),
  ADD KEY `maintenances_week_id_foreign` (`week_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reports_user_id_foreign` (`user_id`);

--
-- Indexes for table `report_user`
--
ALTER TABLE `report_user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_user_report_id_foreign` (`report_id`),
  ADD KEY `report_user_user_id_foreign` (`user_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `streams`
--
ALTER TABLE `streams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `streams_class_id_foreign` (`class_id`);

--
-- Indexes for table `terms`
--
ALTER TABLE `terms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `terms_year_session_id_foreign` (`year_session_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `weeks`
--
ALTER TABLE `weeks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `weeks_term_id_foreign` (`term_id`);

--
-- Indexes for table `year_sessions`
--
ALTER TABLE `year_sessions`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attachments`
--
ALTER TABLE `attachments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendances`
--
ALTER TABLE `attendances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `billings`
--
ALTER TABLE `billings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `co_curriculars`
--
ALTER TABLE `co_curriculars`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `disciplines`
--
ALTER TABLE `disciplines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `extra_curriculars`
--
ALTER TABLE `extra_curriculars`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `item_requests`
--
ALTER TABLE `item_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `maintenances`
--
ALTER TABLE `maintenances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=211;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `report_user`
--
ALTER TABLE `report_user`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `streams`
--
ALTER TABLE `streams`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `terms`
--
ALTER TABLE `terms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `weeks`
--
ALTER TABLE `weeks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `year_sessions`
--
ALTER TABLE `year_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attachments`
--
ALTER TABLE `attachments`
  ADD CONSTRAINT `attachments_report_id_foreign` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendances`
--
ALTER TABLE `attendances`
  ADD CONSTRAINT `attendances_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendances_stream_id_foreign` FOREIGN KEY (`stream_id`) REFERENCES `streams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendances_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendances_week_id_foreign` FOREIGN KEY (`week_id`) REFERENCES `weeks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendances_year_session_id_foreign` FOREIGN KEY (`year_session_id`) REFERENCES `year_sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `billings`
--
ALTER TABLE `billings`
  ADD CONSTRAINT `billings_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `billings_year_session_id_foreign` FOREIGN KEY (`year_session_id`) REFERENCES `year_sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_report_id_foreign` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `co_curriculars`
--
ALTER TABLE `co_curriculars`
  ADD CONSTRAINT `co_curriculars_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `co_curriculars_year_session_id_foreign` FOREIGN KEY (`year_session_id`) REFERENCES `year_sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `disciplines`
--
ALTER TABLE `disciplines`
  ADD CONSTRAINT `disciplines_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `disciplines_stream_id_foreign` FOREIGN KEY (`stream_id`) REFERENCES `streams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `disciplines_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `disciplines_week_id_foreign` FOREIGN KEY (`week_id`) REFERENCES `weeks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `disciplines_year_session_id_foreign` FOREIGN KEY (`year_session_id`) REFERENCES `year_sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_year_session_id_foreign` FOREIGN KEY (`year_session_id`) REFERENCES `year_sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `extra_curriculars`
--
ALTER TABLE `extra_curriculars`
  ADD CONSTRAINT `extra_curriculars_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `extra_curriculars_week_id_foreign` FOREIGN KEY (`week_id`) REFERENCES `weeks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `extra_curriculars_year_session_id_foreign` FOREIGN KEY (`year_session_id`) REFERENCES `year_sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `item_requests`
--
ALTER TABLE `item_requests`
  ADD CONSTRAINT `item_requests_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `item_requests_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `item_requests_week_id_foreign` FOREIGN KEY (`week_id`) REFERENCES `weeks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `item_requests_year_session_id_foreign` FOREIGN KEY (`year_session_id`) REFERENCES `year_sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `maintenances`
--
ALTER TABLE `maintenances`
  ADD CONSTRAINT `maintenances_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `maintenances_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `maintenances_week_id_foreign` FOREIGN KEY (`week_id`) REFERENCES `weeks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `maintenances_year_session_id_foreign` FOREIGN KEY (`year_session_id`) REFERENCES `year_sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `report_user`
--
ALTER TABLE `report_user`
  ADD CONSTRAINT `report_user_report_id_foreign` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `report_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `streams`
--
ALTER TABLE `streams`
  ADD CONSTRAINT `streams_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `terms`
--
ALTER TABLE `terms`
  ADD CONSTRAINT `terms_year_session_id_foreign` FOREIGN KEY (`year_session_id`) REFERENCES `year_sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `weeks`
--
ALTER TABLE `weeks`
  ADD CONSTRAINT `weeks_term_id_foreign` FOREIGN KEY (`term_id`) REFERENCES `terms` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
