-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 01, 2025 at 06:14 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_toko`
--

-- --------------------------------------------------------

--
-- Table structure for table `barang`
--

CREATE TABLE `barang` (
  `id` int NOT NULL,
  `kode` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `stok` int NOT NULL DEFAULT '0',
  `harga` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barang`
--

INSERT INTO `barang` (`id`, `kode`, `nama`, `stok`, `harga`, `created_at`, `updated_at`) VALUES
(10011, 'KD10011', 'Thermaltake Kabel HDMI Ergonomic', 64, 336026.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10012, 'KD10012', 'SteelSeries T3 VGA Card 26\"', 81, 11693434.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10013, 'KD10013', 'D-Link Advanced Casing 18\"', 59, 1826334.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10014, 'KD10014', 'Canon Headset S3', 16, 1919367.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10015, 'KD10015', 'MSI X7 Router 14\"', 49, 847768.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10016, 'KD10016', 'Gigabyte Kabel VGA T1', 40, 173356.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10017, 'KD10017', 'Fantech V5 Router 49\"', 59, 1082219.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10018, 'KD10018', 'Motherboard Team Prime Space Gray', 38, 6767769.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10019, 'KD10019', 'Digital Alliance M1 RAM 24\"', 89, 1393690.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10020, 'KD10020', 'Lenovo G1 Headset 46\"', 78, 1630870.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10021, 'KD10021', 'Processor Samsung Nano Gold', 33, 4379980.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10022, 'KD10022', 'Router Dell T5 Merah', 34, 725579.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10023, 'KD10023', 'Samsung Hub USB Creator', 29, 446820.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10024, 'KD10024', 'NZXT Kabel LAN Nano', 90, 324901.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10025, 'KD10025', 'Access Point Xiaomi Advanced Pink', 36, 450976.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10026, 'KD10026', 'HP Basic Kabel LAN 25\"', 58, 149706.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10027, 'KD10027', 'Seagate Headset Touch', 70, 448112.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10028, 'KD10028', 'Power Supply Rexus Essential Pink', 7, 956552.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10029, 'KD10029', 'Razer Pro Keyboard 38\"', 11, 1177373.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10030, 'KD10030', 'Smartphone Apple LED Hitam', 59, 9658623.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10031, 'KD10031', 'Webcam Canon X5 Silver', 30, 1445695.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10032, 'KD10032', 'Vgen Headset Value', 8, 224775.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10033, 'KD10033', 'Microphone Acer X7 Hijau', 34, 4891462.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10034, 'KD10034', 'Canon Value Motherboard 35\"', 85, 5073599.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10035, 'KD10035', 'HyperX Card Reader Creator', 96, 182880.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10036, 'KD10036', 'Netgear Network Card Plus', 20, 177007.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10037, 'KD10037', 'Cisco V1 Tablet 14\"', 3, 10777371.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10038, 'KD10038', 'Fantech Switch A3', 60, 1607866.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10039, 'KD10039', 'Kingston Compact Motherboard 26\"', 11, 6863119.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10040, 'KD10040', 'Speaker Gigabyte V1 Abu-abu', 52, 921716.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10041, 'KD10041', 'NZXT Scanner Classic', 33, 1451997.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10042, 'KD10042', 'HP V3 Kabel HDMI 37\"', 65, 220756.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10043, 'KD10043', 'Apple Headset Student', 61, 2796926.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10044, 'KD10044', 'WD Stabilizer S1', 48, 2286893.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10045, 'KD10045', 'Transcend Plus Scanner 27\"', 14, 2818259.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10046, 'KD10046', 'Card Reader Seagate M5 Hijau', 91, 176430.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10047, 'KD10047', 'Toshiba Smartphone Standard', 100, 17999321.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10048, 'KD10048', 'Seagate Speaker T1', 9, 2060759.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10049, 'KD10049', 'Gigabyte M1 Printer 15\"', 1, 2553972.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10050, 'KD10050', 'Sandisk UPS Value', 72, 3292562.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10051, 'KD10051', 'Keyboard Vgen A5 Pink', 97, 1617282.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10052, 'KD10052', 'BenQ Hub USB X1', 72, 343130.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10053, 'KD10053', 'Corsair SSD A1', 44, 3420464.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10054, 'KD10054', 'Seagate Pro Hub USB 32\"', 79, 168401.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10055, 'KD10055', 'Samsung Microphone Essential', 38, 2386943.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10056, 'KD10056', 'VGA Card NVIDIA Mini Hitam', 79, 22633367.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10057, 'KD10057', 'LG Monitor Pro', 92, 9259173.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10058, 'KD10058', 'Kingston Premium Microphone 11\"', 35, 3203303.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10059, 'KD10059', 'SteelSeries Kabel VGA Ergonomic', 8, 200355.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10060, 'KD10060', 'AOC X2 Flashdisk 40\"', 28, 71603.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10061, 'KD10061', 'Seagate Cooling Fan T3', 93, 836235.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10062, 'KD10062', 'UPS Brother Lite Midnight Blue', 33, 3306674.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10063, 'KD10063', 'NVIDIA Basic Keyboard 13\"', 89, 938293.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10064, 'KD10064', 'ViewSonic Laptop Elite', 23, 7021368.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10065, 'KD10065', 'Stabilizer Seagate S5 Biru', 37, 1410516.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10066, 'KD10066', 'SteelSeries Hub USB M5', 49, 229125.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10067, 'KD10067', 'Kingston Creator Laptop 48\"', 89, 17785367.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10068, 'KD10068', 'Digital Alliance X3 Speaker 16\"', 65, 740958.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10069, 'KD10069', 'Kabel VGA Xiaomi Plus Carbon Black', 63, 89010.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10070, 'KD10070', 'Fantech Bluetooth Casing 44\"', 70, 2684406.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10071, 'KD10071', 'Brother X7 Switch 14\"', 42, 7660754.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10072, 'KD10072', 'Canon A3 Motherboard 28\"', 88, 9000889.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10073, 'KD10073', 'Switch NVIDIA Basic Midnight Blue', 57, 5576967.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10074, 'KD10074', 'Harddisk ViewSonic G3 Merah', 25, 2656882.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10075, 'KD10075', 'ViewSonic Power Supply Pro', 73, 987202.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10076, 'KD10076', 'HP Router Compact', 39, 4844201.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10077, 'KD10077', 'Team A7 Laptop 14\"', 27, 9569836.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10078, 'KD10078', 'Speaker WD Premium Space Gray', 80, 1473480.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10079, 'KD10079', 'Mouse Gigabyte Touch Biru', 64, 196690.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10080, 'KD10080', 'Netgear Casing Premium', 35, 1012643.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10081, 'KD10081', 'Xiaomi Performance Power Supply 33\"', 40, 475494.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10082, 'KD10082', 'Headset Toshiba S3 Space Gray', 20, 1389755.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10083, 'KD10083', 'Gigabyte Business Monitor 13\"', 54, 3885528.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10084, 'KD10084', 'BenQ Microphone V5', 9, 511126.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10085, 'KD10085', 'Transcend Cooling Fan V3', 68, 237493.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10086, 'KD10086', 'Toshiba Kabel LAN T1', 60, 361880.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10087, 'KD10087', 'Kabel LAN NZXT Ergonomic Carbon Black', 52, 200882.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10088, 'KD10088', 'HyperX Slim Switch 48\"', 8, 2082406.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10089, 'KD10089', 'Network Card Apple M3 Abu-abu', 56, 504948.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10090, 'KD10090', 'ViewSonic A1 Access Point 45\"', 58, 2743049.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10091, 'KD10091', 'TP-Link G1 VGA Card 19\"', 85, 28367629.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10092, 'KD10092', 'Digital Alliance Kabel HDMI G7', 46, 482138.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10093, 'KD10093', 'Kabel HDMI LG Wireless Oranye', 98, 278645.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10094, 'KD10094', 'Kabel LAN Cooler Master Compact Midnight Blue', 84, 383824.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10095, 'KD10095', 'Samsung Ultra Stabilizer 41\"', 73, 1780260.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10096, 'KD10096', 'Logitech Card Reader Lite', 21, 242510.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10097, 'KD10097', 'Brother Wireless Microphone 36\"', 61, 1558950.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10098, 'KD10098', 'LG Cooling Fan Wireless', 94, 326623.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10099, 'KD10099', 'Brother Switch Student', 12, 9073048.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10100, 'KD10100', 'Netgear Kabel HDMI Slim', 54, 89469.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10101, 'KD10101', 'Huawei S5 Router 12\"', 6, 2347450.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10102, 'KD10102', 'SSD AMD Pro Merah', 77, 2172099.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10103, 'KD10103', 'Canon Casing S3', 4, 1558091.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10104, 'KD10104', 'Xiaomi Switch Wireless', 72, 4015658.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10105, 'KD10105', 'Vgen VGA Card X3', 40, 13085624.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10106, 'KD10106', 'HyperX G3 Kabel LAN 13\"', 7, 456617.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10107, 'KD10107', 'SSD MSI A5 Silver', 79, 2811221.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10108, 'KD10108', 'Team Stabilizer Business', 27, 322332.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10109, 'KD10109', 'LG Converter Business', 12, 437488.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57'),
(10110, 'KD10110', 'Huawei Processor Bluetooth', 54, 12981256.00, '2025-04-01 18:10:57', '2025-04-01 18:10:57');

-- --------------------------------------------------------

--
-- Table structure for table `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `aktivitas` varchar(255) NOT NULL,
  `timestamp` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_aktivitas`
--

INSERT INTO `log_aktivitas` (`id`, `user_id`, `aktivitas`, `timestamp`) VALUES
(1, 1, 'Login ke sistem', '2025-04-01 15:12:22'),
(2, 1, 'Logout dari sistem', '2025-04-01 15:24:03'),
(3, 2, 'Login ke sistem', '2025-04-01 15:24:11'),
(4, 2, 'Logout dari sistem', '2025-04-01 15:44:20'),
(5, 1, 'Login ke sistem', '2025-04-01 15:44:28'),
(6, 1, 'Logout dari sistem', '2025-04-01 15:45:44'),
(7, 2, 'Login ke sistem', '2025-04-01 15:45:52'),
(8, 2, 'Membuat transaksi baru dengan ID: 1', '2025-04-01 23:01:59'),
(9, 2, 'Mengedit barang: KD005 - Headset Gaming Rexus', '2025-04-01 23:02:48'),
(10, 2, 'Membuat transaksi baru dengan ID: 2', '2025-04-01 23:03:18'),
(11, 2, 'Logout dari sistem', '2025-04-01 16:07:25'),
(12, 1, 'Login ke sistem', '2025-04-01 16:07:32'),
(13, 1, 'Logout dari sistem', '2025-04-01 16:10:18'),
(14, 2, 'Login ke sistem', '2025-04-01 16:10:26'),
(15, 2, 'Menghapus barang: KD004 - Keyboard Mechanical Fantech', '2025-04-01 23:15:28'),
(16, 2, 'Logout dari sistem', '2025-04-01 16:16:35'),
(17, 1, 'Logout dari sistem', '2025-04-01 16:18:14'),
(18, 1, 'Login ke sistem', '2025-04-01 16:18:22'),
(19, 1, 'Logout dari sistem', '2025-04-01 16:18:33'),
(20, 2, 'Login ke sistem', '2025-04-01 16:18:58'),
(21, 2, 'Membuat transaksi baru dengan ID: 3', '2025-04-01 23:42:13'),
(22, 2, 'error', '2025-04-01 14:20:16'),
(23, 2, 'error', '2025-04-01 14:20:21'),
(24, 2, 'transaksi', '2025-04-01 14:23:51'),
(25, 2, 'transaksi', '2025-04-01 14:24:38'),
(26, 2, 'Menghapus barang: KD02666 - Access Point AMD Creator Merah', '2025-04-02 01:08:30'),
(27, 2, 'Logout dari sistem', '2025-04-01 18:09:02'),
(28, 1, 'Login ke sistem', '2025-04-01 18:09:10');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int NOT NULL,
  `tanggal` datetime NOT NULL,
  `user_id` int NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`id`, `tanggal`, `user_id`, `total`, `created_at`) VALUES
