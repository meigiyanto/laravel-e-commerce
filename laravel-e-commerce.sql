-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 02, 2026 at 06:03 PM
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
-- Database: `laravel-e-commerce`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`id`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 2, '2026-10-02 08:23:24', '2026-10-02 08:23:24');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cart_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `image`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Elektronik', 'elektronik', 'https://images.unsplash.com/photo-1498049794561-7780e7231661?auto=format&fit=crop&w=800&q=80', 'Berbagai macam produk elektronik dan aksesoris teknologi.', '2026-10-02 08:22:04', '2026-10-02 08:22:04'),
(2, 'Fashion', 'fashion', 'https://images.unsplash.com/photo-1445205170230-053b83016050?auto=format&fit=crop&w=800&q=80', 'Produk fashion pria dan wanita.', '2026-10-02 08:22:04', '2026-10-02 08:22:04'),
(3, 'Rumah Tangga', 'rumah-tangga', 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=800&q=80', 'Berbagai kebutuhan rumah tangga sehari-hari.', '2026-10-02 08:22:04', '2026-10-02 08:22:04'),
(4, 'Komputer', 'komputer', 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=800&q=80', 'Perangkat komputer dan aksesoris pendukungnya.', '2026-10-02 08:22:04', '2026-10-02 08:22:04'),
(5, 'Olahraga', 'olahraga', 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?auto=format&fit=crop&w=800&q=80', '', '2026-10-02 08:22:04', '2026-10-02 08:22:04');

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
(16, '2026_09_28_222942_create_categories_table', 1),
(17, '2026_09_28_223104_create_sub_categories_table', 1),
(18, '2026_09_28_223312_create_products_table', 1),
(19, '2026_09_28_223952_create_users_table', 1),
(20, '2026_09_28_224058_create_password_reset_tokens_table', 1),
(21, '2026_09_28_224227_create_sessions_table', 1),
(22, '2026_09_29_072801_create_cache_table', 1),
(23, '2026_09_29_075104_create_orders_table', 1),
(24, '2026_09_29_075314_create_order_items_table', 1),
(25, '2026_09_29_080531_add_role_to_users_table', 1),
(26, '2026_09_29_183703_create_carts_table', 1),
(27, '2026_09_29_183809_create_cart_items_table', 1),
(28, '2026_09_30_051216_create_reviews_table', 1),
(29, '2026_09_30_065747_create_wishlists_table', 1),
(30, '2026_09_30_121812_create_payments_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `order_number` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `customer_name` varchar(255) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `shipping_address` text NOT NULL,
  `notes` text DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `shipping_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `status`, `customer_name`, `phone`, `shipping_address`, `notes`, `subtotal`, `shipping_cost`, `total`, `created_at`, `updated_at`) VALUES
(1, 2, 'ORD-20261002152403-ZV0WI', 'pending', 'Test User', '085282769582', 'Pekaja RT 2 RW 1 Kalibagor Banyumas Jawa Tengah Indonesia 53191', NULL, 449000.00, 0.00, 449000.00, '2026-10-02 08:24:03', '2026-10-02 08:24:03'),
(2, 2, 'ORD-20261002154238-ADGBY', 'pending', 'Test User', '085282769582', 'Pekaja RT 2 RW 1 Kalibagor Banyumas Jawa Tengah 53191', NULL, 999000.00, 0.00, 999000.00, '2026-10-02 08:42:38', '2026-10-02 08:42:38'),
(3, 2, 'ORD-20261002154729-NHWAY', 'pending', 'Test User', '085282769582', 'Pekaja RT 2 RW 1 Kalibagor Banyumas Jawa Tengah 53191', NULL, 649000.00, 0.00, 649000.00, '2026-10-02 08:47:29', '2026-10-02 08:47:29');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `price`, `quantity`, `subtotal`, `created_at`, `updated_at`) VALUES
(1, 1, 5, 'Women\'s Casual Dress', 449000.00, 1, 449000.00, '2026-10-02 08:24:03', '2026-10-02 08:24:03'),
(2, 2, 9, 'Running Shoes Pro', 999000.00, 1, 999000.00, '2026-10-02 08:42:38', '2026-10-02 08:42:38'),
(3, 3, 10, 'Hiking Backpack', 649000.00, 1, 649000.00, '2026-10-02 08:47:29', '2026-10-02 08:47:29');

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
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `provider` varchar(255) DEFAULT NULL,
  `reference_id` varchar(255) DEFAULT NULL,
  `stripe_payment_intent_id` varchar(255) DEFAULT NULL,
  `currency` varchar(255) NOT NULL DEFAULT 'IDR',
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `payment_method` varchar(255) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `payment_type` varchar(255) DEFAULT NULL,
  `transaction_status` varchar(255) NOT NULL DEFAULT 'pending',
  `fraud_status` varchar(255) DEFAULT NULL,
  `status_code` varchar(255) DEFAULT NULL,
  `gross_amount` decimal(15,2) NOT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `provider`, `reference_id`, `stripe_payment_intent_id`, `currency`, `status`, `payment_method`, `transaction_id`, `payment_type`, `transaction_status`, `fraud_status`, `status_code`, `gross_amount`, `paid_at`, `expires_at`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 1, 'stripe', NULL, NULL, 'IDR', 'pending', 'stripe', NULL, 'card', 'pending', NULL, NULL, 449000.00, NULL, NULL, NULL, '2026-10-02 08:24:03', '2026-10-02 08:24:03'),
(2, 2, 'stripe', NULL, NULL, 'IDR', 'pending', 'stripe', NULL, 'card', 'pending', NULL, NULL, 999000.00, NULL, NULL, NULL, '2026-10-02 08:42:38', '2026-10-02 08:42:38'),
(3, 3, 'stripe', NULL, 'pi_3UM8jjPKDWdy3rCf0t1ajjj2', 'IDR', 'pending', 'stripe', 'pi_3UM8jjPKDWdy3rCf0t1ajjj2', 'card', 'requires_payment_method', NULL, NULL, 649000.00, NULL, NULL, '{\"stripe_payment_intent_status\":\"requires_payment_method\"}', '2026-10-02 08:47:29', '2026-10-02 08:51:04');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `sub_category_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(15,2) NOT NULL,
  `stock` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `sub_category_id`, `name`, `slug`, `description`, `price`, `stock`, `image`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Smartphone X1', 'smartphone-x1', 'Smartphone modern dengan performa tinggi dan desain elegan.', 4999000.00, 25, 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=800&q=80', '2026-10-02 08:22:05', '2026-10-02 08:22:05'),
(2, 1, 2, 'Pro Laptop 14', 'pro-laptop-14', 'Laptop ringan dan powerful untuk pekerjaan, bisnis, dan produktivitas.', 12999000.00, 15, 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=800&q=80', '2026-10-02 08:22:05', '2026-10-02 08:22:05'),
(3, 1, 4, 'Wireless Headset', 'wireless-headset', 'Headset wireless dengan desain nyaman dan kualitas audio jernih.', 799000.00, 40, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80', '2026-10-02 08:22:05', '2026-10-02 08:22:05'),
(4, 2, 8, 'Classic Men\'s Jacket', 'classic-mens-jacket', 'Jaket pria dengan desain klasik yang cocok untuk berbagai kesempatan.', 599000.00, 30, 'https://images.unsplash.com/photo-1551028719-00167b16eac5?auto=format&fit=crop&w=800&q=80', '2026-10-02 08:22:05', '2026-10-02 08:22:05'),
(5, 2, 9, 'Women\'s Casual Dress', 'womens-casual-dress', 'Dress kasual wanita dengan desain modern dan nyaman digunakan.', 449000.00, 34, 'https://images.unsplash.com/photo-1496747611176-843222e1e57c?auto=format&fit=crop&w=800&q=80', '2026-10-02 08:22:05', '2026-10-02 08:24:03'),
(6, 3, 11, 'Modern Sofa', 'modern-sofa', 'Sofa modern dengan desain minimalis untuk ruang keluarga.', 4999000.00, 10, 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=800&q=80', '2026-10-02 08:22:05', '2026-10-02 08:22:05'),
(7, 3, 10, 'Dapur Cookware Set', 'dapur-cookware-set', 'Set peralatan masak lengkap untuk kebutuhan dapur sehari-hari.', 899000.00, 20, 'https://images.unsplash.com/photo-1556911220-bff31c812dba?auto=format&fit=crop&w=800&q=80', '2026-10-02 08:22:05', '2026-10-02 08:22:05'),
(8, 5, 14, 'Adjustable Dumbbell', 'adjustable-dumbbell', 'Dumbbell adjustable untuk latihan kekuatan di rumah.', 749000.00, 25, 'https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?auto=format&fit=crop&w=800&q=80', '2026-10-02 08:22:05', '2026-10-02 08:22:05'),
(9, 5, 15, 'Running Shoes Pro', 'running-shoes-pro', 'Sepatu lari ringan dengan desain sporty dan nyaman.', 999000.00, 29, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=800&q=80', '2026-10-02 08:22:06', '2026-10-02 08:42:38'),
(10, 5, 16, 'Hiking Backpack', 'hiking-backpack', 'Tas hiking dengan kapasitas besar untuk aktivitas outdoor.', 649000.00, 17, 'https://images.unsplash.com/photo-1551632811-561732d1e306?auto=format&fit=crop&w=800&q=80', '2026-10-02 08:22:06', '2026-10-02 08:47:29');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `comment` text NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
('uWbGQ88CXcM4AAhXcl3HWuAHaS8w0qQQEYGdxWdf', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:157.0) Gecko/20100101 Firefox/157.0', 'eyJfdG9rZW4iOiJJc20zVGpVb2RNcFdqRThiVHkzN0lTdVQ3ejF5REx3M2o2QldBMDZ2IiwidXJsIjp7ImludGVuZGVkIjoiaHR0cDpcL1wvbG9jYWxob3N0OjgwMDBcL2NoZWNrb3V0In0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cL2xvY2FsaG9zdDo4MDAwXC9vcmRlcnMiLCJyb3V0ZSI6Im9yZGVycy5pbmRleCJ9LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MiwicGFzc3dvcmRfaGFzaF93ZWIiOiIxZDdjMTlhOWRiYmZmNmM3MWYwNzBiODkxM2ZlODZlZWQ0ZTMyOWFkNWE5NGI4MjNiNDk4OTMxYjgwMmZkMTc2In0=', 1790956909);

-- --------------------------------------------------------

--
-- Table structure for table `sub_categories`
--

CREATE TABLE `sub_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sub_categories`
--

INSERT INTO `sub_categories` (`id`, `category_id`, `name`, `slug`, `image`, `description`, `created_at`, `updated_at`) VALUES
(1, 1, 'Smartphone', 'smartphone', 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=800&q=80', 'Berbagai jenis smartphone dan perangkat mobile.', '2026-10-02 08:22:04', '2026-10-02 08:22:04'),
(2, 1, 'Laptop', 'laptop', 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=800&q=80', '', '2026-10-02 08:22:05', '2026-10-02 08:22:05'),
(3, 1, 'Audio', 'audio', '', 'Headphone, headset, speaker dan perangkat audio.', '2026-10-02 08:22:05', '2026-10-02 08:22:05'),
(4, 1, 'Aksesoris HP', 'aksesoris-hp', 'https://images.unsplash.com/photo-1601524909162-ae8725290836?auto=format&fit=crop&w=800&q=80', 'Aksesoris smartphone seperti kabel dan charger.', '2026-10-02 08:22:05', '2026-10-02 08:22:05'),
(5, 2, 'Sepatu', 'sepatu', '', 'Berbagai jenis sepatu pria dan wanita.', '2026-10-02 08:22:05', '2026-10-02 08:22:05'),
(6, 2, 'Tas', 'tas', '', 'Tas selempang, backpack dan tas sehari-hari.', '2026-10-02 08:22:05', '2026-10-02 08:22:05'),
(7, 2, 'Jam Tangan', 'jam-tangan', '', 'Jam tangan casual dan formal.', '2026-10-02 08:22:05', '2026-10-02 08:22:05'),
(8, 2, 'Pakaian Pria', 'pakaian-pria', 'https://images.unsplash.com/photo-1617137968427-85924c800a22?auto=format&fit=crop&w=800&q=80', '', '2026-10-02 08:22:05', '2026-10-02 08:22:05'),
(9, 2, 'Pakaian Wanita', 'pakaian-wanita', 'https://images.unsplash.com/photo-1617137968427-85924c800a22?auto=format&fit=crop&w=800&q=80', '', '2026-10-02 08:22:05', '2026-10-02 08:22:05'),
(10, 3, 'Dapur', 'dapur', '', 'Peralatan dan perlengkapan dapur.', '2026-10-02 08:22:05', '2026-10-02 08:22:05'),
(11, 3, 'Peralatan Rumah', 'peralatan-rumah', '', 'Berbagai perlengkapan untuk kebutuhan rumah.', '2026-10-02 08:22:05', '2026-10-02 08:22:05'),
(12, 4, 'Keyboard', 'keyboard', '', 'Keyboard mekanikal dan keyboard komputer.', '2026-10-02 08:22:05', '2026-10-02 08:22:05'),
(13, 4, 'Mouse', 'mouse', '', 'Mouse wireless dan gaming.', '2026-10-02 08:22:05', '2026-10-02 08:22:05'),
(14, 5, 'Fitness', 'fitness', 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=800&q=80', '', '2026-10-02 08:22:05', '2026-10-02 08:22:05'),
(15, 5, 'Running', 'running', 'https://images.unsplash.com/photo-1552674605-db6ffd4facb5?auto=format&fit=crop&w=800&q=80', '', '2026-10-02 08:22:05', '2026-10-02 08:22:05'),
(16, 5, 'Outdoor', 'outdoor', 'https://images.unsplash.com/photo-1551632811-561732d1e306?auto=format&fit=crop&w=800&q=80', '', '2026-10-02 08:22:05', '2026-10-02 08:22:05');

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
  `role` varchar(255) NOT NULL DEFAULT 'user',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `role`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Administrator', 'denmasgie0@gmail.com', '2026-10-02 08:22:04', '$2y$12$x1CERcxT3/4KQ3K7AZHo6eAAbLo98W7NrfxSGySQKe7my4sWi1G3K', 'admin', NULL, '2026-10-02 08:22:04', '2026-10-02 08:22:04'),
(2, 'Test User', 'test@example.com', NULL, '$2y$12$IMPL3aYq9mQakN1u1dyEROvxy8AZ34FAxGFASXcWFYl4iEybY3W/S', 'user', NULL, '2026-10-02 08:23:03', '2026-10-02 08:23:03');

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `carts_user_id_unique` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cart_items_cart_id_product_id_unique` (`cart_id`,`product_id`),
  ADD KEY `cart_items_product_id_foreign` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categories_slug_unique` (`slug`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `orders_order_number_unique` (`order_number`),
  ADD KEY `orders_user_id_foreign` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_items_order_id_foreign` (`order_id`),
  ADD KEY `order_items_product_id_foreign` (`product_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payments_stripe_payment_intent_id_unique` (`stripe_payment_intent_id`),
  ADD UNIQUE KEY `payments_transaction_id_unique` (`transaction_id`),
  ADD KEY `payments_order_id_foreign` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `products_slug_unique` (`slug`),
  ADD KEY `products_category_id_foreign` (`category_id`),
  ADD KEY `products_sub_category_id_foreign` (`sub_category_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reviews_user_product_unique` (`user_id`,`product_id`),
  ADD KEY `reviews_product_id_foreign` (`product_id`),
  ADD KEY `reviews_order_id_foreign` (`order_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `sub_categories`
--
ALTER TABLE `sub_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sub_categories_category_id_slug_unique` (`category_id`,`slug`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_role_index` (`role`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wishlists_user_id_product_id_unique` (`user_id`,`product_id`),
  ADD KEY `wishlists_product_id_foreign` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sub_categories`
--
ALTER TABLE `sub_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_sub_category_id_foreign` FOREIGN KEY (`sub_category_id`) REFERENCES `sub_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sub_categories`
--
ALTER TABLE `sub_categories`
  ADD CONSTRAINT `sub_categories_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `wishlists_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlists_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
