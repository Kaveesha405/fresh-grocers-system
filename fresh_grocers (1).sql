-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 25, 2026 at 08:03 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fresh_grocers`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `AdminID` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`AdminID`, `Username`, `Password`, `Email`, `CreatedAt`) VALUES
(1, 'admin', '$2a$12$rVNj1HRHb/4ELDnI6EOPmOWqOtgtmUXksV3U/crrLN2pO/qrxyKRi', 'admin@freshgrocers.lk', '2026-02-02 13:59:22');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `CartID` int(11) NOT NULL,
  `CustomerID` int(11) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`CartID`, `CustomerID`, `CreatedAt`) VALUES
(1, 1, '2026-02-02 13:59:23');

-- --------------------------------------------------------

--
-- Table structure for table `cartitem`
--

CREATE TABLE `cartitem` (
  `CartItemID` int(11) NOT NULL,
  `Quantity` int(11) NOT NULL,
  `CartID` int(11) DEFAULT NULL,
  `ProductID` int(11) DEFAULT NULL,
  `AddedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `csr`
--

CREATE TABLE `csr` (
  `CSRID` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `FirstName` varchar(50) DEFAULT NULL,
  `LastName` varchar(50) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `csr`
--

INSERT INTO `csr` (`CSRID`, `Username`, `Password`, `FirstName`, `LastName`, `Email`, `CreatedAt`) VALUES
(1, 'csr1', '$2a$12$Bv8bL.DuH6Qj5DouKUbKRugfaQXEXnI0Z7P9oi5IBscUH.rdtmpBK', 'Saman', 'Perera', 'csr@freshgrocers.lk', '2026-02-02 13:59:23');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `CustomerID` int(11) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `PhoneNumber` varchar(15) NOT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `Password` varchar(255) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`CustomerID`, `FirstName`, `LastName`, `PhoneNumber`, `Email`, `Address`, `Password`, `CreatedAt`) VALUES
(1, 'Nimal', 'Silva', '0771234567', 'nimal@gmail.com', '123 Galle Road, Colombo 03', '$2a$12$Mqqc8fGIkh95erDYnv1yXu7wOAKDhd0Zlmxt7mpT2wOJBj93tuyiK', '2026-02-02 13:59:23'),
(2, 'Kamala', 'Fernando', '0772345678', 'kamala@gmail.com', '456 Kandy Road, Colombo 07', '$2a$12$Mqqc8fGIkh95erDYnv1yXu7wOAKDhd0Zlmxt7mpT2wOJBj93tuyiK', '2026-02-02 13:59:23'),
(3, 'Andrew', 'Fernando', '0781267851', 'Andrew@gmail.com', '58,Pahala Kadirana, Thimbirigaskatuwa', '$2a$12$Mqqc8fGIkh95erDYnv1yXu7wOAKDhd0Zlmxt7mpT2wOJBj93tuyiK', '2026-02-24 06:53:10');

-- --------------------------------------------------------

--
-- Table structure for table `deliveryagent`
--