(1, '2025-04-01 23:01:59', 2, 275000.00, '2025-04-01 16:01:59'),
(2, '2025-04-01 23:03:18', 2, 275000.00, '2025-04-01 16:03:18'),
(3, '2025-04-01 23:42:13', 2, 41786824.00, '2025-04-01 16:42:13'),
(10, '2025-04-02 00:23:51', 2, 13464063.00, '2025-04-01 17:23:51'),
(11, '2025-04-02 00:24:38', 2, 6837646.00, '2025-04-01 17:24:38');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi_detail`
--

CREATE TABLE `transaksi_detail` (
  `id` int NOT NULL,
  `transaksi_id` int NOT NULL,
  `barang_id` int NOT NULL,
  `jumlah` int NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `role` enum('admin','penjaga') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `remember_token` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama`, `role`, `created_at`, `remember_token`) VALUES
(1, 'admin', '$2y$10$2JZlRVTF4Eqymk2oeVQEA./H1fu6e4N3FCIhEDHIrhzWl7UAKuYrS', 'Administrator', 'admin', '2025-04-01 15:12:00', '88b5d86e3278e1a0340ccef45647f5d5'),
(2, 'penjaga', '$2y$10$dcciqi33J876NTNemFTer.BN67elij1lNbbanqG2siXbl4h0E2B4S', 'Penjaga Toko', 'penjaga', '2025-04-01 15:12:00', '84ca8c8cd1cc780ed4ad944ee9e45553');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode` (`kode`);

--
-- Indexes for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `transaksi_detail`
--
ALTER TABLE `transaksi_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaksi_id` (`transaksi_id`),
  ADD KEY `barang_id` (`barang_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barang`
--
ALTER TABLE `barang`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10111;

--
-- AUTO_INCREMENT for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `transaksi_detail`
--
ALTER TABLE `transaksi_detail`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD CONSTRAINT `log_aktivitas_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `transaksi_detail`
--
ALTER TABLE `transaksi_detail`
  ADD CONSTRAINT `transaksi_detail_ibfk_1` FOREIGN KEY (`transaksi_id`) REFERENCES `transaksi` (`id`),
  ADD CONSTRAINT `transaksi_detail_ibfk_2` FOREIGN KEY (`barang_id`) REFERENCES `barang` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
