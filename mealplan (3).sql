-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3307
-- Generation Time: Feb 11, 2026 at 01:47 PM
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
-- Database: `mealplan`
--

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `budget_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `mealplan_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `spent` decimal(10,2) DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `currency` varchar(10) DEFAULT 'KES'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budgets`
--

INSERT INTO `budgets` (`budget_id`, `user_id`, `mealplan_id`, `amount`, `spent`, `start_date`, `end_date`, `currency`) VALUES
(1, 7, 4, 5000.00, 0.00, '2026-01-27', '2026-02-03', 'KES'),
(2, 7, NULL, 5000.00, 0.00, '2026-01-27', '2026-02-03', 'KES');

-- --------------------------------------------------------

--
-- Table structure for table `food_items`
--

CREATE TABLE `food_items` (
  `fooditem_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `price_per_unit` decimal(10,2) DEFAULT NULL,
  `calories_per_unit` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `food_items`
--

INSERT INTO `food_items` (`fooditem_id`, `name`, `category`, `unit`, `price_per_unit`, `calories_per_unit`, `created_at`) VALUES
(1, 'Maize Flour', 'Grain', 'kg', 120.00, NULL, '2026-01-27 06:50:49'),
(2, 'Sukuma Wiki', 'Vegetable', 'bunch', 20.00, NULL, '2026-01-27 06:50:49'),
(3, 'Onions', 'Vegetable', 'kg', 80.00, NULL, '2026-01-27 06:50:49'),
(4, 'Tomatoes', 'Vegetable', 'kg', 100.00, NULL, '2026-01-27 06:50:49'),
(5, 'Beans', 'Protein', 'kg', 200.00, NULL, '2026-01-27 06:50:49'),
(6, 'Wheat Flour', 'Grain', 'kg', 150.00, NULL, '2026-01-27 06:50:49'),
(7, 'Cooking Oil', 'Fat', 'litre', 250.00, NULL, '2026-01-27 06:50:49'),
(8, 'Sugar', 'Sweetener', 'kg', 180.00, NULL, '2026-01-27 06:50:49'),
(9, 'Tea Leaves', 'Beverage', '100g', 120.00, NULL, '2026-01-27 06:50:49'),
(10, 'Beef', 'Protein', 'kg', 600.00, NULL, '2026-01-27 06:50:49'),
(11, 'Avocado', 'Fruit', 'piece', 30.00, NULL, '2026-01-27 06:50:49'),
(12, 'Rice', 'Grain', 'kg', 200.00, NULL, '2026-01-27 06:50:49'),
(13, 'Potatoes', 'Vegetable', 'kg', 70.00, NULL, '2026-01-27 06:50:49'),
(14, 'Green Bananas', 'Fruit', 'kg', 100.00, NULL, '2026-01-27 09:56:07'),
(15, 'Beef (stew cut)', 'Protein', 'kg', 650.00, NULL, '2026-01-27 09:56:07'),
(16, 'Chicken (whole)', 'Protein', 'kg', 550.00, NULL, '2026-01-27 09:56:07'),
(17, 'Potatoes (Irish)', 'Vegetable', 'kg', 90.00, NULL, '2026-01-27 09:56:07'),
(18, 'Pumpkin Leaves', 'Vegetable', 'bunch', 15.00, NULL, '2026-01-27 09:56:07'),
(19, 'Coconut Milk', 'Dairy', '500ml', 120.00, NULL, '2026-01-27 09:56:07'),
(20, 'Carrots', 'Vegetable', 'kg', 80.00, NULL, '2026-01-27 09:56:07'),
(21, 'Cabbage', 'Vegetable', 'head', 40.00, NULL, '2026-01-27 09:56:07'),
(22, 'Spinach', 'Vegetable', 'bunch', 25.00, NULL, '2026-01-27 09:56:07'),
(23, 'Bananas', 'Fruit', 'dozen', 100.00, NULL, '2026-01-27 09:56:07'),
(24, 'Oranges', 'Fruit', 'kg', 120.00, NULL, '2026-01-27 09:56:07'),
(25, 'Pineapple', 'Fruit', 'piece', 80.00, NULL, '2026-01-27 09:56:07'),
(26, 'Green Bananas', 'Fruit', 'kg', 100.00, NULL, '2026-01-27 09:58:30'),
(27, 'Beef (stew cut)', 'Protein', 'kg', 650.00, NULL, '2026-01-27 09:58:30'),
(28, 'Chicken (whole)', 'Protein', 'kg', 550.00, NULL, '2026-01-27 09:58:30'),
(29, 'Potatoes (Irish)', 'Vegetable', 'kg', 90.00, NULL, '2026-01-27 09:58:30'),
(30, 'Pumpkin Leaves', 'Vegetable', 'bunch', 15.00, NULL, '2026-01-27 09:58:30'),
(31, 'Coconut Milk', 'Dairy', '500ml', 120.00, NULL, '2026-01-27 09:58:30'),
(32, 'Carrots', 'Vegetable', 'kg', 80.00, NULL, '2026-01-27 09:58:30'),
(33, 'Cabbage', 'Vegetable', 'head', 40.00, NULL, '2026-01-27 09:58:30'),
(34, 'Spinach', 'Vegetable', 'bunch', 25.00, NULL, '2026-01-27 09:58:30'),
(35, 'Bananas', 'Fruit', 'dozen', 100.00, NULL, '2026-01-27 09:58:30'),
(36, 'Oranges', 'Fruit', 'kg', 120.00, NULL, '2026-01-27 09:58:30'),
(37, 'Pineapple', 'Fruit', 'piece', 80.00, NULL, '2026-01-27 09:58:30');

-- --------------------------------------------------------

--
-- Table structure for table `mealplan_nutrition`
--

