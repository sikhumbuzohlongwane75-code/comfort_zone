-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Nov 20, 2026 at 03:57 PM
-- Server version: 11.4.9-MariaDB
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `comfort_zone_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(59, 1, 'login', 'User logged in', '::1', '2026-08-30 21:02:19'),
(58, 6, 'login', 'User logged in', '::1', '2026-08-30 09:40:09'),
(57, 5, 'logout', 'User logged out', '::1', '2026-08-29 20:26:19'),
(56, 5, 'logout', 'User logged out', '::1', '2026-08-29 19:37:33');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
CREATE TABLE IF NOT EXISTS `cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_cart_item` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `session_id`, `product_id`, `quantity`, `created_at`) VALUES
(24, 1, NULL, 3, 1, '2026-08-31 11:57:21'),
(45, 7, NULL, 23, 1, '2026-11-14 16:27:54'),
(46, 7, NULL, 21, 1, '2026-11-14 16:27:58'),
(23, 1, NULL, 16, 1, '2026-08-31 11:53:39'),
(18, 1, NULL, 13, 1, '2026-08-30 21:56:49');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_gps_logs`
--

DROP TABLE IF EXISTS `delivery_gps_logs`;
CREATE TABLE IF NOT EXISTS `delivery_gps_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `driver_name` varchar(100) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `status` varchar(50) DEFAULT 'En Route',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `delivery_address` text DEFAULT NULL,
  `items` text NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `delivery_fee` decimal(10,2) DEFAULT 0.00,
  `tax` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','completed','failed') DEFAULT 'pending',
  `order_status` enum('pending','processing','shipped','completed','cancelled') DEFAULT 'pending',
  `order_type` enum('Delivery','Pickup') DEFAULT 'Pickup',
  `special_instructions` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `user_id`, `customer_name`, `customer_email`, `customer_phone`, `delivery_address`, `items`, `subtotal`, `delivery_fee`, `tax`, `total`, `payment_method`, `payment_status`, `order_status`, `order_type`, `special_instructions`, `created_at`, `updated_at`) VALUES
(13, 'CZ-9FDB1', 6, 'Sikhumbuzo Hlongwane', 'Sikhumbuzohlongwane75@gmail.com', '0799226833', '511 Griffiths Mxenge Highway\r\nMangosuthu University of Technology 511 Mangosuthu Highway', '[{\"id\":21,\"user_id\":6,\"session_id\":null,\"product_id\":13,\"quantity\":1,\"created_at\":\"2026-08-31 03:04:31\",\"name\":\"\\ud83c\\udf3f Immune Booster Tea\",\"price\":\"85.00\"},{\"id\":20,\"user_id\":6,\"session_id\":null,\"product_id\":14,\"quantity\":1,\"created_at\":\"2026-08-31 02:49:42\",\"name\":\"\\ud83d\\udcaa Moringa Power Powder\",\"price\":\"95.00\"}]', 180.00, 35.00, 27.00, 242.00, 'eft_paysharp', 'pending', 'pending', 'Delivery', '', '2026-08-31 01:27:33', '2026-08-31 01:27:33'),
(14, 'CZ-C4A64', 6, 'Sikhumbuzo Hlongwane', 'Sikhumbuzohlongwane75@gmail.com', '0799226833', 'Mangosuthu University of Technology 511 Mangosuthu Highway', '[{\"id\":22,\"user_id\":6,\"session_id\":null,\"product_id\":2,\"quantity\":1,\"created_at\":\"2026-08-31 13:33:49\",\"name\":\"\\ud83c\\udf57 Peri-Peri Chicken\",\"price\":\"95.00\"}]', 95.00, 35.00, 14.25, 144.25, 'eft_paysharp', 'pending', 'completed', 'Delivery', '', '2026-08-31 11:34:51', '2026-08-31 18:39:18'),
(15, 'CZ-5210D', 6, 'Sikhumbuzo Hlongwane', 'Sikhumbuzohlongwane75@gmail.com', '0799226833', 'Walk-In Counter Order', '[{\"id\":25,\"user_id\":6,\"session_id\":null,\"product_id\":2,\"quantity\":1,\"created_at\":\"2026-08-31 17:37:52\",\"name\":\"\\ud83c\\udf57 Peri-Peri Chicken\",\"price\":\"95.00\"}]', 95.00, 0.00, 14.25, 109.25, 'eft_paysharp', 'pending', 'shipped', 'Pickup', '', '2026-08-31 16:31:39', '2026-08-31 20:42:43'),
(17, 'CZ-3DBC5', 6, 'Sikhumbuzo Hlongwane', 'Sikhumbuzohlongwane75@gmail.com', '0799226833', 'Walk-In Counter Order', '[{\"id\":27,\"user_id\":6,\"session_id\":null,\"product_id\":9,\"quantity\":1,\"created_at\":\"2026-08-31 22:18:02\",\"name\":\"\\ud83e\\udd64 Craft Soda\",\"price\":\"18.00\"}]', 18.00, 0.00, 2.70, 20.70, 'eft_paysharp', 'pending', 'pending', 'Pickup', '', '2026-08-31 21:15:22', '2026-08-31 21:15:22'),
(18, 'CZ-40EAB', 6, 'Sikhumbuzo Hlongwane', 'Sikhumbuzohlongwane75@gmail.com', '0799226833', 'Walk-In Counter Order', '[{\"id\":28,\"user_id\":6,\"session_id\":null,\"product_id\":2,\"quantity\":1,\"created_at\":\"2026-08-31 23:30:21\",\"name\":\"\\ud83c\\udf57 Peri-Peri Chicken\",\"price\":\"95.00\"},{\"id\":29,\"user_id\":6,\"session_id\":null,\"product_id\":4,\"quantity\":1,\"created_at\":\"2026-08-31 23:30:31\",\"name\":\"\\ud83c\\udf2d Boerewors Roll\",\"price\":\"65.00\"}]', 160.00, 0.00, 24.00, 184.00, 'eft_paysharp', 'pending', 'pending', 'Pickup', '', '2026-08-31 21:30:44', '2026-08-31 21:30:44'),
(19, 'CZ-1A466', 6, 'Sikhumbuzo Hlongwane', 'Sikhumbuzohlongwane75@gmail.com', '0799226833', 'Walk-In Counter Order', '[{\"id\":30,\"user_id\":6,\"session_id\":null,\"product_id\":15,\"quantity\":1,\"created_at\":\"2026-08-31 23:44:17\",\"name\":\"\\ud83c\\udf75 Detox Herbal Tea\",\"price\":\"65.00\"}]', 65.00, 0.00, 9.75, 74.75, 'eft_paysharp', 'pending', 'pending', 'Pickup', '', '2026-08-31 21:44:34', '2026-08-31 21:44:34'),
(20, 'CZ-B0279', 6, 'Sikhumbuzo Hlongwane', 'Sikhumbuzohlongwane75@gmail.com', '0799226833', 'Walk-In Counter Order', '[{\"id\":33,\"user_id\":6,\"session_id\":null,\"product_id\":23,\"quantity\":4,\"created_at\":\"2026-09-01 10:26:23\",\"name\":\"chicken dust\",\"price\":\"25.00\"}]', 100.00, 0.00, 15.00, 115.00, 'eft_paysharp', 'pending', 'completed', 'Pickup', '', '2026-09-01 08:29:09', '2026-09-01 08:31:18'),
(21, 'CZ-4FC86', 6, 'Sikhumbuzo Hlongwane', 'Sikhumbuzohlongwane75@gmail.com', '0799226833', 'Walk-In Counter Order', '[{\"id\":34,\"user_id\":6,\"session_id\":null,\"product_id\":23,\"quantity\":4,\"created_at\":\"2026-09-01 16:56:38\",\"name\":\"chicken dust\",\"price\":\"25.00\"}]', 100.00, 0.00, 15.00, 115.00, 'eft_paysharp', 'pending', 'pending', 'Pickup', '', '2026-09-01 14:56:59', '2026-09-01 14:56:59'),
(22, 'CZ-EB136', 7, 'SINDI HLONGWANE', 'Sindihlongwane75@gmail.com', '0793480102', 'Walk-In Counter Order', '[{\"id\":36,\"user_id\":7,\"session_id\":null,\"product_id\":3,\"quantity\":1,\"created_at\":\"2026-11-09 19:44:43\",\"name\":\"\\ud83e\\udd69 Lamb Chops\",\"price\":\"185.00\"},{\"id\":35,\"user_id\":7,\"session_id\":null,\"product_id\":5,\"quantity\":1,\"created_at\":\"2026-11-09 19:44:42\",\"name\":\"\\ud83c\\udf3d Pap & Chakalaka\",\"price\":\"35.00\"},{\"id\":37,\"user_id\":7,\"session_id\":null,\"product_id\":16,\"quantity\":1,\"created_at\":\"2026-11-13 17:44:42\",\"name\":\"\\ud83c\\udf3f African Wormwood\",\"price\":\"75.00\"},{\"id\":38,\"user_id\":7,\"session_id\":null,\"product_id\":18,\"quantity\":1,\"created_at\":\"2026-11-13 17:44:43\",\"name\":\"\\ud83d\\udc68\\u200d\\ud83d\\udc69\\u200d\\ud83d\\udc67 Family Feast\",\"price\":\"550.00\"}]', 845.00, 0.00, 126.75, 971.75, 'eft_paysharp', 'pending', 'pending', 'Pickup', '', '2026-11-13 15:44:55', '2026-11-13 15:44:55'),
(23, 'CZ-DD2E5', 7, 'SINDI HLONGWANE', 'Sindihlongwane75@gmail.com', '0793480102', 'Walk-In Counter Order', '[{\"id\":39,\"user_id\":7,\"session_id\":null,\"product_id\":3,\"quantity\":1,\"created_at\":\"2026-11-13 19:11:35\",\"name\":\"\\ud83e\\udd69 Lamb Chops\",\"price\":\"185.00\"},{\"id\":40,\"user_id\":7,\"session_id\":null,\"product_id\":5,\"quantity\":1,\"created_at\":\"2026-11-13 19:11:36\",\"name\":\"\\ud83c\\udf3d Pap & Chakalaka\",\"price\":\"35.00\"}]', 220.00, 0.00, 33.00, 253.00, 'eft_paysharp', 'pending', 'pending', 'Pickup', '', '2026-11-13 17:11:44', '2026-11-13 17:11:44'),
(24, 'CZ-CFBDA', 7, 'SINDI HLONGWANE', 'Sindihlongwane75@gmail.com', '0793480102', 'Walk-In Counter Order', '[{\"id\":41,\"user_id\":7,\"session_id\":null,\"product_id\":3,\"quantity\":1,\"created_at\":\"2026-11-14 18:18:04\",\"name\":\"\\ud83e\\udd69 Lamb Chops\",\"price\":\"185.00\"},{\"id\":42,\"user_id\":7,\"session_id\":null,\"product_id\":5,\"quantity\":1,\"created_at\":\"2026-11-14 18:18:05\",\"name\":\"\\ud83c\\udf3d Pap & Chakalaka\",\"price\":\"35.00\"}]', 220.00, 0.00, 33.00, 253.00, 'eft_paysharp', 'pending', 'pending', 'Pickup', '', '2026-11-14 16:18:15', '2026-11-14 16:18:15'),
(25, 'CZ-4B94E', 7, 'SINDI HLONGWANE', 'Sindihlongwane75@gmail.com', '0793480102', 'Walk-In Counter Order', '[{\"id\":44,\"user_id\":7,\"session_id\":null,\"product_id\":10,\"quantity\":1,\"created_at\":\"2026-11-14 18:22:58\",\"name\":\"\\ud83c\\udf7a Local Lager\",\"price\":\"28.00\"},{\"id\":43,\"user_id\":7,\"session_id\":null,\"product_id\":14,\"quantity\":1,\"created_at\":\"2026-11-14 18:22:57\",\"name\":\"\\ud83d\\udcaa Moringa Power Powder\",\"price\":\"95.00\"}]', 123.00, 0.00, 18.45, 141.45, 'eft_paysharp', 'pending', 'pending', 'Pickup', '', '2026-11-14 16:23:07', '2026-11-14 16:23:07'),
(26, 'CZ-D0D23', 8, 'Bill Gyokeres', '', '0687946339', 'House 133, Isipingo Hills, Isipingo, 4033', '[{\"id\":47,\"user_id\":8,\"session_id\":null,\"product_id\":11,\"quantity\":1,\"created_at\":\"2026-11-18 20:08:35\",\"name\":\"\\ud83e\\uddc3 Traditional Ginger Beer\",\"price\":\"22.00\"},{\"id\":48,\"user_id\":8,\"session_id\":null,\"product_id\":15,\"quantity\":1,\"created_at\":\"2026-11-18 20:08:40\",\"name\":\"\\ud83c\\udf75 Lung Revival Tea\",\"price\":\"65.00\"},{\"id\":49,\"user_id\":8,\"session_id\":null,\"product_id\":20,\"quantity\":1,\"created_at\":\"2026-11-18 20:08:45\",\"name\":\"\\ud83e\\udd69Grilled Beef Steak\",\"price\":\"125.00\"}]', 212.00, 35.00, 31.80, 278.80, 'direct_eft', 'pending', 'pending', 'Delivery', '', '2026-11-18 18:09:53', '2026-11-18 18:09:53');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`email`, `token`, `created_at`) VALUES
('Sikhumbuzohlongwane75@gmail.com', 'e41d941b56ac87d4fde14aabe0267a4771ab014eb5df4f58ea12324f208c45f4', '2026-11-16 20:30:57');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `category` enum('meat','side_dish','beverage','herbal','grilled_special') NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `original_price` decimal(10,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `min_stock_level` int(11) DEFAULT 5,
  `is_available` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category`, `price`, `original_price`, `description`, `stock`, `min_stock_level`, `is_available`, `is_featured`, `created_at`, `image`) VALUES
