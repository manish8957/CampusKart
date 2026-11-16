-- CampusKart Database Schema
-- Second-Hand Book and Gadget Store for College Students

CREATE DATABASE IF NOT EXISTS `campuskart_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `campuskart_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- Drop all tables cleanly if re-installing
DROP TABLE IF EXISTS `canteen_order_items`;
DROP TABLE IF EXISTS `canteen_orders`;
DROP TABLE IF EXISTS `canteen_cart`;
DROP TABLE IF EXISTS `canteen_items`;

DROP TABLE IF EXISTS `sneaker_order_items`;
DROP TABLE IF EXISTS `sneaker_orders`;
DROP TABLE IF EXISTS `sneaker_cart`;
DROP TABLE IF EXISTS `sneakers`;

DROP TABLE IF EXISTS `wishlist`;
DROP TABLE IF EXISTS `contact_messages`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `cart`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `college` VARCHAR(150) DEFAULT 'Campus Main Block',
  `hostel` VARCHAR(100) DEFAULT '',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Categories Table
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(50) DEFAULT 'bi-book',
  `description` VARCHAR(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Products Table
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `mrp` DECIMAL(10,2) DEFAULT NULL,
  `condition` VARCHAR(50) NOT NULL DEFAULT 'Good',
  `image` VARCHAR(255) NOT NULL,
  `category_id` INT NOT NULL,
  `seller_id` INT NOT NULL,
  `is_featured` TINYINT(1) DEFAULT 0,
  `status` ENUM('available', 'sold') DEFAULT 'available',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Cart Table
CREATE TABLE `cart` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `qty` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `user_product` (`user_id`, `product_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Orders Table
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_code` VARCHAR(30) NOT NULL,
  `user_id` INT NOT NULL,
  `total` DECIMAL(10,2) NOT NULL,
  `address` TEXT NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `payment_method` VARCHAR(50) DEFAULT 'Cash on Campus Pickup',
  `date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('Pending Pickup', 'Confirmed', 'Completed', 'Cancelled') DEFAULT 'Pending Pickup',
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Order Items Table
CREATE TABLE `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NULL,
  `title` VARCHAR(200) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `qty` INT NOT NULL DEFAULT 1,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Contact Inquiries Table
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL,
  `subject` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Wishlist Table (Books & Gadgets)
CREATE TABLE IF NOT EXISTS `wishlist` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `user_product_wishlist` (`user_id`, `product_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Sneaker Wishlist Table
CREATE TABLE IF NOT EXISTS `sneaker_wishlist` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `sneaker_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `user_sneaker_wishlist` (`user_id`, `sneaker_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`sneaker_id`) REFERENCES `sneakers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- SEED DATA
-- --------------------------------------------------------

-- Insert Sample Users (All passwords are: password123)
-- Hash: $2y$10$EbwMWR6z659YeAfzpQiXfOO3HBirqm5bZw9ugS7Jtm3RTz7PSVmBa
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `college`, `hostel`) VALUES
(1, 'Aarav Sharma', 'aarav.sharma@campus.edu', '$2y$10$EbwMWR6z659YeAfzpQiXfOO3HBirqm5bZw9ugS7Jtm3RTz7PSVmBa', '9876543210', 'Apex Engineering College', 'Aryabhata Hostel, Room 304'),
(2, 'Sneha Patel', 'sneha.patel@campus.edu', '$2y$10$EbwMWR6z659YeAfzpQiXfOO3HBirqm5bZw9ugS7Jtm3RTz7PSVmBa', '9823456789', 'National Institute of Tech', 'Gargi Girls Hostel, Room 112'),
(3, 'Rohan Verma', 'rohan.verma@campus.edu', '$2y$10$EbwMWR6z659YeAfzpQiXfOO3HBirqm5bZw9ugS7Jtm3RTz7PSVmBa', '9711223344', 'Government Medical College', 'Charaka Hostel, Room 205');

-- Insert Categories
INSERT INTO `categories` (`id`, `name`, `icon`, `description`) VALUES
(1, 'Engineering & Tech', 'bi-cpu', 'Core textbooks for CSE, ECE, Mech, Civil & Electrical engineering'),
(2, 'Calculators & Electronics', 'bi-calculator', 'Scientific calculators, Arduino, sensors & electronic kits'),
(3, 'Laptops & Gadgets', 'bi-laptop', 'Second-hand keyboards, mice, headphones, adapters & student tablets'),
(4, 'Medical & Science', 'bi-heart-pulse', 'Anatomy, physiology, biology & clinical medicine reference books'),
(5, 'Exam Prep (GATE / CAT)', 'bi-mortarboard', 'Previous years solved papers, coaching notes & formula booklets'),
(6, 'Semester Bundles', 'bi-collection', 'Curated all-in-one book bundles grouped by branch & semester'),
(7, 'Drawing & Lab Tools', 'bi-rulers', 'Mini drafters, lab coats, drawing sheet holders & dissection boxes');

-- Insert Sample Products
INSERT INTO `products` (`id`, `title`, `description`, `price`, `mrp`, `condition`, `image`, `category_id`, `seller_id`, `is_featured`, `status`) VALUES
(1, 'Higher Engineering Mathematics - B.S. Grewal (44th Ed)', 'Essential math guide for 1st & 2nd year engineering students. No torn pages, few light pencil notes in calculus section. Very well preserved.', 380.00, 850.00, 'Good', 'assets/images/bs_grewal.jpg', 1, 1, 1, 'available'),
(2, 'Casio fx-991EX ClassWiz Scientific Calculator', 'Solar + battery natural textbook display scientific calculator with 552 functions. Original case included, clear screen, working smoothly.', 750.00, 1420.00, 'Like New', 'assets/images/casio_991ex.jpg', 2, 2, 1, 'available'),
(3, 'Logitech K380 Multi-Device Bluetooth Keyboard', 'Compact wireless keyboard, easily pairs with laptop, tablet, and phone. Ideal for hostel studying and taking lecture notes.', 1250.00, 2995.00, 'Like New', 'assets/images/logitech_k380.jpg', 3, 1, 1, 'available'),
(4, 'Data Structures and Algorithms in C++ (Mark Allen Weiss)', 'Standard university textbook for CSE 3rd sem. Covers trees, graphs, sorting, complexity analysis. Slightly folded cover, interior is crisp.', 240.00, 675.00, 'Fair', 'assets/images/dsa_cpp.jpg', 1, 3, 1, 'available'),
(5, 'GATE CSE 2025 Made Easy Chapterwise Solved Papers', 'Complete 33 years solved question bank for GATE Computer Science. Includes detailed explanations and shortcut formula sheet.', 340.00, 790.00, 'Good', 'assets/images/gate_cse.jpg', 5, 1, 1, 'available'),
(6, 'CSE 3rd Semester Complete Bundle (DS + OOPs + Discrete Math)', 'All 3 major subject books for CSE 2nd year (Tanenbaum/Weiss/Rosen). Save huge by buying the semester bundle together!', 650.00, 1950.00, 'Good', 'assets/images/cse_bundle.jpg', 6, 2, 1, 'available'),
(7, 'Mini Drafter with Engineering Sheet Storage Tube', 'Omega brand engineering mini drafter with clamp, scales, and black waterproof drawing sheet holder tube. Used only 1 semester.', 290.00, 680.00, 'Like New', 'assets/images/mini_drafter.jpg', 7, 3, 1, 'available'),
(8, 'Guyton & Hall Textbook of Medical Physiology (14th Ed)', 'Gold standard reference physiology book for MBBS students. High quality diagrams, neatly covered with transparent sheet.', 980.00, 2600.00, 'Good', 'assets/images/guyton_physiology.jpg', 4, 3, 0, 'available'),
(9, 'Raspberry Pi 4 Model B (4GB RAM) with Acrylic Case', 'Includes 4GB Raspberry Pi, 3 heatsinks installed, acrylic casing with mini cooling fan. Perfect for IoT & final year capstone projects.', 3200.00, 6200.00, 'Good', 'assets/images/raspberry_pi.jpg', 3, 1, 0, 'available'),
(10, 'Concepts of Physics (Vol 1 & 2) - H.C. Verma', 'Legendary physics books set for foundational engineering and JEE/GATE review. Clean inside with highlighted key formulas.', 280.00, 750.00, 'Fair', 'assets/images/hc_verma.jpg', 1, 2, 0, 'available'),
(11, 'Boat Rockerz 450 Wireless Bluetooth Headphones', 'Hostel study companion with 15h battery playback, cushioned ear pads, deep bass, 3.5mm AUX cable included. Sanitized and tested.', 520.00, 1499.00, 'Good', 'assets/images/boat_headphones.jpg', 3, 2, 0, 'available'),
(12, 'Arduino Uno R3 Starter Sensor Kit & Jumper Wires', 'Includes Arduino Uno R3 DIP chip board, ultrasonic sensor, breadboard, LEDs, resistors, buzzer, and 40 jumper wires. Great for lab assignments.', 490.00, 1100.00, 'New', 'assets/images/arduino_kit.jpg', 2, 1, 0, 'available');

-- --------------------------------------------------------
-- SNEAKER STORE MODULE
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `sneakers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `brand` VARCHAR(80) NOT NULL,
  `size` VARCHAR(20) NOT NULL,
  `colour` VARCHAR(50) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `mrp` DECIMAL(10,2) DEFAULT NULL,
  `condition` VARCHAR(50) NOT NULL DEFAULT 'Good',
  `image` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `seller_id` INT NOT NULL,
  `is_featured` TINYINT(1) DEFAULT 0,
  `status` ENUM('available', 'sold') DEFAULT 'available',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sneaker_cart` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `sneaker_id` INT NOT NULL,
  `qty` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `user_sneaker` (`user_id`, `sneaker_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`sneaker_id`) REFERENCES `sneakers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sneaker_orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_code` VARCHAR(30) NOT NULL,
  `user_id` INT NOT NULL,
  `total` DECIMAL(10,2) NOT NULL,
  `address` TEXT NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `payment_method` VARCHAR(50) DEFAULT 'Cash on Campus Pickup',
  `date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('Pending Pickup', 'Confirmed', 'Completed', 'Cancelled') DEFAULT 'Pending Pickup',
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sneaker_order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `sneaker_id` INT NULL,
  `title` VARCHAR(200) NOT NULL,
  `brand` VARCHAR(80) NOT NULL,
  `size` VARCHAR(20) NOT NULL,
  `colour` VARCHAR(50) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `qty` INT NOT NULL DEFAULT 1,
  FOREIGN KEY (`order_id`) REFERENCES `sneaker_orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Sample Sneakers
INSERT INTO `sneakers` (`id`, `title`, `brand`, `size`, `colour`, `price`, `mrp`, `condition`, `image`, `seller_id`, `is_featured`, `description`) VALUES
(1, 'Nike Air Force 1 \'07 Triple White', 'Nike', 'UK 8', 'White', 2400.00, 7495.00, 'Like New', 'assets/images/sneakers/nike_air_force.jpg', 1, 1, 'Authentic Nike Air Force 1 low-top in clean triple white leather. Worn only 3 times for college fests. Completely sanitized, zero creases on toe box.'),
(2, 'Adidas Originals Superstar Classic', 'Adidas', 'UK 9', 'White', 1850.00, 6999.00, 'Good', 'assets/images/sneakers/adidas_superstar.jpg', 2, 1, 'Timeless shell-toe Adidas with black serrated three stripes. Great hostel everyday sneaker, solid traction and rubber cupsole.'),
(3, 'Puma Suede Classic XXI Lows', 'Puma', 'UK 7', 'Black', 1400.00, 5499.00, 'Good', 'assets/images/sneakers/puma_suede.jpg', 3, 1, 'Rich velvety black suede upper with classic Puma contrast white formstrip. Lightly worn for one semester, inner cushion is clean.'),
(4, 'Converse Chuck Taylor All Star High', 'Converse', 'UK 8', 'Red', 1100.00, 3999.00, 'Fair', 'assets/images/sneakers/converse_chuck.jpg', 1, 0, 'High-top canvas sneaker in iconic crimson red. Vintage grunge college look, vulcanized rubber sole completely intact.'),
(5, 'Vans Old Skool Skate Shoes', 'Vans', 'UK 9', 'Black', 1650.00, 4999.00, 'Like New', 'assets/images/sneakers/vans_old_skool.jpg', 2, 1, 'Sturdy suede & canvas old skool skate shoes with signature white side stripe and waffle rubber sole. Barely worn.'),
(6, 'New Balance 574 Core Heritage', 'New Balance', 'UK 10', 'Grey', 2800.00, 8999.00, 'Like New', 'assets/images/sneakers/new_balance_574.jpg', 3, 1, 'Ultra-comfortable ENCAP midsole cushioning with premium heather grey suede mesh. Ideal for walking around college campus all day.'),
(7, 'Air Jordan 1 Mid Chicago Red', 'Jordan', 'UK 9', 'Red', 4200.00, 11495.00, 'Good', 'assets/images/sneakers/jordan_1_mid.jpg', 1, 1, 'Iconic Chicago colourway with Air-Sole unit, Jumpman tongue tag, and Wings logo on collar. Clean condition with box.'),
(8, 'Asics Gel-Kayano 14 Runner', 'Asics', 'UK 8', 'Blue', 2100.00, 6999.00, 'Fair', 'assets/images/sneakers/asics_gel.jpg', 2, 0, 'Late 2000s runner aesthetic with GEL technology cushioning. Super comfortable gym and sports day sneaker.');

-- --------------------------------------------------------
-- CAMPUS CANTEEN MODULE
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `canteen_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `category` ENUM('Snacks', 'Meals', 'Beverages', 'Fast Food') NOT NULL,
  `food_type` ENUM('Veg', 'Non-Veg') NOT NULL DEFAULT 'Veg',
  `price` DECIMAL(10,2) NOT NULL,
  `image` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `prep_time` VARCHAR(50) DEFAULT '10-15 mins',
  `is_available` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `canteen_cart` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `item_id` INT NOT NULL,
  `qty` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `user_canteen` (`user_id`, `item_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`item_id`) REFERENCES `canteen_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `canteen_orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_code` VARCHAR(30) NOT NULL,
  `token_number` INT NOT NULL,
  `user_id` INT NOT NULL,
  `total` DECIMAL(10,2) NOT NULL,
  `pickup_time` VARCHAR(100) NOT NULL,
  `pickup_counter` VARCHAR(100) NOT NULL DEFAULT 'Counter 2 (Quick Service)',
  `phone` VARCHAR(20) NOT NULL,
  `special_notes` TEXT,
  `payment_method` VARCHAR(50) DEFAULT 'Pay at Canteen Counter',
  `date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('Order Received', 'Kitchen Preparing', 'Ready for Pickup', 'Picked Up', 'Cancelled') DEFAULT 'Order Received',
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `canteen_order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `item_id` INT NULL,
  `title` VARCHAR(150) NOT NULL,
  `category` VARCHAR(50) NOT NULL,
  `food_type` VARCHAR(20) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `qty` INT NOT NULL DEFAULT 1,
  FOREIGN KEY (`order_id`) REFERENCES `canteen_orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Sample Canteen Menu
INSERT INTO `canteen_items` (`id`, `title`, `category`, `food_type`, `price`, `image`, `prep_time`, `description`) VALUES
(1, 'Masala Cutting Chai', 'Beverages', 'Veg', 15.00, 'assets/images/canteen/masala_chai.jpg', '3 mins', 'Authentic freshly boiled college canteen chai infused with crushed ginger, cardamom, and whole milk.'),
(2, 'Crispy Punjabi Samosa Plate (2 Pcs)', 'Snacks', 'Veg', 30.00, 'assets/images/canteen/samosa_plate.jpg', 'Instant', 'Golden crisp flaky crust stuffed with spicy mashed potato & green peas, served with sweet tamarind and spicy mint chutneys.'),
(3, 'Tandoori Paneer Roll', 'Snacks', 'Veg', 70.00, 'assets/images/canteen/paneer_roll.jpg', '8 mins', 'Tandoor-marinated spicy paneer cubes wrapped in a layered flaky paratha with sliced red onions and mint mayo.'),
(4, 'Deluxe North Indian Student Thali', 'Meals', 'Veg', 95.00, 'assets/images/canteen/special_thali.jpg', '5 mins', 'Complete student lunch thali with Shahi Paneer, Dal Makhani, Jeera Rice, 3 Butter Tawa Rotis, Salad, and Gulab Jamun.'),
(5, 'Amritsari Chole Bhature (2 Pcs)', 'Meals', 'Veg', 80.00, 'assets/images/canteen/chole_bhature.jpg', '8 mins', 'Two puffy golden bhature served with slow-cooked spicy chickpea gravy, pickled carrot, and sliced green chillies.'),
(6, 'Crispy Veggie Cheese Burger', 'Fast Food', 'Veg', 65.00, 'assets/images/canteen/cheese_burger.jpg', '10 mins', 'Crispy herb potato patty topped with sliced cheddar cheese, fresh iceberg lettuce, tomatoes, and thousand island sauce.'),
(7, 'Peri-Peri French Fries', 'Fast Food', 'Veg', 55.00, 'assets/images/canteen/peri_fries.jpg', '7 mins', 'Freshly cut potato fries dusted generously with hot African peri-peri spices. Served with garlic dip.'),
(8, 'Thick Frappe Cold Coffee', 'Beverages', 'Veg', 45.00, 'assets/images/canteen/cold_coffee.jpg', '4 mins', 'Frothy ice-cold blended coffee with rich dark cocoa syrup and a scoop of vanilla ice cream.'),
(9, 'Hostel Cheese Tadka Maggi', 'Fast Food', 'Veg', 50.00, 'assets/images/canteen/veg_maggi.jpg', '6 mins', 'The legendary midnight hostel staple cooked with onion, tomatoes, green chilies, double tastemaker, and melted cheese on top.');

SET FOREIGN_KEY_CHECKS = 1;