CREATE TABLE `mealplan_nutrition` (
  `nutrition_id` int(11) NOT NULL,
  `mealplan_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_calories` int(11) DEFAULT 0,
  `total_protein` decimal(10,2) DEFAULT 0.00,
  `total_carbs` decimal(10,2) DEFAULT 0.00,
  `total_fats` decimal(10,2) DEFAULT 0.00,
  `total_cost` decimal(10,2) DEFAULT 0.00,
  `calculation_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `meals`
--

CREATE TABLE `meals` (
  `meal_id` int(11) NOT NULL,
  `mealplan_id` int(11) DEFAULT NULL,
  `recipe_id` int(11) DEFAULT NULL,
  `meal_name` varchar(100) DEFAULT NULL,
  `meal_type` varchar(50) DEFAULT NULL,
  `scheduled_date` date DEFAULT NULL,
  `scheduled_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meals`
--

INSERT INTO `meals` (`meal_id`, `mealplan_id`, `recipe_id`, `meal_name`, `meal_type`, `scheduled_date`, `scheduled_time`) VALUES
(1, 4, 3, 'Mandazi & Chai', 'Breakfast', '2026-01-27', NULL),
(2, 4, 4, 'Pilau & Kachumbari', 'Lunch', '2026-01-27', NULL),
(3, 4, 1, 'Ugali & Sukuma Wiki', 'Dinner', '2026-01-27', NULL),
(4, 1, 3, 'Mandazi & Chai', 'Breakfast', '2026-01-27', NULL),
(5, 1, 4, 'Pilau & Kachumbari', 'Lunch', '2026-01-27', NULL),
(6, 1, 1, 'Ugali & Sukuma Wiki', 'Dinner', '2026-01-27', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `meal_plans`
--

CREATE TABLE `meal_plans` (
  `mealplan_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `total_calories` int(11) DEFAULT 0,
  `total_protein` decimal(10,2) DEFAULT 0.00,
  `total_carbs` decimal(10,2) DEFAULT 0.00,
  `total_fats` decimal(10,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meal_plans`
--

INSERT INTO `meal_plans` (`mealplan_id`, `user_id`, `name`, `start_date`, `end_date`, `total_cost`, `total_calories`, `total_protein`, `total_carbs`, `total_fats`, `created_at`) VALUES
(1, 7, 'Weekly Family Plan', '2026-01-27', '2026-02-03', 3500.00, 0, 0.00, 0.00, 0.00, '2026-01-27 12:56:07'),
(2, 7, 'Budget Weekly Plan', '2026-01-27', '2026-02-03', 2800.00, 0, 0.00, 0.00, 0.00, '2026-01-27 12:56:07'),
(3, 10, 'John\'s Fitness Plan', '2026-01-27', '2026-02-03', 4200.00, 0, 0.00, 0.00, 0.00, '2026-01-27 12:56:07'),
(4, 7, 'Weekly Family Plan', '2026-01-27', '2026-02-03', 3500.00, 0, 0.00, 0.00, 0.00, '2026-01-27 12:58:30');

-- --------------------------------------------------------

--
-- Table structure for table `nutrition_goals`
--

CREATE TABLE `nutrition_goals` (
  `goal_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `daily_calories` int(11) DEFAULT 2000,
  `daily_protein` decimal(10,2) DEFAULT 50.00,
  `daily_carbs` decimal(10,2) DEFAULT 250.00,
  `daily_fats` decimal(10,2) DEFAULT 65.00,
  `goal_type` varchar(50) DEFAULT 'Maintenance',
  `active_level` varchar(50) DEFAULT 'Moderate',
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `height_cm` int(11) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gender` varchar(10) DEFAULT 'Other',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nutrition_goals`
--

INSERT INTO `nutrition_goals` (`goal_id`, `user_id`, `daily_calories`, `daily_protein`, `daily_carbs`, `daily_fats`, `goal_type`, `active_level`, `weight_kg`, `height_cm`, `age`, `gender`, `created_at`, `updated_at`) VALUES
(1, 22, 2000, 50.00, 250.00, 65.00, 'Maintenance', 'Moderate', NULL, NULL, NULL, 'Other', '2026-02-11 09:19:07', '2026-02-11 09:19:07'),
(2, 5, 2000, 50.00, 250.00, 65.00, 'Maintenance', 'Moderate', NULL, NULL, NULL, 'Other', '2026-02-11 09:19:07', '2026-02-11 09:19:07'),
(3, 4, 2000, 50.00, 250.00, 65.00, 'Maintenance', 'Moderate', NULL, NULL, NULL, 'Other', '2026-02-11 09:19:07', '2026-02-11 09:19:07'),
(4, 3, 2000, 50.00, 250.00, 65.00, 'Maintenance', 'Moderate', NULL, NULL, NULL, 'Other', '2026-02-11 09:19:07', '2026-02-11 09:19:07'),
(5, 19, 2000, 50.00, 250.00, 65.00, 'Maintenance', 'Moderate', NULL, NULL, NULL, 'Other', '2026-02-11 09:19:07', '2026-02-11 09:19:07'),
(6, 2, 2000, 50.00, 250.00, 65.00, 'Maintenance', 'Moderate', NULL, NULL, NULL, 'Other', '2026-02-11 09:19:07', '2026-02-11 09:19:07'),
(7, 1, 2000, 50.00, 250.00, 65.00, 'Maintenance', 'Moderate', NULL, NULL, NULL, 'Other', '2026-02-11 09:19:07', '2026-02-11 09:19:07'),
(8, 6, 2000, 50.00, 250.00, 65.00, 'Maintenance', 'Moderate', NULL, NULL, NULL, 'Other', '2026-02-11 09:19:07', '2026-02-11 09:19:07'),
(9, 20, 2000, 50.00, 250.00, 65.00, 'Maintenance', 'Moderate', NULL, NULL, NULL, 'Other', '2026-02-11 09:19:07', '2026-02-11 09:19:07'),
(10, 10, 2000, 50.00, 250.00, 65.00, 'Maintenance', 'Moderate', NULL, NULL, NULL, 'Other', '2026-02-11 09:19:07', '2026-02-11 09:19:07'),
(11, 15, 2000, 50.00, 250.00, 65.00, 'Maintenance', 'Moderate', NULL, NULL, NULL, 'Other', '2026-02-11 09:19:07', '2026-02-11 09:19:07'),
(12, 16, 2000, 50.00, 250.00, 65.00, 'Maintenance', 'Moderate', NULL, NULL, NULL, 'Other', '2026-02-11 09:19:07', '2026-02-11 09:19:07'),
(13, 8, 2000, 50.00, 250.00, 65.00, 'Maintenance', 'Moderate', NULL, NULL, NULL, 'Other', '2026-02-11 09:19:07', '2026-02-11 09:19:07'),
(14, 11, 2000, 50.00, 250.00, 65.00, 'Maintenance', 'Moderate', NULL, NULL, NULL, 'Other', '2026-02-11 09:19:07', '2026-02-11 09:19:07'),
(15, 9, 2000, 50.00, 250.00, 65.00, 'Maintenance', 'Moderate', NULL, NULL, NULL, 'Other', '2026-02-11 09:19:07', '2026-02-11 09:19:07'),
(16, 21, 2000, 50.00, 250.00, 65.00, 'Maintenance', 'Moderate', NULL, NULL, NULL, 'Other', '2026-02-11 09:19:07', '2026-02-11 09:19:07'),
(17, 18, 2000, 50.00, 250.00, 65.00, 'Maintenance', 'Moderate', NULL, NULL, NULL, 'Other', '2026-02-11 09:19:07', '2026-02-11 09:19:07'),
(18, 7, 2000, 50.00, 250.00, 65.00, 'Maintenance', 'Moderate', NULL, NULL, NULL, 'Other', '2026-02-11 09:19:07', '2026-02-11 09:19:07'),
(19, 17, 2000, 50.00, 250.00, 65.00, 'Maintenance', 'Moderate', NULL, NULL, NULL, 'Other', '2026-02-11 09:19:07', '2026-02-11 09:19:07');

-- --------------------------------------------------------

--
-- Table structure for table `pantry`
--

CREATE TABLE `pantry` (
  `pantry_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `added_date` datetime DEFAULT current_timestamp(),
  `last_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pantry`
--

INSERT INTO `pantry` (`pantry_id`, `user_id`, `item_name`, `category`, `quantity`, `unit`, `expiry_date`, `added_date`, `last_updated`) VALUES
(6, 15, 'Rice', 'Other', 5.00, 'kg', NULL, '2026-01-28 18:13:10', '2026-01-28 18:13:10'),
(7, 15, 'Flour', 'Grain', 6.00, 'kg', NULL, '2026-01-28 18:13:38', '2026-01-28 18:13:38'),
(8, 15, 'beans', 'Grain', 2.00, 'kg', NULL, '2026-01-28 18:14:04', '2026-01-28 18:14:04'),
(9, 15, 'oil', 'Other', 5.00, 'litre', '2027-01-28', '2026-01-28 18:14:51', '2026-01-28 18:14:51'),
(10, 16, 'green grams', 'Grain', 4.00, 'kg', NULL, '2026-01-29 09:10:00', '2026-01-29 09:10:00'),
(11, 16, 'Rice', 'Other', 5.00, 'kg', NULL, '2026-01-29 09:10:43', '2026-01-29 09:10:43'),
(12, 16, 'tomatoes', 'Vegetable', 5.00, 'g', NULL, '2026-01-29 09:11:11', '2026-01-29 09:11:11'),
(13, 16, 'onions', 'Vegetable', 4.00, 'g', NULL, '2026-01-29 09:12:02', '2026-01-29 09:12:02'),
(14, 16, 'cooking oil', 'Other', 5.00, 'litre', '2026-11-29', '2026-01-29 09:12:43', '2026-01-29 09:12:43'),
(16, 4, 'bananas', 'Fruit', 3.00, 'g', NULL, '2026-01-29 14:27:35', '2026-01-29 14:27:35'),
(17, 4, 'Rice', 'Other', 5.00, 'kg', NULL, '2026-01-29 14:27:59', '2026-01-29 14:27:59'),
(18, 4, 'cabbage', 'Vegetable', 3.00, 'bunch', NULL, '2026-01-29 14:28:26', '2026-01-29 14:28:26'),
(19, 4, 'tomatoes', 'Vegetable', 7.00, 'piece', NULL, '2026-01-29 14:29:08', '2026-01-29 14:29:08'),
(20, 4, 'meat', 'Protein', 3.00, 'kg', NULL, '2026-01-29 14:29:30', '2026-01-29 14:29:30'),
(21, 4, 'milk', 'Dairy', 3.00, 'packet', '2026-02-28', '2026-01-29 14:29:56', '2026-01-29 14:29:56'),
(22, 3, 'Sukuma Wiki', 'Vegetable', 5.00, 'bunch', NULL, '2026-02-03 09:35:59', '2026-02-03 09:35:59'),
(23, 3, 'cabbage', 'Vegetable', 4.00, 'bunch', NULL, '2026-02-03 09:36:32', '2026-02-03 09:36:32'),
(24, 3, 'tomatoes', 'Vegetable', 10.00, 'piece', NULL, '2026-02-03 09:36:57', '2026-02-03 09:36:57'),
(25, 3, 'onions', 'Vegetable', 10.00, 'piece', NULL, '2026-02-03 09:37:25', '2026-02-03 09:37:25'),
(26, 3, 'bananas', 'Fruit', 6.00, 'bunch', NULL, '2026-02-03 09:37:44', '2026-02-03 09:37:44'),
(27, 3, 'olive oil', 'Other', 10.00, 'litre', NULL, '2026-02-03 09:38:07', '2026-02-03 09:38:07'),
(28, 3, 'apples', 'Fruit', 2.00, 'piece', NULL, '2026-02-03 09:38:37', '2026-02-03 09:38:37'),
(29, 3, 'green grams', 'Protein', 5.00, 'kg', NULL, '2026-02-03 09:38:59', '2026-02-03 09:38:59'),
(30, 3, 'beans', 'Protein', 15.00, 'kg', NULL, '2026-02-03 09:39:18', '2026-02-03 09:39:18'),
(31, 3, 'Rice', 'Other', 7.00, 'kg', NULL, '2026-02-03 09:39:45', '2026-02-03 09:39:45'),
(32, 3, 'tumeric', 'Spice', 5.00, 'g', NULL, '2026-02-03 09:40:02', '2026-02-03 09:40:02'),
(33, 17, 'Maize flour', 'Grain', 5.00, 'kg', '2026-03-09', '2026-02-09 16:30:35', '2026-02-09 16:30:35'),
(34, 17, 'tomatoes', 'Vegetable', 6.00, 'piece', NULL, '2026-02-10 08:04:18', '2026-02-10 08:04:18'),
(35, 17, 'eggs', 'Protein', 5.00, 'piece', NULL, '2026-02-10 08:04:47', '2026-02-10 08:04:47'),
(36, 17, 'onions', 'Vegetable', 4.00, 'piece', NULL, '2026-02-10 08:06:11', '2026-02-10 08:06:11'),
(37, 17, 'Sukuma Wiki', 'Vegetable', 6.00, 'bunch', NULL, '2026-02-10 08:06:46', '2026-02-10 08:06:46'),
(38, 18, 'Sukuma Wiki', 'Vegetable', 5.00, 'bunch', NULL, '2026-02-10 10:56:30', '2026-02-10 10:56:30'),
(39, 18, 'Maize flour', 'Grain', 5.00, 'kg', '2026-03-10', '2026-02-10 10:56:50', '2026-02-10 10:57:39'),
(40, 18, 'Rice', 'Grain', 7.00, 'kg', NULL, '2026-02-10 10:57:09', '2026-02-10 10:57:09'),
(41, 18, 'lentils', 'Protein', 4.00, 'kg', NULL, '2026-02-10 10:57:28', '2026-02-10 10:57:28'),
(42, 19, 'Maize flour', 'Grain', 5.00, 'kg', '2026-03-12', '2026-02-10 16:43:04', '2026-02-10 16:43:04'),
(43, 19, 'Sukuma Wiki', 'Vegetable', 5.00, 'bunch', NULL, '2026-02-10 16:43:25', '2026-02-10 16:43:25'),
(44, 20, 'tomatoes', 'Vegetable', 5.00, 'piece', NULL, '2026-02-10 19:34:51', '2026-02-10 19:34:51'),
(45, 20, 'Rice', 'Grain', 5.00, 'kg', NULL, '2026-02-10 19:35:11', '2026-02-10 19:35:11'),
(46, 20, 'beans', 'Protein', 6.00, 'kg', NULL, '2026-02-10 19:35:28', '2026-02-10 19:35:28'),
(47, 20, 'onions', 'Vegetable', 4.00, 'piece', NULL, '2026-02-10 19:35:59', '2026-02-10 19:35:59'),
(48, 20, 'milk', 'Dairy', 5.00, 'packet', NULL, '2026-02-10 19:36:24', '2026-02-10 19:36:24'),
(49, 3, 'lentils', 'Protein', 4.00, 'kg', NULL, '2026-02-11 12:41:06', '2026-02-11 12:41:06'),
(50, 3, 'peas', 'Protein', 2.00, 'kg', NULL, '2026-02-11 12:41:40', '2026-02-11 12:41:40');

-- --------------------------------------------------------

--
-- Table structure for table `recipes`
--

CREATE TABLE `recipes` (
  `recipe_id` int(11) NOT NULL,
  `recipe_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `instructions` text NOT NULL,
  `prep_time` varchar(50) DEFAULT NULL,
  `serving_size` int(11) DEFAULT NULL,
  `meal_type` varchar(50) DEFAULT NULL,
  `estimated_cost` decimal(10,2) DEFAULT NULL,
  `calories` int(11) DEFAULT NULL,
  `protein` decimal(5,2) DEFAULT NULL,
  `carbs` decimal(5,2) DEFAULT NULL,
  `fats` decimal(5,2) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recipes`
--

INSERT INTO `recipes` (`recipe_id`, `recipe_name`, `description`, `instructions`, `prep_time`, `serving_size`, `meal_type`, `estimated_cost`, `calories`, `protein`, `carbs`, `fats`, `image`, `created_at`) VALUES
(1, 'Ugali & Sukuma Wiki', 'Traditional Kenyan staple meal', '1. Prepare ugali\n2. Cook sukuma wiki\n3. Serve hot', NULL, NULL, 'Dinner', 150.00, 420, 12.50, 75.20, 8.30, NULL, '2026-01-27 06:50:49'),
(2, 'Chapati & Beans', 'Soft chapati with stewed beans', '1. Make chapati dough\n2. Prepare bean stew\n3. Cook and serve', NULL, NULL, 'Dinner', 180.00, 580, 22.10, 85.60, 18.40, NULL, '2026-01-27 06:50:49'),
(3, 'Mandazi & Chai', 'Sweet fried bread with tea', '1. Prepare mandazi dough\n2. Fry until golden\n3. Brew tea', NULL, NULL, 'Breakfast', 80.00, 320, 8.50, 45.30, 12.70, NULL, '2026-01-27 06:50:49'),
(4, 'Pilau & Kachumbari', 'Spiced rice with fresh salad', '1. Cook pilau rice\n2. Prepare kachumbari\n3. Mix and serve', NULL, NULL, 'Lunch', 220.00, 680, 20.30, 120.50, 15.80, NULL, '2026-01-27 06:50:49'),
(5, 'Githeri & Avocado', 'Boiled maize and beans with avocado', '1. Cook githeri\n2. Slice avocado\n3. Serve together', NULL, NULL, 'Lunch', 180.00, 520, 18.70, 85.40, 20.20, NULL, '2026-01-27 06:50:49'),
(6, 'Nyama Choma', 'Grilled meat Kenyan style', '1. Marinate meat\n2. Grill to perfection\n3. Serve with kachumbari', NULL, NULL, 'Dinner', 450.00, 750, 45.60, 5.20, 55.30, NULL, '2026-01-27 06:50:49'),
(7, 'Ugali & Beef Stew', 'Traditional Kenyan staple with tender beef', '1. Prepare ugali\n2. Cook beef stew with tomatoes and onions\n3. Serve hot', NULL, NULL, 'Dinner', 250.00, 650, 35.20, 68.50, 28.30, NULL, '2026-01-27 09:56:07'),
(8, 'Matoke', 'Steamed green bananas with vegetables', '1. Peel and cook green bananas\n2. Add tomatoes and onions\n3. Steam until soft', NULL, NULL, 'Lunch', 180.00, 420, 8.50, 85.30, 12.70, NULL, '2026-01-27 09:56:07'),
(9, 'Chai & Mahamri', 'Swahili sweet tea with coconut doughnuts', '1. Brew tea with spices\n2. Fry mahamri until golden\n3. Serve together', NULL, NULL, 'Breakfast', 120.00, 380, 7.20, 52.40, 15.80, NULL, '2026-01-27 09:56:07'),
(10, 'Mukimo', 'Mashed potatoes with greens and maize', '1. Boil potatoes and maize\n2. Add pumpkin leaves\n3. Mash together', NULL, NULL, 'Lunch', 150.00, 520, 12.30, 95.60, 18.20, NULL, '2026-01-27 09:56:07'),
(11, 'Roasted Chicken & Chips', 'Crispy chicken with homemade chips', '1. Marinate chicken\n2. Roast until golden\n3. Serve with chips', NULL, NULL, 'Dinner', 320.00, 720, 42.50, 65.80, 35.40, NULL, '2026-01-27 09:56:07'),
(12, 'Fruit Salad', 'Fresh seasonal fruits', '1. Chop assorted fruits\n2. Mix with lemon juice\n3. Chill and serve', NULL, NULL, 'Snack', 80.00, 180, 2.10, 42.30, 0.80, NULL, '2026-01-27 09:56:07'),
(13, 'Ugali & Beef Stew', 'Traditional Kenyan staple with tender beef', '1. Prepare ugali\n2. Cook beef stew with tomatoes and onions\n3. Serve hot', NULL, NULL, 'Dinner', 250.00, 650, 35.20, 68.50, 28.30, NULL, '2026-01-27 09:58:30'),
(14, 'Matoke', 'Steamed green bananas with vegetables', '1. Peel and cook green bananas\n2. Add tomatoes and onions\n3. Steam until soft', NULL, NULL, 'Lunch', 180.00, 420, 8.50, 85.30, 12.70, NULL, '2026-01-27 09:58:30'),
(15, 'Chai & Mahamri', 'Swahili sweet tea with coconut doughnuts', '1. Brew tea with spices\r\n2. Fry mahamri until golden\r\n3. Serve together', '', 4, 'Breakfast', 120.00, 380, 7.20, 52.40, 15.80, NULL, '2026-01-27 09:58:30'),
(16, 'Mukimo', 'Mashed potatoes with greens and maize', '1. Boil potatoes and maize\n2. Add pumpkin leaves\n3. Mash together', NULL, NULL, 'Lunch', 150.00, 520, 12.30, 95.60, 18.20, NULL, '2026-01-27 09:58:30'),
(17, 'Roasted Chicken & Chips', 'Crispy chicken with homemade chips', '1. Marinate chicken\n2. Roast until golden\n3. Serve with chips', NULL, NULL, 'Dinner', 320.00, 720, 42.50, 65.80, 35.40, NULL, '2026-01-27 09:58:30'),
(18, 'Fruit Salad', 'Fresh seasonal fruits', '1. Chop assorted fruits\n2. Mix with lemon juice\n3. Chill and serve', NULL, NULL, 'Snack', 80.00, 180, 2.10, 42.30, 0.80, NULL, '2026-01-27 09:58:30'),
(19, 'Bean Stew & Rice', 'Protein-rich bean stew with rice', '1. Cook beans until soft\n2. Prepare tomato-based stew\n3. Serve with rice', NULL, NULL, 'Lunch', 180.00, 520, 22.50, 85.30, 12.80, NULL, '2026-02-10 07:40:57'),
(20, 'Vegetable Soup', 'Hearty vegetable soup', '1. Sauté onions and garlic\n2. Add chopped vegetables\n3. Simmer until tender', NULL, NULL, 'Dinner', 120.00, 280, 8.50, 45.20, 9.30, NULL, '2026-02-10 07:40:57'),
(21, 'Fruit Smoothie', 'Fresh fruit smoothie', '1. Blend fruits with water\n2. Add ice if desired\n3. Serve chilled', NULL, NULL, 'Breakfast', 80.00, 180, 3.20, 42.10, 0.50, NULL, '2026-02-10 07:40:57'),
(22, 'Grilled Tilapia', 'Fresh grilled fish', '1. Clean and season fish\n2. Grill until cooked\n3. Serve with lemon', NULL, NULL, 'Dinner', 280.00, 320, 38.50, 5.20, 16.30, NULL, '2026-02-10 07:40:57'),
(23, 'Egg Scramble', 'Scrambled eggs with vegetables', '1. Beat eggs\n2. Sauté vegetables\n3. Cook eggs with vegetables', NULL, NULL, 'Breakfast', 90.00, 250, 18.20, 8.50, 16.80, NULL, '2026-02-10 07:40:57'),
(24, 'Avocado Salad', 'Fresh avocado salad', '1. Slice avocado\n2. Mix with greens\n3. Add lemon dressing', NULL, NULL, 'Lunch', 150.00, 320, 8.50, 12.30, 28.40, NULL, '2026-02-10 07:40:57'),
(25, 'Grilled Chicken Salad', 'Grilled chicken with mixed greens', '1. Grill chicken breast\n2. Prepare salad\n3. Combine and serve', NULL, NULL, 'Dinner', 220.00, 380, 42.50, 8.20, 22.10, NULL, '2026-02-10 07:40:57'),
(26, 'Chapati & Lentils', 'Soft chapati with lentil stew', '1. Make chapati dough\n2. Cook lentil stew\n3. Serve together', NULL, NULL, 'Dinner', 160.00, 580, 24.30, 85.60, 18.90, NULL, '2026-02-10 07:40:57'),
(27, 'Kenyan Tea', 'Traditional Kenyan tea', '1. Boil water with tea leaves\n2. Add milk and sugar\n3. Strain and serve', NULL, NULL, 'Breakfast', 40.00, 120, 3.50, 18.20, 4.30, NULL, '2026-02-10 07:40:57'),
(28, 'Roasted Nuts', 'Mixed roasted nuts', '1. Toss nuts with spices\n2. Roast until golden\n3. Cool and serve', NULL, NULL, 'Snack', 100.00, 320, 12.50, 18.30, 25.80, NULL, '2026-02-10 07:40:57'),
(29, 'Vegetable Samosa', 'Crispy vegetable samosas', '1. Prepare vegetable filling\n2. Wrap in pastry\n3. Fry until golden', NULL, NULL, 'Snack', 60.00, 180, 5.20, 25.40, 8.90, NULL, '2026-02-10 07:40:57');

-- --------------------------------------------------------

--
-- Table structure for table `recipe_ingredients`
--

CREATE TABLE `recipe_ingredients` (
  `id` int(11) NOT NULL,
  `recipe_id` int(11) DEFAULT NULL,
  `fooditem_id` int(11) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recipe_ingredients`
--

INSERT INTO `recipe_ingredients` (`id`, `recipe_id`, `fooditem_id`, `quantity`, `unit`) VALUES
(1, 1, 1, 2.00, 'kg'),
(2, 1, 2, 3.00, 'bunch'),
(3, 1, 3, 0.50, 'kg'),
(4, 1, 4, 1.00, 'kg'),
(5, 1, 7, 0.25, 'litre'),
(6, 2, 6, 3.00, 'kg'),
(7, 2, 5, 1.00, 'kg'),
(8, 2, 4, 0.50, 'kg'),
(9, 2, 3, 0.25, 'kg'),
(10, 2, 7, 0.30, 'litre'),
(11, 3, 6, 2.00, 'kg'),
(12, 3, 8, 0.50, 'kg'),
(13, 3, 7, 1.00, 'litre'),
(14, 3, 9, 0.10, '100g'),
(15, 4, 12, 2.00, 'kg'),
(16, 4, 3, 0.50, 'kg'),
(17, 4, 4, 1.00, 'kg'),
(18, 4, 7, 0.20, 'litre'),
(19, 4, 13, 1.00, 'kg'),
(20, 5, 5, 1.00, 'kg'),
(21, 5, 1, 1.00, 'kg'),
(22, 5, 4, 0.50, 'kg'),
(23, 5, 3, 0.25, 'kg'),
(24, 5, 11, 3.00, 'piece'),
(25, 6, 10, 2.00, 'kg'),
(26, 6, 4, 1.00, 'kg'),
(27, 6, 3, 0.50, 'kg'),
(28, 7, 1, 2.00, 'kg'),
(29, 7, 15, 1.00, 'kg'),
(30, 7, 4, 1.00, 'kg'),
(31, 7, 3, 0.50, 'kg'),
(32, 7, 7, 0.20, 'litre'),
(33, 8, 26, 3.00, 'kg'),
(34, 8, 4, 0.50, 'kg'),
(35, 8, 3, 0.25, 'kg'),
(36, 8, 19, 1.00, '500ml'),
(37, 8, 7, 0.10, 'litre'),
(38, 9, 8, 0.30, 'kg'),
(39, 9, 9, 0.15, '100g'),
(40, 9, 6, 1.50, 'kg'),
(41, 9, 7, 0.50, 'litre'),
(42, 10, 17, 2.00, 'kg'),
(43, 10, 1, 0.50, 'kg'),
(44, 10, 18, 2.00, 'bunch'),
(45, 10, 3, 0.25, 'kg'),
(46, 10, 7, 0.10, 'litre'),
(47, 11, 16, 1.50, 'kg'),
(48, 11, 13, 2.00, 'kg'),
(49, 11, 7, 0.50, 'litre'),
(50, 11, 3, 0.25, 'kg'),
(51, 12, 23, 6.00, 'dozen'),
(52, 12, 24, 1.00, 'kg'),
(53, 12, 25, 1.00, 'piece'),
(54, 12, 11, 2.00, 'piece'),
(55, 19, 5, 1.00, 'kg'),
(56, 19, 12, 2.00, 'kg'),
(57, 19, 4, 0.50, 'kg'),
(58, 19, 3, 0.25, 'kg'),
(59, 19, 7, 0.15, 'litre'),
(60, 20, 21, 1.00, 'head'),
(61, 20, 20, 0.50, 'kg'),
(62, 20, 22, 2.00, 'bunch'),
(63, 20, 13, 1.00, 'kg'),
(64, 20, 3, 0.25, 'kg');

-- --------------------------------------------------------

--
-- Table structure for table `shopping_lists`
--

CREATE TABLE `shopping_lists` (
  `shoppinglist_id` int(11) NOT NULL,
  `mealplan_id` int(11) DEFAULT NULL,
  `fooditem_id` int(11) DEFAULT NULL,
  `quantity_needed` decimal(10,2) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shopping_lists`
--

INSERT INTO `shopping_lists` (`shoppinglist_id`, `mealplan_id`, `fooditem_id`, `quantity_needed`, `unit`, `status`) VALUES
(1, 4, 1, 2.00, 'kg', 'Pending'),
(2, 4, 2, 3.00, 'bunch', 'Pending'),
(3, 4, 3, 0.50, 'kg', 'Purchased'),
(4, 4, 4, 1.00, 'kg', 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` varchar(50) DEFAULT 'Individual',
  `phone_number` varchar(15) DEFAULT NULL,
  `date_registered` date NOT NULL,
  `is_admin` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `password`, `user_type`, `phone_number`, `date_registered`, `is_admin`) VALUES
(1, 'Daville Bukachi', 'Daville@gmail.com', '$2y$10$n0I8InSmREmsCNfe7.3ySu8LLAyIbTyjzSItOPF1yMTJTCVs2XvfO', 'Individual', '0745792056', '2026-01-21', 0),
(2, 'Daville Bukachi', 'Daville2@gmail.com', '$2y$10$O3MwDY8lCCVpvEzYe.Jjcu62umiVGPxT8pb3HUPiMPIPMaD194yqO', 'Individual', '0745792056', '2026-01-21', 0),
(3, 'Selinah Bukachi', 'bukachiselinah@gmail.com', '$2y$10$9inaec0EWBIBlIwsBMxvbevJKBq5iDRdBLcCbw88ueDRWYjUOBBL6', 'Nutritionist', '0745792056', '2026-01-21', 0),
(4, 'Dorcas  Bukachi', 'bukachidorcas@gmail.com', '$2y$10$r5qPyMCyYBSTIAIl54TM3uEObplKCdtyhJyjmxjM2UJr2jUZzP/XG', 'Family', '', '2026-01-21', 0),
(5, 'Aline Bukachi', 'alinebukachi@gmail.com', '$2y$10$bgIN/LwbFKxujCrFE/Ob/.P6U3SSi9JMC4kXVlM/DEfvXUBXAkUPm', 'Individual', '0725429073', '2026-01-22', 0),
(6, 'Florence Nzau', 'Fnzau@gmail.com', '$2y$10$iywnCtosDqNhKJMfSidtouvo0RiArV2KWfW2gasuGEaGYyCFbMKYm', 'Nutritionist', '0712097643', '2026-01-25', 0),
(7, 'Test User', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Individual', '0712345678', '2026-01-27', 0),
(8, 'Martin Bukachi', 'martin@gmail.com', '$2y$10$AgQPpzWlkEXX1nI2H/AhBuZ2eZfEBn9UrQD1WsPR83eDY71dS41Ee', 'Individual', '0720788534', '2026-01-27', 0),
(9, 'Mercy Mwanzia', 'mercy@gmail.com', '$2y$10$gqcLXHq90t5fPGpQaPKbJumYQMLSmDpRGA7J5Y6iz41OzcQKK9vMS', 'Organization', '0743 2168 12', '2026-01-27', 0),
(10, 'John Kamau', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Individual', '0712345678', '2026-01-27', 0),
(11, 'Mary Wanjiku', 'mary@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Family', '0723456789', '2026-01-27', 0),
(15, 'John Kariuki', 'kariuki@gmail.com', '$2y$10$ruhl/dJmO5R.yfe5ZL1gz.gAbDFTKw8jC9Vrw50DKcRYOFiNXFHbu', 'Family', '0789 6543 21', '2026-01-28', 0),
(16, 'Katty Joseph', 'katty@gmail.com', '$2y$10$XHMnA4HzHGUwj/U13YVbbeyLtTAx3WQ1hL19VONh.oozEbj/Jux4y', 'Nutritionist', '0765 4321 70', '2026-01-29', 0),
(17, 'Zakari Mark', 'zakari@gmail.com', '$2y$10$OcPwNY3O7MqwUkjnjnlubOqtGLVK32rVAjYyTTQ0eizM0M1m1KeTq', 'Individual', '0765 4903 20', '2026-02-09', 0),
(18, 'Success Musenya', 'success@gmail.com', '$2y$10$zehIAee.KajPZobO5jDn6e87J4LD4b1aUGwxF99IgapXQIfsSBUp6', 'Individual', '0790 4798 41', '2026-02-10', 0),
(19, 'Charles Nzamutamu', 'charles@gmail.com', '$2y$10$NKKDy.VtG7QFfityW.aG1uafBMyF7kAzAob9tH6Ug.hOnIaVjwq.a', 'Family', '0743 0965 43', '2026-02-10', 0),
(20, 'Grace Nelson', 'grace@gmail.com', '$2y$10$.68zjsJUuVres8bj02AIyetpJTp9wUgZ.M8oySpgL7G57sXQjt6bW', 'Individual', '0712 3456 78', '2026-02-10', 0),
(21, 'Selinah Sabuti', 'sabuti@gmail.com', '$2y$10$RFESCdD0v/X1lvpAiDMY2eyPwciu73V13SqOClYdjZJ1EnSH1jZ7m', 'Nutritionist', '0112 3456 79', '2026-02-11', 0),
(22, 'System Admin', 'admin@nutriplan.ke', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', '0700123456', '2026-02-11', 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_activity`
--

CREATE TABLE `user_activity` (
  `activity_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `activity_details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_activity`
--

INSERT INTO `user_activity` (`activity_id`, `user_id`, `activity_type`, `activity_details`, `created_at`) VALUES
(1, 3, 'plan_generated', 'Generated pantry-based meal plan', '2026-02-09 12:18:14'),
(2, 17, 'plan_generated', 'Generated pantry-based meal plan', '2026-02-09 13:31:46'),
(3, 17, 'plan_generated', 'Generated pantry-based meal plan', '2026-02-09 13:33:16'),
(4, 17, 'plan_generated', 'Generated pantry-based meal plan', '2026-02-09 13:35:56'),
(5, 17, 'plan_generated', 'Generated pantry-based meal plan', '2026-02-10 05:09:03'),
(6, 17, 'plan_generated', 'Generated pantry-based meal plan', '2026-02-10 05:27:19'),
(7, 17, 'plan_generated', 'Generated pantry-based meal plan', '2026-02-10 06:10:02'),
(8, 17, 'plan_generated', 'Generated pantry-based meal plan', '2026-02-10 06:28:59'),
(9, 3, 'plan_generated', 'Generated pantry-based meal plan', '2026-02-10 07:27:58'),
(10, 4, 'plan_generated', 'Generated pantry-based meal plan', '2026-02-10 07:50:33'),
(11, 4, 'plan_generated', 'Generated pantry-based meal plan', '2026-02-10 07:53:47'),
(12, 3, 'plan_generated', 'Generated pantry-based meal plan', '2026-02-10 08:06:06'),
(13, 3, 'plan_generated', 'Generated pantry-based meal plan', '2026-02-10 14:53:36'),
(14, 3, 'plan_generated', 'Generated pantry-based meal plan', '2026-02-10 15:02:39'),
(15, 4, 'plan_generated', 'Generated pantry-based meal plan', '2026-02-10 15:05:16'),
(16, 4, 'plan_generated', 'Generated pantry-based meal plan', '2026-02-10 15:08:25'),
(17, 4, 'plan_generated', 'Generated pantry-based meal plan', '2026-02-10 15:09:38'),
(18, 20, 'preferences_updated', 'Auto-saved dietary preferences: High-Protein diet, 3 meals/day', '2026-02-10 16:49:32'),
(19, 20, 'plan_generated', 'Generated pantry-based meal plan', '2026-02-10 16:49:41'),
(20, 20, 'preferences_updated', 'Auto-saved dietary preferences: Vegan diet, 3 meals/day', '2026-02-10 16:50:27'),
(21, 20, 'preferences_updated', 'Auto-saved dietary preferences: Modern-Healthy diet, 3 meals/day', '2026-02-10 16:50:36'),
(22, 20, 'preferences_updated', 'Auto-saved dietary preferences: Vegan diet, 3 meals/day', '2026-02-10 16:50:42'),
(23, 20, 'preferences_updated', 'Auto-saved dietary preferences: Vegan diet, 3 meals/day', '2026-02-10 16:50:45'),
(24, 20, 'preferences_updated', 'Auto-saved dietary preferences: Vegan diet, 3 meals/day', '2026-02-10 16:50:48'),
(25, 20, 'preferences_updated', 'Auto-saved dietary preferences: Vegan diet, 3 meals/day', '2026-02-10 16:50:50'),
(26, 20, 'preferences_updated', 'Auto-saved dietary preferences: Vegan diet, 3 meals/day', '2026-02-10 16:50:53'),
(27, 20, 'preferences_updated', 'Auto-saved dietary preferences: Vegan diet, 3 meals/day', '2026-02-10 16:50:57'),
(28, 20, 'preferences_updated', 'Auto-saved dietary preferences: Vegan diet, 3 meals/day', '2026-02-10 16:51:01'),
(29, 20, 'preferences_updated', 'Auto-saved dietary preferences: Vegan diet, 3 meals/day', '2026-02-10 16:51:04'),
(30, 20, 'preferences_updated', 'Auto-saved dietary preferences: Vegan diet, 3 meals/day', '2026-02-10 16:51:07'),
(31, 20, 'preferences_updated', 'Auto-saved dietary preferences: Vegan diet, 3 meals/day', '2026-02-10 16:51:10'),
(32, 20, 'preferences_updated', 'Auto-saved dietary preferences: Vegan diet, 3 meals/day', '2026-02-10 16:51:16'),
(33, 20, 'plan_generated', 'Generated pantry-based meal plan', '2026-02-10 16:51:21'),
(34, 20, 'preferences_updated', 'Auto-saved dietary preferences: Modern-Healthy diet, 3 meals/day', '2026-02-10 16:53:54'),
(35, 20, 'preferences_updated', 'Auto-saved dietary preferences: Modern-Healthy diet, 2 meals/day', '2026-02-10 16:54:02'),
(36, 20, 'preferences_updated', 'Auto-saved dietary preferences: Modern-Healthy diet, 2 meals/day', '2026-02-10 16:54:09'),
(37, 20, 'preferences_updated', 'Auto-saved dietary preferences: Modern-Healthy diet, 2 meals/day', '2026-02-10 16:54:14'),
(38, 20, 'preferences_updated', 'Auto-saved dietary preferences: Modern-Healthy diet, 2 meals/day', '2026-02-10 16:54:21'),
(39, 20, 'preferences_updated', 'Auto-saved dietary preferences: Modern-Healthy diet, 2 meals/day', '2026-02-10 16:54:23'),
(40, 20, 'preferences_updated', 'Auto-saved dietary preferences: Modern-Healthy diet, 2 meals/day', '2026-02-10 16:54:26'),
(41, 20, 'preferences_updated', 'Auto-saved dietary preferences: Modern-Healthy diet, 2 meals/day', '2026-02-10 16:54:28'),
(42, 20, 'preferences_updated', 'Auto-saved dietary preferences: Modern-Healthy diet, 2 meals/day', '2026-02-10 16:54:34'),
(43, 20, 'preferences_updated', 'Auto-saved dietary preferences: Modern-Healthy diet, 2 meals/day', '2026-02-10 16:54:36'),
(44, 20, 'preferences_updated', 'Auto-saved dietary preferences: Modern-Healthy diet, 2 meals/day', '2026-02-10 16:54:37'),
(45, 20, 'preferences_updated', 'Auto-saved dietary preferences: Modern-Healthy diet, 2 meals/day', '2026-02-10 16:54:39'),
(46, 20, 'preferences_updated', 'Auto-saved dietary preferences: Modern-Healthy diet, 2 meals/day', '2026-02-10 16:54:40'),
(47, 20, 'preferences_updated', 'Auto-saved dietary preferences: Modern-Healthy diet, 2 meals/day', '2026-02-10 16:54:41'),
(48, 20, 'plan_generated', 'Generated pantry-based meal plan', '2026-02-10 16:54:48'),
(49, 20, 'plan_generated', 'Generated pantry-based meal plan', '2026-02-10 17:10:43'),
(50, 20, 'plan_generated', 'Generated pantry-based meal plan', '2026-02-10 17:14:03'),
(51, 20, 'plan_generated', 'Generated pantry-based meal plan', '2026-02-10 17:14:14'),
(52, 20, 'plan_generated', 'Generated pantry-based meal plan', '2026-02-10 17:19:45'),
(53, 3, 'preferences_updated', 'Auto-saved dietary preferences: Modern-Healthy diet, 4 meals/day', '2026-02-11 07:49:22'),
(54, 3, 'preferences_updated', 'Auto-saved dietary preferences: Vegan diet, 4 meals/day', '2026-02-11 07:49:30'),
(55, 3, 'preferences_updated', 'Auto-saved dietary preferences: Modern-Healthy diet, 4 meals/day', '2026-02-11 07:49:34'),
(56, 3, 'preferences_updated', 'Auto-saved dietary preferences: Modern-Healthy diet, 4 meals/day', '2026-02-11 07:49:38'),
(57, 3, 'preferences_updated', 'Auto-saved dietary preferences: Modern-Healthy diet, 4 meals/day', '2026-02-11 07:49:48'),
(58, 3, 'preferences_updated', 'Auto-saved dietary preferences: Modern-Healthy diet, 4 meals/day', '2026-02-11 07:49:53'),
(59, 3, 'preferences_updated', 'Auto-saved dietary preferences: Modern-Healthy diet, 4 meals/day', '2026-02-11 07:50:00'),
(60, 3, 'plan_generated', 'Generated pantry-based meal plan', '2026-02-11 07:50:03'),
(61, 3, 'preferences_updated', 'Auto-saved dietary preferences: Balanced diet, 4 meals/day', '2026-02-11 07:51:02'),
(62, 3, 'plan_generated', 'Generated pantry-based meal plan', '2026-02-11 07:51:09'),
(63, 21, 'signup', 'New user registered as Nutritionist', '2026-02-11 08:21:23'),
(64, 3, 'login', 'Nutritionist logged in', '2026-02-11 08:43:55'),
(65, 22, 'login', 'Admin logged in', '2026-02-11 09:09:28'),
(66, 3, 'login', 'User logged in', '2026-02-11 09:37:40'),
(67, 3, 'preferences_updated', 'Auto-saved dietary preferences: High-Protein diet, 4 meals/day', '2026-02-11 09:41:51'),
(68, 3, 'preferences_updated', 'Auto-saved dietary preferences: High-Protein diet, 2 meals/day', '2026-02-11 09:41:57'),
(69, 3, 'preferences_updated', 'Auto-saved dietary preferences: High-Protein diet, 2 meals/day', '2026-02-11 09:42:04'),
(70, 3, 'preferences_updated', 'Auto-saved dietary preferences: High-Protein diet, 2 meals/day', '2026-02-11 09:42:07'),
(71, 3, 'preferences_updated', 'Auto-saved dietary preferences: High-Protein diet, 2 meals/day', '2026-02-11 09:42:13'),
(72, 3, 'preferences_updated', 'Auto-saved dietary preferences: High-Protein diet, 2 meals/day', '2026-02-11 09:42:18'),
(73, 3, 'preferences_updated', 'Auto-saved dietary preferences: High-Protein diet, 2 meals/day', '2026-02-11 09:42:20'),
(74, 3, 'preferences_updated', 'Auto-saved dietary preferences: High-Protein diet, 2 meals/day', '2026-02-11 09:42:22'),
(75, 3, 'preferences_updated', 'Auto-saved dietary preferences: High-Protein diet, 2 meals/day', '2026-02-11 09:42:23'),
(76, 3, 'preferences_updated', 'Auto-saved dietary preferences: High-Protein diet, 2 meals/day', '2026-02-11 09:42:25'),
(77, 3, 'plan_generated', 'Generated pantry-based meal plan', '2026-02-11 09:42:57'),
(78, 22, 'login', 'Admin logged in', '2026-02-11 09:46:38');

-- --------------------------------------------------------

--
-- Table structure for table `user_budgets`
--

CREATE TABLE `user_budgets` (
  `budget_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `budget_type` varchar(20) DEFAULT 'Weekly',
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'KES',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `current_spending` decimal(10,2) DEFAULT 0.00,
  `status` varchar(20) DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_budgets`
--

INSERT INTO `user_budgets` (`budget_id`, `user_id`, `budget_type`, `amount`, `currency`, `start_date`, `end_date`, `current_spending`, `status`, `created_at`, `updated_at`) VALUES
(1, 3, 'Weekly', 5000.00, 'KES', '2026-01-28', '2026-02-03', 0.00, 'Inactive', '2026-01-28 17:18:40', '2026-01-28 17:21:17'),
(2, 3, 'Weekly', 3000.00, 'KES', '2026-01-28', '2026-02-03', 0.00, 'Inactive', '2026-01-28 17:21:17', '2026-01-28 17:51:04'),
(3, 3, 'Weekly', 2500.00, 'KES', '2026-01-28', '2026-02-03', 0.00, 'Inactive', '2026-01-28 17:51:04', '2026-02-03 09:41:53'),
(4, 15, 'Monthly', 12000.00, 'KES', '2026-01-28', '2026-02-27', 0.00, 'Active', '2026-01-28 18:16:41', '2026-01-28 18:16:41'),
(5, 16, 'Weekly', 2000.00, 'KES', '2026-01-29', '2026-02-04', 0.00, 'Active', '2026-01-29 09:14:42', '2026-01-29 09:14:42'),
(6, 4, 'Weekly', 2500.00, 'KES', '2026-01-29', '2026-02-04', 0.00, 'Inactive', '2026-01-29 14:31:39', '2026-02-03 09:32:14'),
(7, 4, 'Monthly', 9000.00, 'KES', '2026-02-03', '2026-03-05', 0.00, 'Inactive', '2026-02-03 09:32:14', '2026-02-10 10:53:29'),
(8, 3, 'Weekly', 6000.00, 'KES', '2026-02-03', '2026-02-09', 0.00, 'Inactive', '2026-02-03 09:41:53', '2026-02-11 12:42:47'),
(9, 17, 'Weekly', 2000.00, 'KES', '2026-02-09', '2026-02-15', 0.00, 'Inactive', '2026-02-09 16:31:33', '2026-02-10 08:08:50'),
(10, 17, 'Weekly', 2500.00, 'KES', '2026-02-10', '2026-02-16', 0.00, 'Active', '2026-02-10 08:08:50', '2026-02-10 08:08:50'),
(11, 4, 'Weekly', 3000.00, 'KES', '2026-02-10', '2026-02-16', 0.00, 'Inactive', '2026-02-10 10:53:29', '2026-02-10 18:08:16'),
(12, 18, 'Weekly', 3000.00, 'KES', '2026-02-10', '2026-02-16', 0.00, 'Active', '2026-02-10 10:58:59', '2026-02-10 10:58:59'),
(13, 19, 'Weekly', 1500.00, 'KES', '2026-02-10', '2026-02-16', 0.00, 'Active', '2026-02-10 16:44:37', '2026-02-10 16:44:37'),
(14, 4, 'Weekly', 2500.00, 'KES', '2026-02-10', '2026-02-16', 0.00, 'Active', '2026-02-10 18:08:16', '2026-02-10 18:08:16'),
(15, 20, 'Weekly', 4000.00, 'KES', '2026-02-10', '2026-02-16', 0.00, 'Active', '2026-02-10 19:37:14', '2026-02-10 19:37:14'),
(16, 3, 'Weekly', 4500.00, 'KES', '2026-02-11', '2026-02-17', 0.00, 'Active', '2026-02-11 12:42:47', '2026-02-11 12:42:47');

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `pref_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `diet_type` varchar(50) DEFAULT 'Balanced',
  `cuisine_pref` varchar(100) DEFAULT 'Kenyan',
  `spicy_level` varchar(20) DEFAULT 'Medium',
  `cooking_time` varchar(50) DEFAULT '30-45 minutes',
  `meals_per_day` int(11) DEFAULT 3,
  `avoid_pork` tinyint(1) DEFAULT 0,
  `avoid_beef` tinyint(1) DEFAULT 0,
  `avoid_fish` tinyint(1) DEFAULT 0,
  `avoid_dairy` tinyint(1) DEFAULT 0,
  `gluten_free` tinyint(1) DEFAULT 0,
  `vegetarian` tinyint(1) DEFAULT 0,
  `vegan` tinyint(1) DEFAULT 0,
  `low_carb` tinyint(1) DEFAULT 0,
  `low_fat` tinyint(1) DEFAULT 0,
  `high_protein` tinyint(1) DEFAULT 0,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `avoid_gluten` tinyint(1) DEFAULT 0,
  `avoid_nuts` tinyint(1) DEFAULT 0,
  `avoid_eggs` tinyint(1) DEFAULT 0,
  `low_sodium` tinyint(1) DEFAULT 0,
  `sugar_free` tinyint(1) DEFAULT 0,
  `diabetic` tinyint(1) DEFAULT 0,
  `hypertension` tinyint(1) DEFAULT 0,
  `cholesterol` tinyint(1) DEFAULT 0,
  `pregnancy` tinyint(1) DEFAULT 0,
  `lactose` tinyint(1) DEFAULT 0,
  `pref_breakfast` tinyint(1) DEFAULT 1,
  `pref_lunch` tinyint(1) DEFAULT 1,
  `pref_dinner` tinyint(1) DEFAULT 1,
  `pref_snacks` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_preferences`
--

INSERT INTO `user_preferences` (`pref_id`, `user_id`, `diet_type`, `cuisine_pref`, `spicy_level`, `cooking_time`, `meals_per_day`, `avoid_pork`, `avoid_beef`, `avoid_fish`, `avoid_dairy`, `gluten_free`, `vegetarian`, `vegan`, `low_carb`, `low_fat`, `high_protein`, `updated_at`, `avoid_gluten`, `avoid_nuts`, `avoid_eggs`, `low_sodium`, `sugar_free`, `diabetic`, `hypertension`, `cholesterol`, `pregnancy`, `lactose`, `pref_breakfast`, `pref_lunch`, `pref_dinner`, `pref_snacks`) VALUES
(1, 3, 'High-Protein', 'All', 'Mild', '45-60 minutes', 2, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-02-11 12:42:25', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1),
(2, 15, 'Balanced', 'All', 'Medium', 'No Preference', 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2026-01-28 18:15:48', 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 0),
(3, 16, 'Weight-Management', 'Kenyan', 'Medium', 'Quick (<30 min)', 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2026-01-29 09:14:01', 0, 1, 0, 0, 0, 0, 0, 1, 0, 0, 1, 0, 1, 0),
(4, 4, 'High-Protein', 'Kenyan', 'Medium', 'No Preference', 4, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, '2026-02-03 09:31:20', 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 1, 1, 1, 1),
(5, 17, 'High-Protein', 'Kenyan', 'Medium', '30-45 minutes', 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, '2026-02-10 08:08:23', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 0),
(6, 20, 'Modern-Healthy', 'Kenyan', 'Medium', '30-45 minutes', 2, 1, 1, 1, 1, 0, 1, 1, 1, 1, 1, '2026-02-10 19:54:41', 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`budget_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `mealplan_id` (`mealplan_id`);

--
-- Indexes for table `food_items`
--
ALTER TABLE `food_items`
  ADD PRIMARY KEY (`fooditem_id`);

--
-- Indexes for table `mealplan_nutrition`
--
ALTER TABLE `mealplan_nutrition`
  ADD PRIMARY KEY (`nutrition_id`),
  ADD KEY `mealplan_id` (`mealplan_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `meals`
--
ALTER TABLE `meals`
  ADD PRIMARY KEY (`meal_id`),
  ADD KEY `mealplan_id` (`mealplan_id`),
  ADD KEY `recipe_id` (`recipe_id`);

--
-- Indexes for table `meal_plans`
--
ALTER TABLE `meal_plans`
  ADD PRIMARY KEY (`mealplan_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `nutrition_goals`
--
ALTER TABLE `nutrition_goals`
  ADD PRIMARY KEY (`goal_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `pantry`
--
ALTER TABLE `pantry`
  ADD PRIMARY KEY (`pantry_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `recipes`
--
ALTER TABLE `recipes`
  ADD PRIMARY KEY (`recipe_id`);

--
-- Indexes for table `recipe_ingredients`
--
ALTER TABLE `recipe_ingredients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipe_id` (`recipe_id`),
  ADD KEY `fooditem_id` (`fooditem_id`);

--
-- Indexes for table `shopping_lists`
--
ALTER TABLE `shopping_lists`
  ADD PRIMARY KEY (`shoppinglist_id`),
  ADD KEY `mealplan_id` (`mealplan_id`),
  ADD KEY `fooditem_id` (`fooditem_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_activity`
--
ALTER TABLE `user_activity`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_budgets`
--
ALTER TABLE `user_budgets`
  ADD PRIMARY KEY (`budget_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`pref_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `budgets`
--
ALTER TABLE `budgets`
  MODIFY `budget_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `food_items`
--
ALTER TABLE `food_items`
  MODIFY `fooditem_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `mealplan_nutrition`
--
ALTER TABLE `mealplan_nutrition`
  MODIFY `nutrition_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `meals`
--
ALTER TABLE `meals`
  MODIFY `meal_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `meal_plans`
--
ALTER TABLE `meal_plans`
  MODIFY `mealplan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `nutrition_goals`
--
ALTER TABLE `nutrition_goals`
  MODIFY `goal_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `pantry`
--
ALTER TABLE `pantry`
  MODIFY `pantry_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `recipes`
--
ALTER TABLE `recipes`
  MODIFY `recipe_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `recipe_ingredients`
--
ALTER TABLE `recipe_ingredients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `shopping_lists`
--
ALTER TABLE `shopping_lists`
  MODIFY `shoppinglist_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `user_activity`
--
ALTER TABLE `user_activity`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `user_budgets`
--
ALTER TABLE `user_budgets`
  MODIFY `budget_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `pref_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `budgets`
--
ALTER TABLE `budgets`
  ADD CONSTRAINT `budgets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `budgets_ibfk_2` FOREIGN KEY (`mealplan_id`) REFERENCES `meal_plans` (`mealplan_id`);

--
-- Constraints for table `mealplan_nutrition`
--
ALTER TABLE `mealplan_nutrition`
  ADD CONSTRAINT `mealplan_nutrition_ibfk_1` FOREIGN KEY (`mealplan_id`) REFERENCES `meal_plans` (`mealplan_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mealplan_nutrition_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `meals`
--
ALTER TABLE `meals`
  ADD CONSTRAINT `meals_ibfk_1` FOREIGN KEY (`mealplan_id`) REFERENCES `meal_plans` (`mealplan_id`),
  ADD CONSTRAINT `meals_ibfk_2` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`recipe_id`);

--
-- Constraints for table `meal_plans`
--
ALTER TABLE `meal_plans`
  ADD CONSTRAINT `meal_plans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `nutrition_goals`
--
ALTER TABLE `nutrition_goals`
  ADD CONSTRAINT `nutrition_goals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `pantry`
--
ALTER TABLE `pantry`
  ADD CONSTRAINT `pantry_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `recipe_ingredients`
--
ALTER TABLE `recipe_ingredients`
  ADD CONSTRAINT `recipe_ingredients_ibfk_1` FOREIGN KEY (`recipe_id`) REFERENCES `recipes` (`recipe_id`),
  ADD CONSTRAINT `recipe_ingredients_ibfk_2` FOREIGN KEY (`fooditem_id`) REFERENCES `food_items` (`fooditem_id`);

--
-- Constraints for table `shopping_lists`
--
ALTER TABLE `shopping_lists`
  ADD CONSTRAINT `shopping_lists_ibfk_1` FOREIGN KEY (`mealplan_id`) REFERENCES `meal_plans` (`mealplan_id`),
  ADD CONSTRAINT `shopping_lists_ibfk_2` FOREIGN KEY (`fooditem_id`) REFERENCES `food_items` (`fooditem_id`);

--
-- Constraints for table `user_activity`
--
ALTER TABLE `user_activity`
  ADD CONSTRAINT `user_activity_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_budgets`
--
ALTER TABLE `user_budgets`
  ADD CONSTRAINT `user_budgets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