CREATE TABLE `deliveryagent` (
  `DeliveryAgentID` int(11) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `PhoneNumber` varchar(15) NOT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Location` varchar(200) DEFAULT NULL,
  `LocationLat` decimal(10,8) DEFAULT NULL,
  `LocationLng` decimal(11,8) DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT 1,
  `Password` varchar(255) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deliveryagent`
--

INSERT INTO `deliveryagent` (`DeliveryAgentID`, `FirstName`, `LastName`, `PhoneNumber`, `Email`, `Location`, `LocationLat`, `LocationLng`, `IsActive`, `Password`, `CreatedAt`) VALUES
(1, 'Sunil', 'Jayawardena', '0771234567', 'sunil@fresh.lk', 'Kadirana Junction, Kadirana North, Gampaha District, Western Province, 11500, Sri Lanka', 7.22024345, 79.87837219, 1, '$2a$12$3NWz2qgRoAFgSIIA07KkrelggVlzhabROl31RQUi4r5D379aqxnLC', '2026-02-24 06:00:10'),
(2, 'Ravi', 'Wickramasinghe', '0772345678', 'ravi@fresh.lk', 'Union Place, Colombo 07', 6.93040000, 79.85700000, 1, '$2a$12$3NWz2qgRoAFgSIIA07KkrelggVlzhabROl31RQUi4r5D379aqxnLC', '2026-02-24 06:00:10'),
(3, 'Nimal', 'Perera', '0773456789', 'nimal@fresh.lk', 'Galle Road, Dehiwala', 6.84560000, 79.86550000, 1, '$2a$12$3NWz2qgRoAFgSIIA07KkrelggVlzhabROl31RQUi4r5D379aqxnLC', '2026-02-24 06:00:10'),
(4, 'Saman', 'Fernando', '0774567890', 'saman@fresh.lk', 'Mount Lavinia Beach Rd', 6.81670000, 79.86330000, 1, '$2a$12$3NWz2qgRoAFgSIIA07KkrelggVlzhabROl31RQUi4r5D379aqxnLC', '2026-02-24 06:00:10'),
(5, 'Kamal', 'Silva', '0775678901', 'kamal@fresh.lk', 'Main Street, Negombo', 7.20580000, 79.83850000, 1, '$2a$12$3NWz2qgRoAFgSIIA07KkrelggVlzhabROl31RQUi4r5D379aqxnLC', '2026-02-24 06:00:10'),
(6, 'Kaveesha', 'Fernando', '0761295700', 'kaveeshaamiru05@gmail.com', 'Kadirana Junction, Kadirana North, Gampaha District, Western Province, 11500, Sri Lanka', 7.22002098, 79.87846898, 1, '$2a$12$3NWz2qgRoAFgSIIA07KkrelggVlzhabROl31RQUi4r5D379aqxnLC', '2026-02-24 06:37:34');

-- --------------------------------------------------------

--
-- Table structure for table `message`
--

CREATE TABLE `message` (
  `MessageID` int(11) NOT NULL,
  `MessageDate` datetime DEFAULT current_timestamp(),
  `Subject` varchar(200) DEFAULT NULL,
  `Content` text DEFAULT NULL,
  `CustomerID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `message`
--

INSERT INTO `message` (`MessageID`, `MessageDate`, `Subject`, `Content`, `CustomerID`) VALUES
(1, '2026-02-02 19:29:23', 'Delivery inquiry', 'Do you deliver to Kandy?', 1);

-- --------------------------------------------------------

--
-- Table structure for table `order`
--

CREATE TABLE `order` (
  `OrderID` int(11) NOT NULL,
  `OrderDate` datetime DEFAULT current_timestamp(),
  `DeliveryDate` datetime DEFAULT NULL,
  `TotalAmount` decimal(10,2) NOT NULL,
  `OrderStatus` enum('Pending','Confirmed','Delivered','Canceled') DEFAULT 'Pending',
  `PaymentStatus` enum('Paid','Pending','Failed') DEFAULT 'Pending',
  `DeliveryAddress` text DEFAULT NULL,
  `DeliveryLat` decimal(10,8) DEFAULT NULL,
  `DeliveryLng` decimal(11,8) DEFAULT NULL,
  `PlacedByCsr` tinyint(1) DEFAULT 0,
  `CustomerID` int(11) DEFAULT NULL,
  `CustomerName` varchar(100) DEFAULT NULL,
  `CustomerPhone` varchar(15) DEFAULT NULL,
  `DeliveryAgentID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order`
--

INSERT INTO `order` (`OrderID`, `OrderDate`, `DeliveryDate`, `TotalAmount`, `OrderStatus`, `PaymentStatus`, `DeliveryAddress`, `DeliveryLat`, `DeliveryLng`, `PlacedByCsr`, `CustomerID`, `CustomerName`, `CustomerPhone`, `DeliveryAgentID`) VALUES
(1, '2026-02-02 19:29:23', '2026-02-24 02:34:11', 1530.00, 'Delivered', 'Paid', NULL, NULL, NULL, 0, 1, NULL, NULL, 1),
(2, '2026-02-24 02:42:34', NULL, 1110.00, 'Delivered', 'Paid', NULL, NULL, NULL, 0, 1, NULL, NULL, 3),
(3, '2026-02-24 13:09:03', '2026-02-24 13:10:14', 770.00, 'Delivered', 'Paid', 'Kadirana Junction, Kadirana North, Gampaha District', 7.22024345, 79.87837219, 0, 3, 'Andrew Fernando', '0781267851', 2),
(4, '2026-02-24 13:12:05', '2026-02-24 13:12:18', 600.00, 'Delivered', 'Paid', 'Kadirana Junction, Kadirana North, Gampaha District', 7.22024345, 79.87837219, 0, 3, 'Andrew Fernando', '0781267851', 1);

-- --------------------------------------------------------

--
-- Table structure for table `orderitem`
--

CREATE TABLE `orderitem` (
  `OrderItemID` int(11) NOT NULL,
  `Quantity` int(11) NOT NULL,
  `UnitPrice` decimal(10,2) NOT NULL,
  `OrderID` int(11) DEFAULT NULL,
  `ProductID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orderitem`
--

INSERT INTO `orderitem` (`OrderItemID`, `Quantity`, `UnitPrice`, `OrderID`, `ProductID`) VALUES
(1, 1, 850.00, 1, 1),
(2, 2, 180.00, 1, 5),
(3, 1, 320.00, 1, 4),
(4, 1, 180.00, 2, 5),
(5, 1, 750.00, 2, 2),
(6, 1, 180.00, 2, 5),
(7, 1, 650.00, 3, 11),
(8, 1, 120.00, 3, 10),
(9, 1, 120.00, 4, 10),
(10, 1, 480.00, 4, 9);

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `PaymentID` int(11) NOT NULL,
  `PaymentDate` datetime DEFAULT current_timestamp(),
  `Amount` decimal(10,2) NOT NULL,
  `PaymentMethod` enum('Card','CashOnDelivery','MobileWallet') DEFAULT 'CashOnDelivery',
  `PaymentStatus` enum('Paid','Pending','Failed') DEFAULT 'Pending',
  `OrderID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`PaymentID`, `PaymentDate`, `Amount`, `PaymentMethod`, `PaymentStatus`, `OrderID`) VALUES
(1, '2026-02-02 19:29:23', 1530.00, 'Card', 'Paid', 1),
(2, '2026-02-24 02:42:34', 1110.00, 'CashOnDelivery', 'Paid', 2),
(3, '2026-02-24 13:09:03', 770.00, 'CashOnDelivery', 'Paid', 3),
(4, '2026-02-24 13:12:05', 600.00, 'CashOnDelivery', 'Paid', 4);

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `ProductID` int(11) NOT NULL,
  `ProductName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `Price` decimal(10,2) NOT NULL,
  `StockQuantity` int(11) DEFAULT 0,
  `Category` varchar(50) DEFAULT NULL,
  `ImageURL` varchar(255) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`ProductID`, `ProductName`, `Description`, `Price`, `StockQuantity`, `Category`, `ImageURL`, `CreatedAt`) VALUES
(1, 'Red Rice (5kg)', 'Organic red rice from local farms', 850.00, 50, 'Rice & Grains', 'https://th.bing.com/th/id/OIP.5H2DGSE8_jGVbQ1NKxEMKQHaHa?w=213&h=213&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-02 13:59:23'),
(2, 'White Rice (5kg)', 'Premium white rice', 750.00, 99, 'Rice & Grains', 'https://th.bing.com/th/id/OIP._InxeIzNLzx5swwwd3OiiQHaHa?w=208&h=208&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-02 13:59:23'),
(3, 'Coconut Oil (1L)', 'Pure coconut oil', 450.00, 30, 'Cooking Oils', 'https://th.bing.com/th/id/OIP.J_8yY62-txYUB3WfYD2PugHaHa?w=212&h=212&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-02 13:59:23'),
(4, 'Dhal (1kg)', 'Imported red lentils', 320.00, 80, 'Pulses', 'https://th.bing.com/th/id/OIP.nMBXAG3I_jNAM47PPXkhawHaHa?w=183&h=183&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-02 13:59:23'),
(5, 'Potatoes (1kg)', 'Fresh local potatoes', 180.00, 118, 'Vegetables', 'https://th.bing.com/th/id/OIP.GlSbOiNzekje2fGFBZ7jvwHaE8?w=245&h=180&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-02 13:59:23'),
(6, 'Onions (1kg)', 'Big onions', 220.00, 90, 'Vegetables', 'https://th.bing.com/th/id/OIP.kX0Kyayk9fEtiF-qcsIuTgHaHa?w=163&h=180&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-02 13:59:23'),
(7, 'Tomatoes (1kg)', 'Fresh ripe tomatoes', 280.00, 60, 'Vegetables', 'https://th.bing.com/th/id/OIP.WnPRF7HBMOZLq3butpS8tAHaHa?w=189&h=189&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-02 13:59:23'),
(8, 'Milk Powder (400g)', 'Anchor milk powder', 950.00, 40, 'Dairy', 'https://th.bing.com/th/id/OIP.l8FTKiSVpskraztXX6dfswHaHa?w=205&h=206&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-02 13:59:23'),
(9, 'Eggs (10 pcs)', 'Farm fresh eggs', 480.00, 69, 'Dairy', 'https://emiratesbiofarm.com/cdn/shop/files/10-eggs-ebf.jpg?v=1699869896', '2026-02-02 13:59:23'),
(10, 'Bread (Large)', 'Freshly baked bread', 120.00, 148, 'Bakery', 'https://th.bing.com/th/id/OIP.8I3vxZTuCZXHzoDYoFIXnwHaE1?w=295&h=193&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-02 13:59:23'),
(11, 'Tea (500g)', 'Ceylon tea', 650.00, 44, 'Beverages', 'https://www.theaustralianproducts.com/wp-content/uploads/2023/09/200224.jpg', '2026-02-02 13:59:23'),
(12, 'Sugar (1kg)', 'White sugar', 210.00, 99, 'Pantry', 'https://th.bing.com/th/id/OIP.DT2tT_cxd4G7Gpdut3A_0gHaHa?w=185&h=185&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-02 13:59:23'),
(13, 'Carrots (1kg)', 'Freshly harvested Nuwara Eliya carrots.', 350.00, 150, 'Vegetables', 'https://th.bing.com/th/id/OIP.NQoblU6aVDk4w-pjm_mqRgHaGD?w=216&h=180&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(14, 'Leeks (500g)', 'Crisp and fresh leeks straight from the farm.', 220.00, 80, 'Vegetables', 'https://th.bing.com/th/id/OIP.46zRZ3yueyJ0PeBOiVtYoAAAAA?w=194&h=194&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(15, 'Cabbage (1kg)', 'Crunchy green cabbage, perfect for salads and curries.', 280.00, 100, 'Vegetables', 'https://th.bing.com/th/id/OIP.ehTo2TKZNANmiaJkdca4OgHaHa?w=183&h=183&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(16, 'Beans (500g)', 'Tender green beans.', 190.00, 120, 'Vegetables', 'https://th.bing.com/th/id/OIP.vaYYQNvr2ImimSPHcLevNwHaHa?w=174&h=180&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(17, 'Pumpkin (1kg)', 'Sweet local pumpkin.', 150.00, 80, 'Vegetables', 'https://th.bing.com/th/id/OIP.WVyhMfNHh7w1QhlIPLie-QHaHa?w=183&h=183&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(18, 'Big Onions (1kg)', 'Imported large red onions.', 450.00, 200, 'Vegetables', 'https://th.bing.com/th/id/OIP.ribXprN0eULRGQiXEHIg1wHaEN?w=287&h=180&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(19, 'Garlic (500g)', 'Pungent and fresh garlic bulbs.', 380.00, 150, 'Vegetables', 'https://th.bing.com/th/id/OIP.tXA4DrTs-bt6TR5WamrWcQHaHa?w=188&h=188&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(20, 'Green Chilies (250g)', 'Spicy local green chilies.', 150.00, 50, 'Vegetables', 'https://th.bing.com/th/id/OIP.f2InJbLU0ywVwI0vLLB6JAHaFj?w=213&h=180&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(21, 'Cavendish Bananas (1kg)', 'Sweet and perfectly ripe yellow bananas.', 320.00, 100, 'Fruits', 'https://th.bing.com/th?q=Cavendish+Banana+Look+Like&w=120&h=120&c=1&rs=1&qlt=70&o=7&cb=1&dpr=1.3&pid=InlineBlock&rm=3&mkt=en-WW&cc=LK&setlang=en&adlt=strict&t=1&mw=247', '2026-02-24 10:41:54'),
(22, 'Papaya (1kg)', 'Sweet local papaya.', 250.00, 60, 'Fruits', 'https://th.bing.com/th/id/OIP.kPfKFzTj1wvED1cqi409YQHaHa?w=184&h=184&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(23, 'Apples (500g)', 'Crisp imported red apples.', 550.00, 80, 'Fruits', 'https://th.bing.com/th/id/OIP.4tpZTtF8VAFdnxy_78n9dwHaHa?w=186&h=186&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(24, 'Mango (1kg)', 'Karthakolomban sweet mangoes.', 480.00, 40, 'Fruits', 'https://th.bing.com/th/id/OIP.6Roh8hgl2qEkr8R18homIwHaHa?w=180&h=180&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(25, 'Watermelon (1kg)', 'Fresh and juicy red watermelon.', 200.00, 50, 'Fruits', 'https://th.bing.com/th/id/OIP.4MOsD_y7Xsw4XBvPiWiM-QHaHa?w=169&h=180&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(26, 'Chicken Breast (1kg)', 'Fresh, boneless and skinless chicken breast.', 1450.00, 80, 'Meat', 'https://th.bing.com/th/id/OIP.cNplJRKXeUWUyBMeoip7FwHaHa?w=162&h=180&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(27, 'Pork (1kg)', 'Fresh pork cuts.', 1200.00, 60, 'Meat', 'https://th.bing.com/th/id/OIP.UwOV6RvUsL06cOk9pmP0uQHaHa?w=174&h=180&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(28, 'Beef (1kg)', 'Fresh beef cuts.', 1800.00, 40, 'Meat', 'https://th.bing.com/th/id/OIP.rDrkQEtRTWUqzkIlDxuO0wHaHa?w=174&h=180&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(29, 'Fish (1kg)', 'Fresh yellowfin tuna.', 2200.00, 30, 'Meat', 'https://th.bing.com/th/id/OIP.O_NPnZP3R-qMqML1qpFZlQHaHa?w=174&h=180&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(30, 'Prawns (500g)', 'Fresh medium prawns.', 1500.00, 25, 'Meat', 'https://th.bing.com/th/id/OIP.QXbmEdxQlEBrs78UF01G-AAAAA?w=245&h=180&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(31, 'Mutton (1kg)', 'Fresh mutton cuts.', 2500.00, 20, 'Meat', 'https://th.bing.com/th/id/OIP.P2wbMylPRekxyFYxu80PlQHaHa?w=183&h=183&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(32, 'Sausages (500g)', 'Chicken sausages.', 650.00, 100, 'Meat', 'https://th.bing.com/th/id/OIP.IOJMRh-uoyoQ-fAInfrDeQHaHa?w=183&h=183&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(33, 'Butter (200g)', 'Salted butter block.', 750.00, 80, 'Dairy', 'https://th.bing.com/th/id/OIP.7GPxoCDMKNPfuXgd5VHm4QHaHa?w=183&h=183&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(34, 'Cheese (250g)', 'Processed cheddar cheese.', 950.00, 60, 'Dairy', 'https://th.bing.com/th/id/OIP.f4PJgwuIUs0g3-Mzl_CgbQHaHa?w=199&h=199&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(35, 'Yogurt (80g)', 'Vanilla flavored yogurt.', 80.00, 200, 'Dairy', 'https://th.bing.com/th/id/OIP.1ommObmZcKHkp2SkOs3zWQHaHa?w=192&h=192&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(36, 'Fresh Milk (1L)', 'Full cream fresh milk.', 450.00, 100, 'Dairy', 'https://th.bing.com/th/id/OIP.iCjTPDM1jDRvi19c__RzvgHaHa?w=176&h=180&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(37, 'Biscuits (400g)', 'Chocolate cream biscuits.', 250.00, 150, 'Bakery', 'https://th.bing.com/th/id/OIP.bV2LmV1dlS03x2551GNSPwHaHa?w=190&h=190&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(38, 'Buns (200g)', 'Sweet tea buns.', 150.00, 120, 'Bakery', 'https://th.bing.com/th/id/OIP.ZF9O7M64iBPUlmKNkaLdhAHaFu?w=229&h=180&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(39, 'Coffee (200g)', 'Instant coffee powder.', 1200.00, 50, 'Beverages', 'https://th.bing.com/th/id/OIP.L-E50tyv7evJuGFMxs23xwHaHa?w=186&h=186&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(40, 'Fruit Juice (1L)', 'Mixed fruit nectar.', 650.00, 80, 'Beverages', 'https://th.bing.com/th/id/OIP.16FIvfaNa2cLs_EHYt-LMgAAAA?w=186&h=186&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(41, 'Soft Drink (1.5L)', 'Carbonated cola beverage.', 350.00, 150, 'Beverages', 'https://th.bing.com/th/id/OIP.b3iHz2novnp2jtdmVHO_GAAAAA?w=158&h=180&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(42, 'Salt (1kg)', 'Iodized table salt.', 120.00, 200, 'Pantry', 'https://th.bing.com/th/id/OIP.l0AF1DAPv8d_2HL4_aeIjAHaHa?w=175&h=180&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(43, 'Wheat Flour (1kg)', 'Premium wheat flour.', 220.00, 180, 'Pantry', 'https://th.bing.com/th/id/OIP.CT6qP8sk1kHYi2fECXVygAAAAA?w=182&h=182&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(44, 'Soya Meat (90g)', 'Chicken flavored soya meat.', 150.00, 100, 'Pantry', 'https://th.bing.com/th/id/OIP.otUemCPScKoXlv5iSMnuEQHaHG?w=196&h=188&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(45, 'Noodles (400g)', 'Instant egg noodles.', 350.00, 120, 'Pantry', 'https://th.bing.com/th/id/OIP.L9dQlc3wJZKvR_JTRuUl2wHaI7?w=162&h=195&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54'),
(46, 'Canned Fish (425g)', 'Mackerel in tomato sauce.', 650.00, 90, 'Pantry', 'https://th.bing.com/th/id/OIP.JrJ4S86yNp3R0Up8lIHNeAHaHa?w=183&h=183&c=7&r=0&o=7&dpr=1.3&pid=1.7&rm=3', '2026-02-24 10:41:54');

-- --------------------------------------------------------

--
-- Table structure for table `rating`
--

CREATE TABLE `rating` (
  `RatingID` int(11) NOT NULL,
  `RatingScore` int(11) DEFAULT NULL CHECK (`RatingScore` between 1 and 5),
  `FeedbackComment` text DEFAULT NULL,
  `RatingDate` datetime DEFAULT current_timestamp(),
  `CustomerID` int(11) DEFAULT NULL,
  `DeliveryAgentID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rating`
--

INSERT INTO `rating` (`RatingID`, `RatingScore`, `FeedbackComment`, `RatingDate`, `CustomerID`, `DeliveryAgentID`) VALUES
(2, 4, NULL, '2026-02-24 13:11:05', 3, 1),
(3, 4, 'Arrived right on time.', '2026-02-24 13:15:28', 3, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`AdminID`),
  ADD UNIQUE KEY `Username` (`Username`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`CartID`),
  ADD UNIQUE KEY `CustomerID` (`CustomerID`);

--
-- Indexes for table `cartitem`
--
ALTER TABLE `cartitem`
  ADD PRIMARY KEY (`CartItemID`),
  ADD KEY `CartID` (`CartID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- Indexes for table `csr`
--
ALTER TABLE `csr`
  ADD PRIMARY KEY (`CSRID`),
  ADD UNIQUE KEY `Username` (`Username`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`CustomerID`);

--
-- Indexes for table `deliveryagent`
--
ALTER TABLE `deliveryagent`
  ADD PRIMARY KEY (`DeliveryAgentID`);

--
-- Indexes for table `message`
--
ALTER TABLE `message`
  ADD PRIMARY KEY (`MessageID`),
  ADD KEY `CustomerID` (`CustomerID`);

--
-- Indexes for table `order`
--
ALTER TABLE `order`
  ADD PRIMARY KEY (`OrderID`),
  ADD KEY `CustomerID` (`CustomerID`),
  ADD KEY `DeliveryAgentID` (`DeliveryAgentID`);

--
-- Indexes for table `orderitem`
--
ALTER TABLE `orderitem`
  ADD PRIMARY KEY (`OrderItemID`),
  ADD KEY `OrderID` (`OrderID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`PaymentID`),
  ADD UNIQUE KEY `OrderID` (`OrderID`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`ProductID`);

--
-- Indexes for table `rating`
--
ALTER TABLE `rating`
  ADD PRIMARY KEY (`RatingID`),
  ADD KEY `CustomerID` (`CustomerID`),
  ADD KEY `DeliveryAgentID` (`DeliveryAgentID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `AdminID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `CartID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cartitem`
--
ALTER TABLE `cartitem`
  MODIFY `CartItemID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `csr`
--
ALTER TABLE `csr`
  MODIFY `CSRID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `CustomerID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `deliveryagent`
--
ALTER TABLE `deliveryagent`
  MODIFY `DeliveryAgentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `message`
--
ALTER TABLE `message`
  MODIFY `MessageID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `order`
--
ALTER TABLE `order`
  MODIFY `OrderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orderitem`
--
ALTER TABLE `orderitem`
  MODIFY `OrderItemID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `PaymentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `ProductID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `rating`
--
ALTER TABLE `rating`
  MODIFY `RatingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`CustomerID`) REFERENCES `customer` (`CustomerID`) ON DELETE CASCADE;

--
-- Constraints for table `cartitem`
--
ALTER TABLE `cartitem`
  ADD CONSTRAINT `cartitem_ibfk_1` FOREIGN KEY (`CartID`) REFERENCES `cart` (`CartID`) ON DELETE CASCADE,
  ADD CONSTRAINT `cartitem_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`) ON DELETE CASCADE;