(1, '🔥 BBQ, Chicken breast plate with fries', 'meat', 245.00, 165.00, 'Ultimate bbq chicken breast plate with fries baby potatoes and creamy slaw🍟🥔🤎🍗', 48, 10, 1, 1, '2026-05-18 08:21:46', '../uploads/products/prod_6afda3185f3291.28712462.jpg'),
(2, '🍗 Chilli Grilled Chicken Skewers', 'meat', 95.00, 115.00, 'Easy Homemade Sweet Chilli Grilled Chicken Skewers _ Free BBQ Recipe', 41, 10, 1, 1, '2026-05-18 08:21:46', '../uploads/products/prod_6afda35cd0fd30.97466439.jpg'),
(3, '🍗 Shisanyama gold platter', 'meat', 185.00, 210.00, 'Premium grilled over open flame.', 26, 8, 1, 1, '2026-05-18 08:21:46', NULL),
(4, '🌭 Boerewors Roll', 'meat', 65.00, 80.00, 'Traditional South African Boerewors served with peri-peri sauce.', 59, 15, 1, 0, '2026-05-18 08:21:46', '../uploads/products/prod_6afda38397e845.94077535.jpg'),
(5, '🌽 Pap & Chakalaka', 'meat', 35.00, 45.00, 'Traditional pap served with spicy chakalaka relish.', 114, 20, 1, 1, '2026-05-18 08:21:46', 'uploads/products/prod_6a966e92914507.94470806.jpg'),
(6, '🥗 Fresh Coleslaw', 'meat', 25.00, 30.00, 'Crispy cabbage and carrots with creamy dressing.', 99, 15, 1, 0, '2026-05-18 08:21:46', '../uploads/products/prod_6afda69483f8a7.64230956.jpg'),
(7, '🍟 Crispy Fries', 'meat', 28.00, 35.00, 'Crispy Seasoned French Fries with Creamy Dipping Sauce', 100, 20, 1, 0, '2026-05-18 08:21:46', '../uploads/products/prod_6afda416015a11.55547805.jpg'),
(8, '🍚 Rice & Gravy', 'meat', 25.00, 30.00, 'Fluffy white rice with garlic Pepper Beef Stew.', 100, 15, 1, 0, '2026-05-18 08:21:46', '../uploads/products/prod_6b00683f893051.96669491.jpg'),
(9, '🥤 Craft Soda', 'beverage', 18.00, 25.00, 'Artisanal craft soda - choice of flavors.', 198, 30, 1, 0, '2026-05-18 08:21:46', 'uploads/products/prod_6a967b59306863.59265244.jpg'),
(10, '🍺 Local Lager', 'meat', 28.00, 35.00, 'Refreshing local beer.', 149, 25, 1, 0, '2026-05-18 08:21:46', '../uploads/products/prod_6afd9a87cfcde0.54188502.jpg'),
(11, '🧃 Traditional Ginger Beer', 'beverage', 22.00, 30.00, 'Homemade spicy ginger beer.', 79, 15, 1, 1, '2026-05-18 08:21:46', 'uploads/products/prod_6a966e7189ca52.96879315.jpg'),
(19, '🥩Medium family feast', 'meat', 300.00, 30.00, 'family treat for the small families', 555, 5, 1, 0, '2026-05-20 19:05:08', 'uploads/products/prod_6a967926c9b395.80005492.jpg'),
(13, '🌿 Allergy Relief', 'meat', 85.00, 120.00, 'Herbal Antihistamine support', 40, 8, 1, 1, '2026-05-18 08:21:46', 'uploads/products/prod_6a9670236da6f1.52762566.jpg'),
(14, '💪 Moringa Power Powder', 'meat', 95.00, 130.00, 'Pure moringa powder - rich in antioxidants and vitamins.', 34, 8, 1, 0, '2026-05-18 08:21:46', '../uploads/products/prod_6afd99bf487608.13494140.jpg'),
(15, '🍵 Lung Revival Tea', 'meat', 65.00, 90.00, 'Supports clear breathing and lung health smokers, asthma and everyday detoxing.', 48, 10, 1, 0, '2026-05-18 08:21:46', 'uploads/products/prod_6a956c42727c51.93352800.jpg'),
(16, '🌿 African Wormwood', 'meat', 75.00, 100.00, 'Traditional African remedy for colds and flu.', 24, 5, 1, 1, '2026-05-18 08:21:46', '../uploads/products/prod_6afd9e8abfa519.01906880.jpg'),
(17, '🔥 Ultimate Braai Platter', 'meat', 350.00, 450.00, 'Mixed grill: beef, chicken, lamb, boerewors. Feeds 2-3 people.', 28, 5, 1, 1, '2026-05-18 08:21:46', '../uploads/products/prod_6afda6065ea071.86515808.jpg'),
(18, '👨‍👩‍👧 Family Feast', 'meat', 550.00, 700.00, 'Complete feast for 4-6 people. Includes all meats, sides, and drinks.', 19, 3, 1, 1, '2026-05-18 08:21:46', 'uploads/products/prod_6a966ef9410978.67109134.jpg'),
(20, '🥩Grilled Beef Steak', 'meat', 125.00, NULL, 'Tender beef steak grilled to perfection with secret spices. Served with your choice of sauce.', 19, 5, 1, 0, '2026-09-01 06:54:30', '../uploads/products/prod_6afd9922272d09.07371577.jpg'),
(21, '🥩Lamb Chops', 'meat', 180.00, NULL, 'Premium lamb chops seasoned with herbs and grilled over open flame.', 25, 5, 1, 0, '2026-09-01 06:54:30', '../uploads/products/prod_6afd990f0aab22.45380328.jpg'),
(22, '🍗Peri-Peri Chicken', 'meat', 95.00, NULL, 'Succulent chicken marinated in authentic peri-peri sauce, flame-grilled.', 25, 5, 1, 0, '2026-09-01 06:54:30', '../uploads/products/prod_6afd98f1a000c8.76153115.jpg'),
(23, '🍗Chicken dust', 'meat', 25.00, NULL, 'Peri peri chicken dust.', 22, 5, 1, 0, '2026-09-01 06:54:30', 'uploads/products/prod_6a96786c513540.23943704.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `stock_transactions`
--

DROP TABLE IF EXISTS `stock_transactions`;
CREATE TABLE IF NOT EXISTS `stock_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `transaction_type` enum('purchase','sale','adjustment') NOT NULL,
  `quantity` int(11) NOT NULL,
  `previous_stock` int(11) NOT NULL,
  `new_stock` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','staff','admin') DEFAULT 'customer',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `phone`, `password`, `role`, `is_active`, `created_at`, `last_login`) VALUES
(1, 'System Admin', 'admin@comfortzone.com', '0712345678', '$2y$10$QCRrrOVlHvk.vqnYaBFxVOaL679ybssSiPoIJ.J8//umn2VcSz20y', 'admin', 1, '2026-05-20 16:49:38', '2026-11-20 17:31:04'),
(8, 'Bill Gyokeres', 'BillGyokeres@goatlifestyle.co.za', '0687946339', '$2y$10$Ot.SoMhQEIHyJpywn3cP8.sgE7YOpOQWKcB0EcK/back1k.hx2Sxu', 'customer', 1, '2026-11-18 18:08:26', '2026-11-20 17:26:05'),
(7, 'SINDI HLONGWANE', 'Sindihlongwane75@gmail.com', '0793480102', '$2y$10$uD9IvdLjTLIj5GnMhno5GuYZIcq8JpDrjRwIXqqayMW0gTw5jluIy', 'customer', 1, '2026-11-09 17:43:05', '2026-11-18 07:45:50'),
(5, 'Staff', 'staff@comfortzone.com', '0700000000', '$2y$10$XPX.695OFsjakWIboq7eMO1OkrCn20w3C0dlmv2rYr7BliGCB9cqm', 'staff', 1, '2026-08-29 14:00:52', '2026-11-16 22:54:11'),
(6, 'Sikhumbuzo Hlongwane', 'Sikhumbuzohlongwane75@gmail.com', '0799226833', '$2y$10$bZ1HZ8BSPI4gMw.F0eoE3eY5oqsNNb3/qqHqtoUQtxRh5fdJqbjYK', 'customer', 1, '2026-08-29 14:44:27', '2026-09-01 16:56:25');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