--
-- Constraints for table `message`
--
ALTER TABLE `message`
  ADD CONSTRAINT `message_ibfk_1` FOREIGN KEY (`CustomerID`) REFERENCES `customer` (`CustomerID`) ON DELETE CASCADE;

--
-- Constraints for table `order`
--
ALTER TABLE `order`
  ADD CONSTRAINT `order_ibfk_1` FOREIGN KEY (`CustomerID`) REFERENCES `customer` (`CustomerID`) ON DELETE SET NULL,
  ADD CONSTRAINT `order_ibfk_2` FOREIGN KEY (`DeliveryAgentID`) REFERENCES `deliveryagent` (`DeliveryAgentID`) ON DELETE SET NULL;

--
-- Constraints for table `orderitem`
--
ALTER TABLE `orderitem`
  ADD CONSTRAINT `orderitem_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `order` (`OrderID`) ON DELETE CASCADE,
  ADD CONSTRAINT `orderitem_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `product` (`ProductID`) ON DELETE SET NULL;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `order` (`OrderID`) ON DELETE CASCADE;

--
-- Constraints for table `rating`
--
ALTER TABLE `rating`
  ADD CONSTRAINT `rating_ibfk_1` FOREIGN KEY (`CustomerID`) REFERENCES `customer` (`CustomerID`) ON DELETE CASCADE,
  ADD CONSTRAINT `rating_ibfk_2` FOREIGN KEY (`DeliveryAgentID`) REFERENCES `deliveryagent` (`DeliveryAgentID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
